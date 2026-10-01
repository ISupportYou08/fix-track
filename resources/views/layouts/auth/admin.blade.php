<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? __('Administrator sign in')])
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            .admin-login-background {
                background-color: #dcbbf9;
                background-image:
                    radial-gradient(circle at 15% 85%, #ff8396 0%, transparent 60%),
                    radial-gradient(circle at 85% 15%, #c881ff 0%, transparent 55%),
                    radial-gradient(circle at 85% 85%, #7e98ff 0%, transparent 60%),
                    radial-gradient(circle at 15% 15%, #f1f8ff 0%, transparent 55%);
                background-attachment: fixed;
                font-family: Inter, sans-serif;
            }

            .admin-login-input:-webkit-autofill,
            .admin-login-input:-webkit-autofill:hover,
            .admin-login-input:-webkit-autofill:focus,
            .admin-login-input:autofill {
                -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
                box-shadow: 0 0 0 1000px #fff inset !important;
                -webkit-text-fill-color: #0f172a !important;
                caret-color: #0f172a;
            }
        </style>
    </head>
    <body class="admin-login-background min-h-screen overflow-x-hidden antialiased selection:bg-slate-800 selection:text-white">
        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
