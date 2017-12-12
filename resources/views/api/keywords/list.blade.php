@extends('layouts.app')

@section('title', 'Page Title')



@section('content')


    <form action="{{url('/test/apidirect/keywords/add')}}" class="row" method="post">

        <div class="col col-md-4">
            {{csrf_field()}}
            <input type="hidden" name="groupId" value="{{$groupId}}">
            <input class="form-control" name="keyword" placeholder="Enter keyword">
        </div>
        <div class="col col-md-4">
            <div class="form-group">
                <input type="checkbox"  name="autotargeting" id="autotargeting"><label for="autotargeting"> autotargeting</label>

            </div>
        </div>
        <div class="col col-md-4">
            <button type="submit" class="btn btn-default">Добавить ключевое слово</button>
        </div>


    </form>
    <div class="row">
        <div class="col col-md-3">
            <button class="btn btn-info" data-toggle="modal" data-target="#wordstatModal">Статистика по словам (запрос)</button>
        </div>
        <div class="col col-md-2">
            <button class="btn btn-info" data-toggle="modal" data-target="#wordstatListModal">Список запросов</button>
        </div>
        <div class="col col-md-1">
            <a href="{{url('/test/apidirect/bids/get/')}}/{{$groupId}}" class="btn btn-info">Ставки</a>
        </div>
        <div class="col col-md-2">
            <a href="" class="btn btn-info">Сводная: ставки + показы</a>
        </div>
    </div>
    <h2>Ключевые слова в группе [{{$groupId}}]</h2>
    <table class="table">
        <thead>
        <tr>
            <th>Идентификатор</th>
            <th>Название</th>
            <th>Ставка на поиске</th>
            <th>Ставка в сетях</th>
            <th>Продуктивность фразы</th>
            <th>Статистика показов и кликов</th>
            <th>Статистика показов и кликов(в сетях)</th>
            <th>Функционал</th>
        </tr>
        </thead>
        <tbody>


        @if($result->getKeywords()!=null)
            @foreach ($result->getKeywords() as $key)
                <tr>
                    <td>{{$key->getId()}}</td>
                    <td>{{$key->getKeyword()}} <a style="font-size:6pt;" class="add_word" href="#" data-toggle="modal" data-text="{{$key->getKeyword()}}" data-target="#wordstatModal">take info</a></td>
                    <td>{{$key->getBid()/1000000}}</td>
                    <td>{{$key->getContextBid()/1000000}}</td>
                    <td>{{$key->getProductivity()!=null?$key->getProductivity()->getValue():""}}</td>
                    <td>{{$key->getStatisticsSearch()->getClicks()}}/{{$key->getStatisticsSearch()->getImpressions()}}</td>
                    <td>{{$key->getStatisticsNetwork()->getClicks()}}/{{$key->getStatisticsSearch()->getImpressions()}}</td>

                    <td><a href={{url('/test/apidirect/keywords/remove')}}/{{$key->getId()}}/{{$groupId}}>удалить</a></td>
                </tr>

            @endforeach
        @endif
        </tbody>
    </table>

    <!-- Modal -->
    <div id="wordstatModal" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">CreateNewWordstatReport</h4>
                </div>
                <div class="modal-body">
                   <form id="createNewWordstatReport" method="post" action="{{url('/test/apidirect/wordstat/create')}}">
                       <div class="form-group">
                           <label for="region">Выбор региона(несколько)</label>
                           <select class="form-control" multiple name="regions[]" id="region">
                               @isset($regions)
                                   @foreach($regions->getGeoRegions() as $r)
                                       <option value="{{$r->getGeoRegionId()}}">{{$r->getGeoRegionName()}}</option>
                                   @endforeach
                               @endisset
                           </select>
                       </div>

                       <div class="form-group">
                           <label for="keywords_stat_names">Ключевое слово</label>
                           <input type="text" class="form-control" id="keywords_stat_names" name="words">
                       </div>
                       <div class="form-group">
                           {{csrf_field()}}
                           <input type="hidden" name="groupId" value="{{$groupId}}">
                           <button class="btn btn-info" type="submit" id="newwordstatreport">Создать запрос</button>
                       </div>

                   </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>


    <!-- Modal -->
    <div id="wordstatListModal" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">GetWordstatReportList</h4>
                </div>
                <div class="modal-body">
                    @isset($wordstatlist)
                        @foreach($wordstatlist as $d)
                            <table class="table">
                                <tbody>
                                <tr>
                                    <td><a href="{{url('/test/apidirect/wordstat/report')}}/{{$d->getReportID()}}/{{$groupId}}">{{$d->getReportID()}}</a></td>
                                    <td>{{$d->getStatusReport()}}</td>
                                    <td><a href="{{url('/test/apidirect/wordstat/delete')}}/{{$d->getReportID()}}/{{$groupId}}">удалить</a></td>
                                </tr>
                                </tbody>
                            </table>
                        @endforeach
                    @endisset
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script>
        $(document).ready(function () {
            $(".add_word").click(function(){
                var text = $(this).attr("data-text");
                $("#keywords_stat_names").val(text);
            });
        });

    </script>
@endsection





