@extends('layouts.app')

@section('title', 'Page Title')
@section('style')
    <style>
        textarea {
            padding:10px;
            box-sizing: border-box;
            height:200px;
            width:100%;
            resize: none;
            margin-bottom:10px;
        }
    </style>
@endsection

@section('content')
    <form action="{{url('/test/apidirect/forecast/get')}}" method="post" class="row">
        <div class="col-md-8"><textarea name="words" id="words"></textarea></div>
        <div class="col-md-4">
            <select class="form-control" multiple name="regions[]" id="region">
                @foreach($regions->getGeoRegions() as $r)
                    <option value="{{$r->getGeoRegionId()}}">{{$r->getGeoRegionName()}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12">
            <button class="btn btn-default" type="submit">Создать репорт</button>
        </div>
        
    </form>
    <h3>Cписок сформированных и формируемых отчетов о прогнозируемом количестве показов и кликов, затратах на кампанию.</h3>
    @foreach($result as $rez)
        <li><a href="{{url('/test/apidirect/forecast/get')}}/{{$rez->getForecastID()}}">{{$rez->getForecastID()}}</a>[{{$rez->getStatusForecast()}}]
            <a href="{{url('/test/apidirect/forecast/remove')}}/{{$rez->getForecastID()}}">удалить</a></li>
    @endforeach
@endsection









