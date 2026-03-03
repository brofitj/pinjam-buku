<?php

if (!isset($_SESSION['user'])) {
    header("Location: /login");
    exit;
}

$breadcrumbs = [
    ['label' => 'Anggota', 'url' => '/member'],
    ['label' => 'Tambah'],
];

ob_start();

?>

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Tambah Anggota
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Central hub for personal customization.
            </div>
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
                <form id="member_add_form" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono" for="name">
                                Foto
                            </label>
                        </div>
                        <div class="form-row-field">
                            <div class="kt-image-input size-16" data-kt-image-input="true">
                                <input
                                    type="file"
                                    name="avatar"
                                    accept=".png, .jpg, .jpeg"
                                />
                                <input
                                    type="hidden"
                                    name="avatar_remove"
                                />

                                <div
                                    class="kt-image-input-placeholder border-2 border-green-500 kt-image-input-empty:border-input"
                                    data-kt-image-input-placeholder="true"
                                    style="background-image: url('/themes/metronic/dist/assets/media/avatars/blank.png')"
                                >
                                    <div
                                        class="kt-image-input-preview"
                                        data-kt-image-input-preview="true"
                                        style="background-image: url('/media/avatars/300-2.png')"
                                    ></div>

                                    <div class="flex items-center justify-center cursor-pointer h-5 left-0 right-0 bottom-0 bg-black/25 absolute">
                                        <svg
                                            class="fill-border opacity-80"
                                            width="14"
                                            height="12"
                                            viewBox="0 0 14 12"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path d="M11.6665 2.64585H11.2232C11.0873 2.64749 10.9538 2.61053 10.8382 2.53928C10.7225 2.46803 10.6295 2.36541 10.5698 2.24335L10.0448 1.19918C9.91266 0.931853 9.70808 0.707007 9.45438 0.550249C9.20068 0.393491 8.90806 0.311121 8.60984 0.312517H5.38984C5.09162 0.311121 4.799 0.393491 4.5453 0.550249C4.2916 0.707007 4.08701 0.931853 3.95484 1.19918L3.42984 2.24335C3.37021 2.36541 3.27716 2.46803 3.1615 2.53928C3.04584 2.61053 2.91234 2.64749 2.7765 2.64585H2.33317C1.90772 2.64585 1.49969 2.81486 1.19885 3.1157C0.898014 3.41654 0.729004 3.82457 0.729004 4.25002V10.0834C0.729004 10.5088 0.898014 10.9168 1.19885 11.2177C1.49969 11.5185 1.90772 11.6875 2.33317 11.6875H11.6665C12.092 11.6875 12.5 11.5185 12.8008 11.2177C13.1017 10.9168 13.2707 10.5088 13.2707 10.0834V4.25002C13.2707 3.82457 13.1017 3.41654 12.8008 3.1157C12.5 2.81486 12.092 2.64585 11.6665 2.64585ZM6.99984 9.64585C6.39413 9.64585 5.80203 9.46624 5.2984 9.12973C4.79478 8.79321 4.40225 8.31492 4.17046 7.75532C3.93866 7.19572 3.87802 6.57995 3.99618 5.98589C4.11435 5.39182 4.40602 4.84613 4.83432 4.41784C5.26262 3.98954 5.80831 3.69786 6.40237 3.5797C6.99644 3.46153 7.61221 3.52218 8.1718 3.75397C8.7314 3.98576 9.2097 4.37829 9.54621 4.88192C9.88272 5.38554 10.0623 5.97765 10.0623 6.58335C10.0608 7.3951 9.73765 8.17317 9.16365 8.74716C8.58965 9.32116 7.81159 9.64431 7 9.64585Z"></path>
                                            <path d="M7 8.77087C8.20812 8.77087 9.1875 7.7915 9.1875 6.58337C9.1875 5.37525 8.20812 4.39587 7 4.39587C5.79188 4.39587 4.8125 5.37525 4.8125 6.58337C4.8125 7.7915 5.79188 8.77087 7 8.77087Z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="kt-image-input-remove"
                                    data-kt-image-input-remove="true"
                                    data-kt-tooltip="true"
                                    data-kt-tooltip-placement="right"
                                    data-kt-tooltip-trigger="hover"
                                >
                                    <i class="ki-filled ki-cross"></i>
                                    <span class="kt-tooltip" data-kt-tooltip-content="true">
                                        Click to remove or revert
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono" for="name">
                                Nama Lengkap
                            </label>
                        </div>
                        <div class="form-row-field">
                            <input
                                id="name"
                                type="text"
                                name="name"
                                class="kt-input w-full"
                                placeholder="John Doe"
                                value=""
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label">
                            <span class="kt-form-label font-normal text-mono">
                                Gender
                            </span>
                        </div>
                        <div class="form-row-field">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <label class="inline-flex items-center gap-2.5 me-3">
                                    <input
                                        type="radio"
                                        name="gender"
                                        value="male"
                                        class="kt-radio radio-sm"
                                        checked
                                    />
                                    <span class="text-sm">
                                        Laki-laki
                                    </span>
                                </label>
                                <label class="inline-flex items-center gap-2.5 me-3">
                                    <input
                                        type="radio"
                                        name="gender"
                                        value="female"
                                        class="kt-radio radio-sm"
                                    />
                                    <span class="text-sm">
                                        Perempuan
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono" for="whatsapp">
                                Nomor WA
                            </label>
                        </div>
                        <div class="form-row-field">
                            <input
                                id="whatsapp"
                                type="number"
                                name="phone"
                                class="kt-input w-full"
                                placeholder="628xxxxxxxxxx"
                                value=""
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono" for="address">
                                Alamat
                            </label>
                        </div>
                        <div class="form-row-field">
                            <input
                                id="address"
                                type="text"
                                name="address"
                                class="kt-input w-full"
                                placeholder="Tulis alamat lengkap"
                                value=""
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono" for="email">
                                Email
                            </label>
                        </div>
                        <div class="form-row-field">
                            <input
                                id="email"
                                type="email"
                                name="email"
                                class="kt-input w-full"
                                placeholder="email@domain.com"
                                value=""
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label">
                            <label class="kt-form-label font-normal text-mono" for="password">
                                Password
                            </label>
                        </div>
                        <div class="form-row-field">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="kt-input w-full"
                                placeholder="••••••••"
                                value=""
                            />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label">
                            <span class="kt-form-label font-normal text-mono">
                                Status
                            </span>
                        </div>
                        <div class="form-row-field">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <label class="inline-flex items-center gap-2.5 me-3">
                                    <input
                                        type="radio"
                                        name="status"
                                        value="active"
                                        class="kt-radio radio-sm"
                                        checked
                                    />
                                    <span class="text-sm">
                                        Aktif
                                    </span>
                                </label>
                                <label class="inline-flex items-center gap-2.5 me-3">
                                    <input
                                        type="radio"
                                        name="status"
                                        value="inactive"
                                        class="kt-radio radio-sm"
                                    />
                                    <span class="text-sm">
                                        Tidak Aktif
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-row-label" style="margin: 0 !important"></div>
                        <div class="form-row-field">
                            <button class="kt-btn kt-btn-primary" id="member_add_submit" type="submit">
                                Simpan
                            </button>
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

<script src="/assets/js/admin/member/add.js"></script>
