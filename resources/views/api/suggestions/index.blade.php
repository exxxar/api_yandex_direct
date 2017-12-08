@extends('layouts.app')

@section('title', 'Page Title')

@section("style")
<style>
    .green-border {
        border:1px darkgreen solid;
    }
    textarea {
        width:100%;
        resize: none;
        padding:10px;
        box-sizing: border-box;
        min-height:200px;
    }
    textarea[disbaled] {
        background: lightgrey;
        cursor: not-allowed;
    }
</style>
@endsection

@section('content')
    <form class="row">
        <div class="col-md-6">
            <textarea name="words" id="words" placeholder="Ключевые слова"></textarea>
            <a href="#" id="clearWords">Очистить</a>
            <a href="#" id="getForecast">Запросить прогноз</a>
        </div>
        <div class="col-md-6">
            <textarea name="suggestion" id="suggestion" placeholder="Подсказки" disabled="true"></textarea>
            <a href="#" id="takeAllWords">Выбрать все слова</a>
        </div>
        <div class="col-md-12">
            <button class="btn btn-default" type="button" id="getsuggestions">Получить подсказки</button>
        </div>
    </form>
@endsection

@section('script')
    <script>
        $(document).ready(function () {

            $("#getForecast").click(function () {
                var keywords = $("#words").val();
                $.post("{{url('/test/apidirect/forecast/get')}}",
                    {
                        words:keywords
                    },
                    function (a,b) {
                        console.log(a);

                    }
                );
            });

            $("textarea[name='words']").keydown(function () {
                $(this).removeClass("green-border");
                $("#getsuggestions").addClass("btn-default").removeClass("btn-success");
            });
            $("#clearWords").click(function () {
                $("#words").val("");
            });
            $("#takeAllWords").click(function () {
                var words = $("#words").val();
                words = words +","+ $("#suggestion").val();
                $("#words").val(words);
                $("#suggestion").val("");
            });
            $("#getsuggestions").click(function () {
                var keyword = $("#words").val();
                $.post("{{url("/test/apidirect/suggestions/get")}}",{
                    words:keyword
                },function (a,b) {
                    var result = "";
                    for(var i=0;i<a.length;i++){
                        result = result + a[i]+",";
                    }
                    $("textarea[name='suggestion']").val(result);

                    $("#getsuggestions").removeClass("btn-default").addClass("btn-success");
                    $("textarea[name='words']").addClass("green-border");

                });
            });
        });
    </script>
@endsection







