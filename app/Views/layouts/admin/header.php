<header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background" data-kt-sticky="true" data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
    <!-- Container -->
    <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
        <!-- Mobile Logo -->
        <div class="flex gap-2.5 lg:hidden items-center -ms-1">
            <a class="shrink-0" href="#">
                <img class="max-h-[25px] w-full" src="/themes/metronic/dist/assets/media/app/mini-logo.svg"/>
            </a>
            <div class="flex items-center">
                <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar">
                    <i class="ki-filled ki-menu"></i>
                </button>
                <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#mega_menu_wrapper">
                    <i class="ki-filled ki-burger-menu-2"></i>
                </button>
            </div>
        </div>
        <!-- End of Mobile Logo -->
        <!-- Breadcrumbs -->
        <?php if (!empty($breadcrumbs)): ?>
            <div class="flex [.kt-header_&]:below-lg:hidden items-center gap-1.25 text-xs lg:text-sm font-medium mb-2.5 lg:mb-0 [--kt-reparent-target:#contentContainer] lg:[--kt-reparent-target:#headerContainer] [--kt-reparent-mode:prepend] lg:[--kt-reparent-mode:prepend]" data-kt-reparent="true">
                <?php foreach ($breadcrumbs as $index => $bc): ?>
                    <?php if ($index > 0): ?>
                        <i class="ki-filled ki-right text-muted-foreground text-[10px]"></i>
                    <?php endif; ?>

                    <?php if (!empty($bc['url'])): ?>
                        <a href="<?= htmlspecialchars($bc['url']) ?>" class="text-secondary-foreground hover:text-primary">
                            <?= htmlspecialchars($bc['label']) ?>
                        </a>
                    <?php else: ?>
                        <span class="text-secondary-foreground">
                            <?= htmlspecialchars($bc['label']) ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <!-- End of Breadcrumbs -->
        <!-- Topbar -->
        <div class="flex items-center gap-2.5">
            <!-- User -->
            <div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-end" data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
                <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                    <img alt="" class="size-9 rounded-full border-2 border-green-500 shrink-0" src="/themes/metronic/dist/assets/media/avatars/300-2.png"/>
                </div>
                <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                    <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                        <div class="flex items-center gap-2">
                            <img alt="" class="size-9 shrink-0 rounded-full border-2 border-green-500" src="/themes/metronic/dist/assets/media/avatars/300-2.png"/>
                            <div class="flex flex-col gap-1.5">
                                <span class="text-sm text-foreground font-semibold leading-none">
                                    Cody Fisher
                                </span>
                                <a class="text-xs text-secondary-foreground hover:text-primary font-medium leading-none" href="#">
                                    fisher@gmail.com
                                </a>
                            </div>
                        </div>
                        <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline">
                            Superadmin
                        </span>
                    </div>
                    <ul class="kt-dropdown-menu-sub">
                        <li>
                            <div class="kt-dropdown-menu-separator"></div>
                        </li>
                        <li>
                            <a class="kt-dropdown-menu-link" href="#">
                                <i class="ki-filled ki-profile-circle"></i>
                                My Profile
                            </a>
                        </li>
                        <li>
                            <div class="kt-dropdown-menu-separator"></div>
                        </li>
                    </ul>
                    <div class="px-2.5 pt-1.5 mb-2.5 flex flex-col gap-3.5">
                        <div class="flex items-center gap-2 justify-between">
                            <span class="flex items-center gap-2">
                                <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                                <span class="font-medium text-2sm">
                                    Dark Mode
                                </span>
                            </span>
                            <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" name="check" type="checkbox" value="1"/>
                        </div>
                        <a class="kt-btn kt-btn-outline justify-center w-full" href="/logout">
                            Log out
                        </a>
                    </div>
                </div>
            </div>
            <!-- End of User -->
        </div>
        <!-- End of Topbar -->
    </div>
    <!-- End of Container -->
</header>