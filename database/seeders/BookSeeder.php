<?php

use App\Core\Database;

class BookSeeder
{
    public function run()
    {
        $db = Database::getInstance();

        $countStmt = $db->query('SELECT COUNT(*) FROM tbr_books');
        $existingCount = (int)$countStmt->fetchColumn();
        $targetCount = 50;

        if ($existingCount >= $targetCount) {
            echo "BookSeeder skipped. Existing books: {$existingCount}\n";
            return;
        }

        $toInsert = $targetCount - $existingCount;
        $startNo = $existingCount + 1;

        $titles = [
            'Pemrograman PHP Dasar',
            'Algoritma dan Struktur Data',
            'Belajar MySQL Praktis',
            'Dasar-dasar Jaringan Komputer',
            'Pengantar Sistem Informasi',
            'Manajemen Perpustakaan Modern',
            'Pemrograman Web Lanjutan',
            'Rekayasa Perangkat Lunak',
            'Dasar UI dan UX',
            'Pemodelan Basis Data',
        ];

        $authors = [
            'Andi Pratama',
            'Budi Santoso',
            'Citra Lestari',
            'Dewi Anggraini',
            'Eko Saputra',
            'Fajar Nugroho',
            'Gita Permata',
            'Hendra Wijaya',
            'Indra Kurniawan',
            'Joko Susilo',
        ];

        $publishers = [
            'Informatika Nusantara',
            'Media Ilmu',
            'Pustaka Cendekia',
            'Tekno Press',
            'Pena Digital',
        ];

        $stmt = $db->prepare(
            "INSERT INTO tbr_books
                (book_code, title, author, publisher, publication_year, isbn, stock, cover_image, description, created_at, updated_at)
             VALUES
                (:book_code, :title, :author, :publisher, :publication_year, :isbn, :stock, NULL, :description, NOW(), NOW())"
        );

        for ($i = 0; $i < $toInsert; $i++) {
            $no = $startNo + $i;
            $title = $titles[$i % count($titles)] . ' Vol. ' . $no;
            $author = $authors[$i % count($authors)];
            $publisher = $publishers[$i % count($publishers)];
            $year = (string)(2010 + ($no % 16));
            $isbn = '978602' . str_pad((string)$no, 7, '0', STR_PAD_LEFT);
            $stock = 3 + ($no % 18);

            $stmt->execute([
                ':book_code' => 'BK' . str_pad((string)$no, 5, '0', STR_PAD_LEFT),
                ':title' => $title,
                ':author' => $author,
                ':publisher' => $publisher,
                ':publication_year' => $year,
                ':isbn' => $isbn,
                ':stock' => $stock,
                ':description' => 'Buku dummy untuk kebutuhan pengembangan module buku, data nomor ' . $no . '.',
            ]);
        }

        echo "BookSeeder inserted {$toInsert} books. Total books now at least {$targetCount}.\n";
    }
}

