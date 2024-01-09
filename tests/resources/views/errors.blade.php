<!DOCTYPE html>
<html lang="en">

<head>
    <title>{{ $title }} &raquo; {{ config('app.name') }}</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1">

    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon.png') }}" />

    {{-- style --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>

<body>
    <div class="container">
        <div class="block">
            <h1>{{ $code }}</h1>
            <h3>{{ $title }}</h3>
            <p>{{ $message }}</p>
        </div>
    </div>
</body>

</html>