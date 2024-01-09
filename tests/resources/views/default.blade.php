<!DOCTYPE html>
<html lang="en">

<head>
    <title>Horizom Framework</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1">
    
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    @yield('style')
</head>

<body>
    <div class="container">
        @yield('content')
    </div>

    @yield('script')
</body>

</html>