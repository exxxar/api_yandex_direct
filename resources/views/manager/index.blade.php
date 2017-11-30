@extends('layouts.app')

@section('title', 'Page Title')

@section('style')
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        .ui-autocomplete {
            max-height: 100px;
            overflow-y: auto;
            /* prevent horizontal scrollbar */
            overflow-x: hidden;
        }
        /* IE 6 doesn't support max-height
         * we use height instead, but this forces the menu to always be this tall
         */
        * html .ui-autocomplete {
            height: 100px;
        }

        span[id] {
            font-weight: bold;
            font-size:14pt;
        }
    </style>
@endsection

@section('content')

    <div class="row" >
        <form id="search-form">
            <div class="col col-md-3">
                <input type="search" id="search-form-field" name="search" class="form-control">
            </div>
            <div class="col col-md-1">
                <input type="button" class="btn btn-primary" value="Искать">
            </div>
        </form>

        <div id="result" class="col col-md-10  offset-md-1" style="display:none">

            <p>№ <span id="kw_id"></span></p>
            <p>Ключевая фраза <span id="kw_keyword"></span>  </p>
            <p>Количество показов в месяц <span id="kw_impressions_per_month"></span></p>
            <p>Ставка на поиске <span id="kw_bid"></span></p>
            <p>минимальная ставка за 1-ю позицию в спецразмещении <span id="kw_search_prices_pf"></span></p>
            <p>минимальная ставка за 4-ю позицию в спецразмещении (вход в спецразмещение) <span id="kw_search_prices_pb"></span></p>
            <p>минимальная ставка за 1-ю позицию в гарантии <span id="kw_search_prices_ff"></span></p>
            <p>минимальная ставка за 4-ю позицию в гарантии (вход в блок гарантированных показов) <span id="kw_search_prices_fb"></span></p>
            <p>Минимальная ставка, установленная для рекламодателя, при которой возможен показ на поиске <span id="kw_min_search_price"></span></p>
            <p>Текущая цена клика на поиске <span id="kw_current_search_price"></span></p>
            <hr>
        </div>
    </div>

@endsection

@section('script')


    {{----}}
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script>
        $(document).ready(function () {
            $("input[type=search]").keyup(function () {
                $("#result").css({"display":"none"});
                $("[id~='kw_']").html("");

            });
            $("input[type=button]").click(function(){
                $.post("{{url('/getdata')}}",{term:$("#search-form-field").val()},function(a,b){

                    $("#kw_id").html(a.id);
                    $("#kw_keyword").html(a.keyword);
                    $("#kw_impressions_per_month").html(a.impressions_per_month);
                    $("#kw_search_prices_pf").html(a.search_prices_pf);
                    $("#kw_search_prices_pb").html(a.search_prices_pb);
                    $("#kw_search_prices_ff").html(a.search_prices_ff);
                    $("#kw_search_prices_fb").html(a.search_prices_fb);
                    $("#kw_min_search_price").html(a.min_search_price);
                    $("#kw_current_search_price").html(a.current_search_price);
                    $("#result").css({"display":"block"});
                });

            });
            $( "#search-form-field" ).autocomplete({
                source: "{{ url('/autocomplete') }}",
                minLength: 2
            });
        });
    </script>
@endsection







