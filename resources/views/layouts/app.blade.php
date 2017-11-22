<html>
<head>
    <title>App Name - @yield('title')</title>
    <link rel="stylesheet" href="{{asset('/css/app.css')}}">
</head>

<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="{{url('/test/apidirect/')}}">Главная</a>
        </div>

    </div>
</nav>

<div class="container" style="padding: 20px; box-sizing: border-box;">
    @yield('content')
</div>
</body>
<script src="{{asset('/js/app.js')}}"></script>
</html>