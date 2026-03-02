<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="system" dir="ltr" lang="en">
    <head>
        <base href="../../../../../">
        <title>
            Metronic - Tailwind CSS Sign In
        </title>
        <meta charset="utf-8"/>
        <meta content="follow, index" name="robots"/>
        <link href="https://127.0.0.1:8001/metronic-tailwind-html/demo1/authentication/branded/sign-in/index.html" rel="canonical"/>
        <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
        <meta content="Sign in page, using Tailwind CSS" name="description"/>
        <meta content="@keenthemes" name="twitter:site"/>
        <meta content="@keenthemes" name="twitter:creator"/>
        <meta content="summary_large_image" name="twitter:card"/>
        <meta content="Metronic - Tailwind CSS Sign In" name="twitter:title"/>
        <meta content="Sign in page, using Tailwind CSS" name="twitter:description"/>
        <meta content="/themes/metronic/dist/assets/media/app/og-image.png" name="twitter:image"/>
        <meta content="https://127.0.0.1:8001/metronic-tailwind-html/demo1/authentication/branded/sign-in/index.html" property="og:url"/>
        <meta content="en_US" property="og:locale"/>
        <meta content="website" property="og:type"/>
        <meta content="@keenthemes" property="og:site_name"/>
        <meta content="Metronic - Tailwind CSS Sign In" property="og:title"/>
        <meta content="Sign in page, using Tailwind CSS" property="og:description"/>
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
    <body class="antialiased flex h-full text-base text-foreground bg-background">
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
        <style>
            .branded-bg {
                background-image: url('/themes/metronic/dist/assets/media/images/2600x1600/1.png');
            }
            .dark .branded-bg {
                background-image: url('/themes/metronic/dist/assets/media/images/2600x1600/1-dark.png');
            }
        </style>
        <div class="grid lg:grid-cols-2 grow">
            <div class="flex justify-center items-center p-8 lg:p-10 order-2 lg:order-1">
                <div class="kt-card max-w-[370px] w-full">
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
            <div class="lg:rounded-xl lg:border lg:border-border lg:m-5 order-1 lg:order-2 bg-top xxl:bg-center xl:bg-cover bg-no-repeat branded-bg"></div>
        </div>
        <!-- End of Page -->
        <!-- Scripts -->
        <script src="/themes/metronic/dist/assets/js/core.bundle.js"></script>
        <script src="/themes/metronic/dist/assets/vendors/ktui/ktui.min.js"></script>
        <script src="/themes/metronic/dist/assets/vendors/apexcharts/apexcharts.min.js"></script>
        <!-- End of Scripts -->
    </body>
</html>