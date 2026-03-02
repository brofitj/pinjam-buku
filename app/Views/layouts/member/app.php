<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="system" dir="ltr" lang="en">
    <head>
        <base href="../../">
        <title>
            Metronic - Tailwind CSS
        </title>
        <meta charset="utf-8"/>
        <meta content="follow, index" name="robots"/>
        <link href="https://127.0.0.1:8001/metronic-html-starter-kit/layout-8/index.html" rel="canonical"/>
        <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
        <meta content="" name="description"/>
        <meta content="@keenthemes" name="twitter:site"/>
        <meta content="@keenthemes" name="twitter:creator"/>
        <meta content="summary_large_image" name="twitter:card"/>
        <meta content="Metronic - Tailwind CSS " name="twitter:title"/>
        <meta content="" name="twitter:description"/>
        <meta content="/themes/metronic/dist/assets/media/app/og-image.png" name="twitter:image"/>
        <meta content="https://127.0.0.1:8001/metronic-html-starter-kit/layout-8/index.html" property="og:url"/>
        <meta content="en_US" property="og:locale"/>
        <meta content="website" property="og:type"/>
        <meta content="@keenthemes" property="og:site_name"/>
        <meta content="Metronic - Tailwind CSS " property="og:title"/>
        <meta content="" property="og:description"/>
        <meta content="/themes/metronic/dist/assets/media/app/og-image.png" property="og:image"/>
        <link href="/themes/metronic/dist/assets/media/app/apple-touch-icon.png" rel="apple-touch-icon" sizes="180x180"/>
        <link href="/themes/metronic/dist/assets/media/app/favicon-32x32.png" rel="icon" sizes="32x32" type="image/png"/>
        <link href="/themes/metronic/dist/assets/media/app/favicon-16x16.png" rel="icon" sizes="16x16" type="image/png"/>
        <link href="/themes/metronic/dist/assets/media/app/favicon.ico" rel="shortcut icon"/>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
        <link href="/themes/metronic/dist/assets/vendors/apexcharts/apexcharts.css" rel="stylesheet"/>
        <link href="/themes/metronic/dist/assets/vendors/keenicons/styles.bundle.css" rel="stylesheet"/>
        <link href="/themes/metronic/dist/assets/css/styles.css" rel="stylesheet"/>
    </head>
    <body class="antialiased flex h-full text-base text-foreground bg-background [--header-height:60px] [--sidebar-width:90px] bg-muted">
        <!-- Theme Mode -->
        <script>
            const defaultThemeMode = 'system';
            let themeMode;

            if (document.documentElement) {
                const storedTheme = localStorage.getItem('kt-theme');

                if (storedTheme === 'light' || storedTheme === 'dark') {
                    themeMode = storedTheme;
                } else {
                    if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
                        themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
                    } else {
                        themeMode = defaultThemeMode;
                    }

                    if (themeMode === 'system') {
                        themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches
                            ? 'dark'
                            : 'light';
                    }

                    localStorage.setItem('kt-theme', themeMode);
                }

                document.documentElement.classList.add(themeMode);
            }
        </script>
        <!-- End of Theme Mode -->
        <!-- Page -->
        <!-- Base -->
        <div class="flex grow">
            <!-- Header -->
            <?php require __DIR__ . '/header.php'; ?>
            <!-- End of Header -->
            <!-- Wrapper -->
            <div class="flex flex-col lg:flex-row grow pt-(--header-height) lg:pt-0">
                <!-- Sidebar -->
                <?php require __DIR__ . '/sidebar.php'; ?>
                <!-- End of Sidebar -->
                <!-- Main -->
                <div class="flex flex-col grow rounded-xl bg-background border border-input lg:ms-(--sidebar-width) mt-0 lg:mt-5 m-5">
                    <div class="flex flex-col grow kt-scrollable-y-auto lg:[--scrollbar-width:auto] pt-5" id="scrollable_content">
                        <main class="grow" role="content">
                            <!-- Container -->
                            <div class="kt-container-fixed">
                                <?php echo $content ?? ''; ?>
                            </div>
                            <!-- End of Container -->
                        </main>
                    </div>
                </div>
                <!-- End of Main -->
            </div>
            <!-- End of Wrapper -->
        </div>
        <!-- End of Base -->
        <!-- End of Page -->
        <!-- Scripts -->
        <script src="/themes/metronic/dist/assets/js/core.bundle.js"></script>
        <script src="/themes/metronic/dist/assets/vendors/ktui/ktui.min.js"></script>
        <script src="/themes/metronic/dist/assets/vendors/apexcharts/apexcharts.min.js"></script>
        <script src="/themes/metronic/dist/assets/js/widgets/general.js"></script>
        <!-- End of Scripts -->
    </body>
</html>