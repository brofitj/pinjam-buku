<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

$breadcrumbs = [
    ['label' => 'Buku', 'url' => '/book'],
    ['label' => 'Ubah'],
];

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Ubah Buku
            </h1>
        </div>
    </div>
</div>

<div class="kt-container-fixed pb-7.5">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card min-w-full">
            <div class="kt-card-content flex flex-col gap-0 p-0">
                <style>
                    .form-row { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); }
                    .form-row:last-child { border: none; }
                    .form-row-label { margin-bottom: 0.5rem; }

                    @media (min-width: 768px) {
                        .form-row { display: flex; align-items: center; gap: 1.5rem; }
                        .form-row-label { flex: 0 0 10rem; margin-bottom: 0; }
                        .form-row-field { flex: 1 1 auto; display: flex; align-items: center; }
                    }
                </style>

                <form id="book_edit_form" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="book_edit_id" value="<?= (int)($_GET['id'] ?? 0) ?>">

                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono">Cover</label>
                        </div>
                        <div class="form-row-field">
                            <div class="kt-image-input size-24" data-kt-image-input="true">
                                <input type="file" name="cover_image" accept=".png, .jpg, .jpeg, .webp" />
                                <input type="hidden" name="cover_remove" id="cover_remove" />
                                <div
                                    class="kt-image-input-placeholder border-2 border-green-500 kt-image-input-empty:border-input"
                                    data-kt-image-input-placeholder="true"
                                    style="background-image: url('/themes/metronic/dist/assets/media/avatars/blank.png')"
                                >
                                    <div
                                        class="kt-image-input-preview"
                                        id="book_cover_preview"
                                        data-kt-image-input-preview="true"
                                        style="background-image: url('/themes/metronic/dist/assets/media/avatars/blank.png')"
                                    ></div>
                                    <div class="flex items-center justify-center cursor-pointer h-6 left-0 right-0 bottom-0 bg-black/25 absolute">
                                        <i class="ki-filled ki-picture text-white"></i>
                                    </div>
                                </div>
                                <button type="button" class="kt-image-input-remove" data-kt-image-input-remove="true">
                                    <i class="ki-filled ki-cross"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="book_code">Kode Buku</label></div>
                        <div class="form-row-field"><input id="book_code" type="text" name="book_code" class="kt-input w-full" placeholder="BK00051" value="" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="title">Judul</label></div>
                        <div class="form-row-field"><input id="title" type="text" name="title" class="kt-input w-full" placeholder="Judul buku" value="" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="author">Penulis</label></div>
                        <div class="form-row-field"><input id="author" type="text" name="author" class="kt-input w-full" placeholder="Nama penulis" value="" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="publisher">Penerbit</label></div>
                        <div class="form-row-field"><input id="publisher" type="text" name="publisher" class="kt-input w-full" placeholder="Nama penerbit" value="" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="publication_year">Tahun Terbit</label></div>
                        <div class="form-row-field"><input id="publication_year" type="number" name="publication_year" class="kt-input w-full" placeholder="2024" min="1900" max="2099" value="" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="isbn">ISBN</label></div>
                        <div class="form-row-field"><input id="isbn" type="text" name="isbn" class="kt-input w-full" placeholder="978xxxxxxxxxx" value="" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="stock">Stok</label></div>
                        <div class="form-row-field"><input id="stock" type="number" name="stock" class="kt-input w-full" placeholder="0" min="0" value="0" /></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label"><label class="kt-form-label font-normal text-mono" for="description">Deskripsi</label></div>
                        <div class="form-row-field"><textarea id="description" name="description" class="kt-textarea w-full" placeholder="Deskripsi singkat buku"></textarea></div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label" style="margin: 0 !important"></div>
                        <div class="form-row-field">
                            <button class="kt-btn kt-btn-primary" id="book_edit_submit" type="submit">Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php

$content = ob_get_clean();

require __DIR__ . '/../../layouts/admin/app.php';

?>

<script src="/assets/js/admin/book/edit.js"></script>
