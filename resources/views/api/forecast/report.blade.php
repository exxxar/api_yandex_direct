@extends('layouts.app')

@section('title', 'Page Title')

@section("style")
    <style>
        span {
            font-weight: bold;
            font-size:14pt;
        }

    </style>
@endsection
@section('content')
    @foreach($result->getPhrases() as $rez)
        <p>Ключевая фраза, для которой составлен прогноз. <span> {{$rez->getPhrase()}}</span></p>
        <p>Средневзвешенная цена клика в нижнем блоке на момент составления прогноза. <span> {{$rez->getMin()}}</span></p>
        <p>Средневзвешенная цена клика в нижнем блоке на момент составления прогноза. <span> {{$rez->getMax()}}</span></p>
        <p>Средневзвешенная цена клика в спецразмещении на момент составления прогноза. <span> {{$rez->getPremiumMin()}}</span></p>
        <p>Средневзвешенная цена клика на первом месте в спецразмещении на момент составления прогноза. <span> {{$rez->getPremiumMax()}}</span></p>
        <p>Возможное количество показов объявления по данной фразе за прошедший месяц. <span> {{$rez->getShows()}}</span></p>
        <p>Возможное количество кликов по объявлению в нижнем блоке (кроме первого места) за прошедший месяц. <span> {{$rez->getClicks()}}</span></p>
        <p>Возможное количество кликов по объявлению на первом месте в нижнем блоке, за прошедший месяц. <span> {{$rez->getFirstPlaceClicks()}}</span></p>
        <p>CTR при показе в нижнем блоке, в процентах. Рассчитывается по формуле:
            Clicks/Shows * 100 <span> {{$rez->getCTR()}}</span></p>
        <p>CTR при показе на первом месте в нижнем блоке. Рассчитывается по формуле:
            FirstPlaceClicks/Shows * 100 <span> {{$rez->getFirstPlaceCTR()}}</span></p>

        <p>CTR при показе в спецразмещении. Рассчитывается по формуле:
            PremiumClicks/Shows * 100 <span> {{$rez->getPremiumCTR()}}</span></p>

        <p>Валюта, в которой выражены цены клика и суммарные затраты в отчете. <span> {{$rez->getCurrency()}}</span></p>

        <hr>
    @endforeach
@endsection









