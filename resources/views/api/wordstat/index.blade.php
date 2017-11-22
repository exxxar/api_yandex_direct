@extends('layouts.app')

@section('title', 'Page Title')



@section('content')
    <style>
        ul li {
            list-style: none;
            padding:10px;
            box-sizing: border-box;
        }
        ul li p {
            display: inline;
        }
    </style>
    <input type="hidden" id="groupId" value="{{$groupId}}">
    {{csrf_field()}}
    <h2>Отчет по словам</h2>
    <ul>
        @foreach ($result as $w)

            @foreach($w->getSearchedWith() as $sw)
                <li>
                    <p><button class="add_keyword btn btn-info" data-keyword="{{$sw->getPhrase()}}" >+</button></p>
                    <p>{{$sw->getPhrase()}}</p>
                    <p>[Показы:{{$sw->getShows()}}]</p>



                    @foreach ($keywords->getKeywords() as $key)
                        @if($key->getKeyword()==$sw->getPhrase())
                            <br>
                            <p>Ставка на поиске:{{$key->getBid()/1000000}}</p><br>
                            <p>Ставка в сетях:{{$key->getContextBid()/1000000}}</p><br>
                            <p>Продуктивность фразы:{{$key->getProductivity()!=null?$key->getProductivity()->getValue():""}}</p><br>
                            <p>Статистика показов и кликов:{{$key->getStatisticsSearch()->getClicks()}}/{{$key->getStatisticsSearch()->getImpressions()}}</p><br>
                            <p>Статистика показов и кликов(в сетях):{{$key->getStatisticsNetwork()->getClicks()}}/{{$key->getStatisticsSearch()->getImpressions()}}</p>
                            @foreach($bids->getBids() as $bid)
                                @if($key->getId()==$bid->getKeywordId())
                                    <p>Ставка на поиске.:{{$bid->getBid()!=null?$bid->getBid()/1000000:"нет"}}</p><br>
                                    <p>Ставка в сетях:{{$bid->getContextBid()!=null?$bid->getContextBid()/1000000:"нет"}}</p><br>
                                    <p>Массив минимальных ставок для данной фразы за все позиции в спецразмещении и в блоке гарантированных показов</p><br>

                                    <table class="table">
                                        <tbody>
                                        <tr>
                                            @if($bid->getCompetitorsBids()!==null)
                                                @foreach($bid->getCompetitorsBids() as $cb)
                                                    <td> {{$cb/1000000}}</td>
                                                @endforeach
                                            @endif
                                        </tr>
                                        </tbody>
                                    </table>

                                    <p>Минимальные ставки для данной фразы за позиции показа на поиске.</p><br>
                                    <table class="table">
                                        <thead>
                                        <th>Позиция</th>
                                        <th>Цена</th>
                                        </thead>
                                        <tbody>
                                        @if($bid->getSearchPrices()!==null)
                                            @foreach($bid->getSearchPrices() as $sp)
                                                <tr>
                                                    <td>{{$sp->getPosition()}}</td>
                                                    <td>{{$sp->getPrice()/1000000}}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        </tbody>
                                    </table>

                                    <p>Минимальная ставка, установленная для рекламодателя, при которой возможен показ на поиске: {{$bid->getMinSearchPrice()/1000000}}</p><br>
                                    <p>Текущая цена клика на поиске:{{$bid->getCurrentSearchPrice()/1000000}}</p><br>

                                    <p>Результаты торгов по фразе.</p><br>

                                    <table class="table">
                                        <thead>
                                        <th>Позиция показа</th>
                                        <th>Минимальная ставка за указанную позицию.</th>
                                        <th>Списываемая цена для указанной позиции.</th>
                                        </thead>
                                        <tbody>
                                        @if($bid->getAuctionBids()!==null)
                                            @foreach($bid->getAuctionBids() as $ab)
                                                <tr>
                                                    <td>{{$ab->getPosition()}}</td>
                                                    <td>{{$ab->getBid()/1000000}}</td>
                                                    <td>{{$ab->getPrice()/1000000}}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        </tbody>
                                    </table>
                                @endif
                            @endforeach
                        @endif


                    @endforeach

                </li>
            @endforeach


        @endforeach
    </ul>





    <h3>Так же ищутся</h3>
    <table class="table">
        <thead>
        <tr>
            <th>Фраза</th>
            <th>Количество показов</th>

        </tr>
        </thead>
        <tbody>

        @foreach ($result as $w)

            @foreach($w->getSearchedAlso() as $sw)
                <tr>
                    <td>{{$sw->getPhrase()}}</td>
                    <td>{{$sw->getShows()}}</td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>

    <script>
        $(document).ready(function () {
            $(".add_keyword").click(function () {
                var control = $(this);
                var _keyword = $(this).attr("data-keyword");
                var _groupId = $("#groupId").val();
                $.post("{{url('/test/apidirect/keywords/add')}}",
                    {
                        groupId:_groupId,
                        keyword:_keyword,
                        _token:$("[name='_token']").val()
                    },
                function (a,b) {
                    if (b=="success") {
                        control.removeClass("btn-info");
                        control.addClass("btn-success");                    }

                   console.log("status:"+b);
                })
            });
        });
    </script>

@endsection







