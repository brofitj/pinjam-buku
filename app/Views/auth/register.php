<?php

ob_start();

?>

<?php if (!empty($error)): ?>
    <div class="p-10 pb-0">
        <div id="alert_register_error" class="kt-alert kt-alert-destructive mx-10">
            <div class="kt-alert-content text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
            <div class="kt-alert-toolbar">
                <div class="kt-alert-actions">
                    <button class="kt-alert-close" data-kt-dismiss="#alert_register_error">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-x"
                            aria-hidden="true"
                        >
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="p-10 pb-0">
        <div id="alert_register_success" class="kt-alert kt-alert-success mx-10">
            <div class="kt-alert-content text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
            <div class="kt-alert-toolbar">
                <div class="kt-alert-actions">
                    <button class="kt-alert-close" data-kt-dismiss="#alert_register_success">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="lucide lucide-x"
                            aria-hidden="true"
                        >
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="/register" class="kt-card-content flex flex-col gap-5 p-10" id="sign_up_form">
    <div class="text-center mb-2.5">
        <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
            Sign up as Member
        </h3>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">
            Nama
        </label>
        <input class="kt-input" placeholder="Nama lengkap" type="text" name="name" value="<?= htmlspecialchars((string)$oldName) ?>"/>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">
            Email
        </label>
        <input class="kt-input" placeholder="email@email.com" type="email" name="email" value="<?= htmlspecialchars((string)$oldEmail) ?>"/>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">
            Password
        </label>
        <div class="kt-input" data-kt-toggle-password="true">
            <input placeholder="Minimal 6 karakter" type="password" name="password" value=""/>
            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                <span class="kt-toggle-password-active:hidden">
                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                </span>
                <span class="hidden kt-toggle-password-active:block">
                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                </span>
            </button>
        </div>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">
            Konfirmasi Password
        </label>
        <div class="kt-input" data-kt-toggle-password="true">
            <input placeholder="Ulangi password" type="password" name="password_confirmation" value=""/>
            <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5" data-kt-toggle-password-trigger="true" type="button">
                <span class="kt-toggle-password-active:hidden">
                    <i class="ki-filled ki-eye text-muted-foreground"></i>
                </span>
                <span class="hidden kt-toggle-password-active:block">
                    <i class="ki-filled ki-eye-slash text-muted-foreground"></i>
                </span>
            </button>
        </div>
    </div>
    <button type="submit" class="kt-btn kt-btn-primary flex justify-center grow">
        Daftar
    </button>
    <div class="text-center text-sm text-secondary-foreground">
        Sudah punya akun?
        <a href="/login" class="text-primary font-medium">Kembali ke login</a>
    </div>
</form>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/auth/app.php';
