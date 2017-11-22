@extends('layouts.app')

@section('title', 'Page Title')



@section('content')


        @foreach($result->getBids() as $bid)
            <h2>Ключевое слово:{{$bid->getKeywordId()}}</h2>
            <p>Ставка на поиске.:{{$bid->getBid()!=null?$bid->getBid()/1000000:"нет"}}</p>
            <p>Ставка в сетях:{{$bid->getContextBid()!=null?$bid->getContextBid()/1000000:"нет"}}</p>
            <p>Массив минимальных ставок для данной фразы за все позиции в спецразмещении и в блоке гарантированных показов</p>
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

            <p>Минимальные ставки для данной фразы за позиции показа на поиске.</p>
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


            {{--@foreach($bid->getContextCoverage() as $cc)
               @foreach($cc->getItems() as $item)
                    {{$item->getProbability()}}
                    {{$item->getPrice()/1000000}}
               @endforeach
            @endforeach--}}
            <p>Минимальная ставка, установленная для рекламодателя, при которой возможен показ на поиске: {{$bid->getMinSearchPrice()/1000000}}</p>
            <p>Текущая цена клика на поиске:{{$bid->getCurrentSearchPrice()/1000000}}</p>



            <p>Результаты торгов по фразе.</p>
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

            <hr>
        @endforeach
        <p>Порядковый номер последнего возвращенного объекта</p>
        {{$result->getLimitedBy()!=null?$bid->getLimitedBy()/1000000:"нет"}}

{{--  {{var_dump($result)}}--}}


@endsection





