<html>
<head>
    <title>App Name - @yield('title')</title>
    <link rel="stylesheet" href="{{asset('/css/app.css')}}">

    @yield("style")
</head>

<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <a class="navbar-brand" href="{{url('/test/apidirect/')}}">Главная</a>
        </div>

        <ul class="nav navbar-nav ">
            <li><a href="{{url('/test/apidirect/pdf/list')}}" target="_blank">Получить PDF-отчет</a></li>
            <li><a href="{{url('/')}}" target="_blank">Страница поиска</a></li>
            <li><a href="{{url('/test/apidirect/suggestions')}}" target="_blank">Подсказки по словам</a></li>
            <li><a href="{{url('/test/apidirect/forecast')}}" target="_blank">Прогноз показов</a></li>
        </ul>


        <ul class="nav navbar-nav pull-right">
            <li><a href="{{url('/test/apidirect/code')}}" target="_blank">Получить КОД</a></li>
        </ul>
    </div>
</nav>

<div class="container" style="padding: 20px; box-sizing: border-box;">
    @yield('content')
</div>
</body>
<script src="{{asset('/js/app.js')}}"></script>

    @yield("script")
</html>