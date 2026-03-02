<?php

ob_start();

?>

<?php if (!empty($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="/login" class="kt-card-content flex flex-col gap-5 p-10" id="sign_in_form">
    <div class="text-center mb-2.5">
        <h3 class="text-lg font-medium text-mono leading-none mb-2.5">
            Sign in
        </h3>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">
            Email
        </label>
        <input class="kt-input" placeholder="email@email.com" type="email" name="email" value="<?= isset($oldEmail) ? htmlspecialchars($oldEmail) : '' ?>"/>
    </div>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label font-normal text-mono">
            Password
        </label>
        <div class="kt-input" data-kt-toggle-password="true">
            <input placeholder="Enter Password" type="password" name="password" value=""/>
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
        Sign In
    </button>
</form>

<?php

$content = ob_get_clean();

require __DIR__ . '/../layouts/auth/app.php';