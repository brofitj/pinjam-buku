<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Logger;

class BookController
{
    private function isAuthenticated(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user']);
    }

    /**
     * Return list of books as JSON.
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Unauthenticated']);
            return;
        }

        $db = Database::getInstance();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $q = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];

        if ($q !== '') {
            $where .= " AND (
                b.book_code LIKE :q_code
                OR b.title LIKE :q_title
                OR b.author LIKE :q_author
                OR b.publisher LIKE :q_publisher
                OR b.isbn LIKE :q_isbn
            )";

            $like = '%' . $q . '%';
            $params[':q_code'] = $like;
            $params[':q_title'] = $like;
            $params[':q_author'] = $like;
            $params[':q_publisher'] = $like;
            $params[':q_isbn'] = $like;
        }

        $allowedSort = [
            'id' => 'b.id',
            'book_code' => 'b.book_code',
            'title' => 'b.title',
            'author' => 'b.author',
            'publication_year' => 'b.publication_year',
            'stock' => 'b.stock',
        ];

        $sortBy = $_GET['sort_by'] ?? 'id';
        $sortDir = strtolower($_GET['sort_dir'] ?? 'desc');

        if (!isset($allowedSort[$sortBy])) {
            $sortBy = 'id';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $orderBy = $allowedSort[$sortBy] . ' ' . strtoupper($sortDir);

        $countSql = "
            SELECT COUNT(*) AS total
            FROM tbr_books b
            WHERE $where
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $dataSql = "
            SELECT
                b.id,
                b.book_code,
                b.title,
                b.author,
                b.publisher,
                b.publication_year,
                b.isbn,
                b.stock,
                b.cover_image,
                b.description
            FROM tbr_books b
            WHERE $where
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";

        $dataStmt = $db->prepare($dataSql);
        foreach ($params as $key => $value) {
            $dataStmt->bindValue($key, $value);
        }
        $dataStmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $dataStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $dataStmt->execute();

        $books = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode([
            'data' => $books,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * Create book via AJAX.
     */
    public function store(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes = $this->toBytes((string)ini_get('post_max_size'));
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            http_response_code(413);
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran total upload melebihi batas server (' . ($this->toReadableSize($postMaxBytes)) . '). Perkecil file atau minta admin menaikkan post_max_size.',
            ]);
            return;
        }

        $bookCode = trim((string)($_POST['book_code'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $author = trim((string)($_POST['author'] ?? ''));
        $publisher = trim((string)($_POST['publisher'] ?? ''));
        $publicationYear = trim((string)($_POST['publication_year'] ?? ''));
        $isbn = trim((string)($_POST['isbn'] ?? ''));
        $stock = (int)($_POST['stock'] ?? 0);
        $description = trim((string)($_POST['description'] ?? ''));

        if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Data form tidak terbaca. Kemungkinan ukuran upload terlalu besar.',
            ]);
            return;
        }

        if ($bookCode === '' || $title === '' || $author === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Kode buku, judul, dan penulis wajib diisi.',
            ]);
            return;
        }

        if ($publicationYear !== '' && !preg_match('/^(19|20)\d{2}$/', $publicationYear)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Tahun terbit tidak valid.',
            ]);
            return;
        }

        if ($stock < 0) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Stok tidak boleh kurang dari 0.',
            ]);
            return;
        }

        $coverFileName = null;
        if (isset($_FILES['cover_image']) && (int)($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $coverFileName = $this->processCoverUpload($_FILES['cover_image']);
            if ($coverFileName === null) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Upload cover gagal. Pastikan file berupa JPG, PNG, atau WEBP (maks 5MB).',
                ]);
                return;
            }
        }

        $db = Database::getInstance();

        $codeCheckStmt = $db->prepare('SELECT id FROM tbr_books WHERE book_code = :book_code LIMIT 1');
        $codeCheckStmt->execute([':book_code' => $bookCode]);
        if ($codeCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Kode buku sudah digunakan.',
            ]);
            return;
        }

        if ($isbn !== '') {
            $isbnCheckStmt = $db->prepare('SELECT id FROM tbr_books WHERE isbn = :isbn LIMIT 1');
            $isbnCheckStmt->execute([':isbn' => $isbn]);
            if ($isbnCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'ISBN sudah digunakan.',
                ]);
                return;
            }
        }

        try {
            $insertStmt = $db->prepare(
                "INSERT INTO tbr_books
                    (book_code, title, author, publisher, publication_year, isbn, stock, cover_image, description, created_at, updated_at)
                 VALUES
                    (:book_code, :title, :author, :publisher, :publication_year, :isbn, :stock, :cover_image, :description, NOW(), NOW())"
            );

            $insertStmt->execute([
                ':book_code' => $bookCode,
                ':title' => $title,
                ':author' => $author,
                ':publisher' => $publisher !== '' ? $publisher : null,
                ':publication_year' => $publicationYear !== '' ? (int)$publicationYear : null,
                ':isbn' => $isbn !== '' ? $isbn : null,
                ':stock' => $stock,
                ':cover_image' => $coverFileName,
                ':description' => $description !== '' ? $description : null,
            ]);
        } catch (\Throwable $e) {
            Logger::error('Create book failed', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan buku.',
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Data buku berhasil ditambahkan.',
        ]);
    }

    /**
     * Get book detail by id.
     */
    public function show(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        $bookId = (int)($_GET['id'] ?? 0);
        if ($bookId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID buku tidak valid.']);
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, book_code, title, author, publisher, publication_year, isbn, stock, cover_image, description
             FROM tbr_books
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $bookId]);
        $book = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data buku tidak ditemukan.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $book,
        ]);
    }

    /**
     * Update book by id via AJAX.
     */
    public function update(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes = $this->toBytes((string)ini_get('post_max_size'));
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            http_response_code(413);
            echo json_encode([
                'success' => false,
                'message' => 'Ukuran total upload melebihi batas server (' . ($this->toReadableSize($postMaxBytes)) . '). Perkecil file atau minta admin menaikkan post_max_size.',
            ]);
            return;
        }

        $bookId = (int)($_POST['id'] ?? 0);
        $bookCode = trim((string)($_POST['book_code'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        $author = trim((string)($_POST['author'] ?? ''));
        $publisher = trim((string)($_POST['publisher'] ?? ''));
        $publicationYear = trim((string)($_POST['publication_year'] ?? ''));
        $isbn = trim((string)($_POST['isbn'] ?? ''));
        $stock = (int)($_POST['stock'] ?? 0);
        $description = trim((string)($_POST['description'] ?? ''));
        $removeCover = in_array((string)($_POST['cover_remove'] ?? ''), ['1', 'true', 'on'], true);

        if ($bookId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID buku tidak valid.']);
            return;
        }

        if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Data form tidak terbaca. Kemungkinan ukuran upload terlalu besar.',
            ]);
            return;
        }

        if ($bookCode === '' || $title === '' || $author === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Kode buku, judul, dan penulis wajib diisi.',
            ]);
            return;
        }

        if ($publicationYear !== '' && !preg_match('/^(19|20)\d{2}$/', $publicationYear)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Tahun terbit tidak valid.',
            ]);
            return;
        }

        if ($stock < 0) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Stok tidak boleh kurang dari 0.',
            ]);
            return;
        }

        $db = Database::getInstance();
        $bookStmt = $db->prepare('SELECT id, cover_image FROM tbr_books WHERE id = :id LIMIT 1');
        $bookStmt->execute([':id' => $bookId]);
        $book = $bookStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data buku tidak ditemukan.']);
            return;
        }

        $codeCheckStmt = $db->prepare('SELECT id FROM tbr_books WHERE book_code = :book_code AND id != :id LIMIT 1');
        $codeCheckStmt->execute([':book_code' => $bookCode, ':id' => $bookId]);
        if ($codeCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Kode buku sudah digunakan.']);
            return;
        }

        if ($isbn !== '') {
            $isbnCheckStmt = $db->prepare('SELECT id FROM tbr_books WHERE isbn = :isbn AND id != :id LIMIT 1');
            $isbnCheckStmt->execute([':isbn' => $isbn, ':id' => $bookId]);
            if ($isbnCheckStmt->fetch(\PDO::FETCH_ASSOC)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'ISBN sudah digunakan.']);
                return;
            }
        }

        $oldCover = trim((string)($book['cover_image'] ?? ''));
        $newCover = $oldCover !== '' ? $oldCover : null;
        $hasUploadedCover = isset($_FILES['cover_image']) && (int)($_FILES['cover_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($hasUploadedCover) {
            $processedCover = $this->processCoverUpload($_FILES['cover_image']);
            if ($processedCover === null) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => 'Upload cover gagal. Pastikan file berupa JPG, PNG, atau WEBP (maks 5MB).',
                ]);
                return;
            }
            $newCover = $processedCover;
        } elseif ($removeCover) {
            $newCover = null;
        }

        try {
            $updateStmt = $db->prepare(
                "UPDATE tbr_books
                 SET book_code = :book_code,
                     title = :title,
                     author = :author,
                     publisher = :publisher,
                     publication_year = :publication_year,
                     isbn = :isbn,
                     stock = :stock,
                     cover_image = :cover_image,
                     description = :description,
                     updated_at = NOW()
                 WHERE id = :id
                 LIMIT 1"
            );

            $updateStmt->execute([
                ':book_code' => $bookCode,
                ':title' => $title,
                ':author' => $author,
                ':publisher' => $publisher !== '' ? $publisher : null,
                ':publication_year' => $publicationYear !== '' ? (int)$publicationYear : null,
                ':isbn' => $isbn !== '' ? $isbn : null,
                ':stock' => $stock,
                ':cover_image' => $newCover,
                ':description' => $description !== '' ? $description : null,
                ':id' => $bookId,
            ]);
        } catch (\Throwable $e) {
            Logger::error('Update book failed', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal memperbarui buku.',
            ]);
            return;
        }

        if (($hasUploadedCover || $removeCover) && $oldCover !== '' && $oldCover !== $newCover) {
            $oldCoverPath = dirname(__DIR__, 2) . '/storage/covers/books/' . $oldCover;
            if (is_file($oldCoverPath)) {
                @unlink($oldCoverPath);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Data buku berhasil diperbarui.',
        ]);
    }

    /**
     * Delete book by id via AJAX.
     */
    public function delete(): void
    {
        header('Content-Type: application/json');

        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        $bookId = (int)($_POST['id'] ?? 0);
        if ($bookId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID buku tidak valid.']);
            return;
        }

        $db = Database::getInstance();
        $bookStmt = $db->prepare('SELECT id, cover_image FROM tbr_books WHERE id = :id LIMIT 1');
        $bookStmt->execute([':id' => $bookId]);
        $book = $bookStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$book) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Data buku tidak ditemukan.']);
            return;
        }

        $deleteStmt = $db->prepare('DELETE FROM tbr_books WHERE id = :id LIMIT 1');
        $deleteStmt->execute([':id' => $bookId]);

        $coverImage = trim((string)($book['cover_image'] ?? ''));
        if ($coverImage !== '' && preg_match('/^[a-zA-Z0-9._-]+$/', $coverImage) === 1) {
            $coverPath = dirname(__DIR__, 2) . '/storage/covers/books/' . $coverImage;
            if (is_file($coverPath)) {
                @unlink($coverPath);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Data buku berhasil dihapus.',
        ]);
    }

    /**
     * Serve book cover image from storage.
     */
    public function cover(): void
    {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            return;
        }

        $file = basename((string)($_GET['file'] ?? ''));
        if ($file === '' || preg_match('/^[a-zA-Z0-9._-]+$/', $file) !== 1) {
            http_response_code(404);
            return;
        }

        $path = dirname(__DIR__, 2) . '/storage/covers/books/' . $file;
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }

        $mime = @mime_content_type($path) ?: 'image/jpeg';
        if (!str_starts_with($mime, 'image/')) {
            http_response_code(404);
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
    }

    private function processCoverUpload(array $file): ?string
    {
        if (
            !function_exists('imagecreatetruecolor') ||
            !function_exists('imagecopyresampled') ||
            !function_exists('imagejpeg')
        ) {
            return null;
        }

        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return null;
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return null;
        }

        $info = @getimagesize($tmpPath);
        if (!$info || empty($info[2])) {
            return null;
        }

        $imageType = (int)$info[2];
        $source = null;
        $saveExt = 'jpg';

        if ((int)$info[2] === IMAGETYPE_JPEG) {
            $source = @imagecreatefromjpeg($tmpPath);
        } elseif ((int)$info[2] === IMAGETYPE_PNG) {
            $source = @imagecreatefrompng($tmpPath);
        } elseif ((int)$info[2] === IMAGETYPE_WEBP) {
            if (function_exists('imagecreatefromwebp')) {
                $source = @imagecreatefromwebp($tmpPath);
            }
        }

        if (!$source || !in_array($imageType, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        if ($srcWidth <= 0 || $srcHeight <= 0) {
            unset($source);
            return null;
        }

        // Keep output ratio 3:4 using center crop.
        $targetRatio = 3 / 4;
        $srcRatio = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            $cropHeight = $srcHeight;
            $cropWidth = (int)round($srcHeight * $targetRatio);
            $cropX = (int)round(($srcWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $srcWidth;
            $cropHeight = (int)round($srcWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int)round(($srcHeight - $cropHeight) / 2);
        }

        $targetWidth = 600;
        $targetHeight = 800;
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$target) {
            unset($source);
            return null;
        }

        $white = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $white);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight
        );

        $dir = dirname(__DIR__, 2) . '/storage/covers/books';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            unset($source, $target);
            return null;
        }

        try {
            $rand = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $rand = (string)mt_rand(1000, 9999);
        }

        $fileName = 'book_' . date('YmdHis') . '_' . $rand . '.' . $saveExt;
        $savePath = $dir . '/' . $fileName;
        $saved = imagejpeg($target, $savePath, 80);
        unset($source, $target);
        if (!$saved) {
            return null;
        }

        return $fileName;
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float)$value;
        $unit = strtolower(substr($value, -1));

        if ($unit === 'g') {
            return (int)($number * 1024 * 1024 * 1024);
        }
        if ($unit === 'm') {
            return (int)($number * 1024 * 1024);
        }
        if ($unit === 'k') {
            return (int)($number * 1024);
        }

        return (int)$number;
    }

    private function toReadableSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        }
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
