<div class="fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 bg-muted [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]" data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start flex" id="sidebar">
    <div class="hidden lg:flex items-center justify-center shrink-0 pt-8 pb-3.5" id="sidebar_header">
        <a href="#">
            <img class="dark:hidden min-h-[42px]" src="/themes/metronic/dist/assets/media/app/mini-logo-square-gray.svg"/>
            <img class="hidden dark:block min-h-[42px]" src="/themes/metronic/dist/assets/media/app/mini-logo-square-gray-dark.svg"/>
        </a>
    </div>
    <div class="kt-scrollable-y-hover grow gap-2.5 shrink-0 flex items-center pt-5 lg:pt-0 ps-3 pe-3 lg:pe-0 flex-col" data-kt-scrollable="true" data-kt-scrollable-dependencies="#sidebar_header,#sidebar_footer" data-kt-scrollable-height="auto" data-kt-scrollable-offset="80px" data-kt-scrollable-wrappers="#sidebar_menu_wrapper" id="sidebar_menu_wrapper">
        <!-- Sidebar Menu -->
        <div class="kt-menu flex flex-col gap-2.5 grow" data-kt-menu="true" id="sidebar_menu">
            <div class="kt-menu-item">
                <a class="kt-menu-link rounded-[9px] border border-transparent kt-menu-item-active:border-border kt-menu-item-active:bg-background kt-menu-link-hover:bg-background kt-menu-link-hover:border-border w-[62px] h-[60px] flex flex-col justify-center items-center gap-1 p-2" href="#">
                    <span class="kt-menu-icon kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground">
                        <i class="ki-filled ki-chart-line-star text-xl"></i>
                    </span>
                    <span class="kt-menu-title text-xs kt-menu-item-here:text-primary kt-menu-item-active:text-primary kt-menu-link-hover:text-primary text-secondary-foreground font-medium">
                        Boards
                    </span>
                </a>
            </div>
        </div>
        <!-- End of Sidebar Menu -->
    </div>
    <div class="flex flex-col gap-5 items-center shrink-0 pb-4" id="sidebar_footer">
        <!-- User -->
        <div data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-start" data-kt-dropdown-placement-rtl="bottom-end" data-kt-dropdown-trigger="click">
            <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                <img alt="" class="size-9 rounded-lg shrink-0" src="/themes/metronic/dist/assets/media/avatars/300-2.png"/>
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
                        Anggota
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
                </ul>
                <div class="kt-dropdown-menu-separator"></div>
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
</div>