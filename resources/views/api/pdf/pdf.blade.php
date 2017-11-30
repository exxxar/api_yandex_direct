<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        span {
            font-weight: bold;
            font-style: italic;
        }
    </style>
</head>
<body>



@foreach($keywords as $kw)


    <p>№ <span>{{$kw->id}}</span></p>
    <p>Ключевая фраза <span>{{$kw->keyword}}</span>  </p>
    <p>Количество показов в месяц <span>{{$kw->impressions_per_month}}</span></p>
    @isset($kw->bid)
    <p>Ставка на поиске <span>{{$kw->bid}}</span></p>
    @endisset

    @isset($kw->search_prices_pf)
    <p>минимальная ставка за 1-ю позицию в спецразмещении <span>{{$kw->search_prices_pf}}</span></p>
    @endisset

    @isset($kw->search_prices_pb)
    <p>минимальная ставка за 4-ю позицию в спецразмещении (вход в спецразмещение) <span>{{$kw->search_prices_pb}}</span></p>
    @endisset

    @isset($kw->search_prices_ff)
    <p>минимальная ставка за 1-ю позицию в гарантии <span>{{$kw->search_prices_ff}}</span></p>
    @endisset

    @isset($kw->search_prices_fb)
    <p>минимальная ставка за 4-ю позицию в гарантии (вход в блок гарантированных показов) <span>{{$kw->search_prices_fb}}</span></p>
    @endisset

    @isset($kw->min_search_price)
    <p>Минимальная ставка, установленная для рекламодателя, при которой возможен показ на поиске <span>{{$kw->min_search_price}}</span></p>
    @endisset

    @isset($kw->current_search_price)
    <p>Текущая цена клика на поиске <span>{{$kw->current_search_price}}</span></p>
    @endisset
    <hr>

    {{--TODO: сделать вывод из всех таблиц, а не только из одной, связи в модели есть--}}


              {{--     @if (!empty($kw->competitorsbids))
                        {{var_dump(($kw->competitorsbids->first())[0])}}
                   @endif--}}



@endforeach

</body>
</html>