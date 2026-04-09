<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Find a Worker')</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for navbar and notification icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Material Icons Round (for admin dashboard) -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    <!-- Custom CSS (UI-UX Pro Max) -->
    <link rel="stylesheet" href="{{ asset('assets/css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/global.css') }}">

    @stack('styles')
</head>

<body>

    @yield('content')
    @php
        $showCustomerChatWidget = (request()->routeIs('home') || request()->is('customer/*'))
            && !request()->routeIs('customer.booking');
    @endphp
    @if($showCustomerChatWidget)
        <x-customer-chat-widget />
    @endif

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="{{ asset('assets/js/api.js') }}"></script>
    <script type="module" src="{{ asset('assets/js/components/Navbar.js') }}"></script>
    @if($showCustomerChatWidget)
        <script type="module" src="{{ asset('assets/js/components/customer-chat-widget.js') }}"></script>
    @endif
    @stack('scripts')
</body>

</html>
