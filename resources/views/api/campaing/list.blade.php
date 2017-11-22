@extends('layouts.app')

@section('title', 'Page Title')



@section('content')


    <form action="{{url('/test/apidirect/groups/add')}}" class="row" method="post">
        <div class="col col-md-4">
            <select class="form-control" multiple name="regions[]" id="region">
                @foreach($regions->getGeoRegions() as $r)
                    <option value="{{$r->getGeoRegionId()}}">{{$r->getGeoRegionName()}}</option>
                @endforeach
            </select>
        </div>
        <div class="col col-md-4">
            {{csrf_field()}}
            <input type="hidden" name="campaingid" value="{{$campaingId}}">
            <input class="form-control" name="name" placeholder="Enter group name">
        </div>

        <div class="col col-md-4">
            <button type="submit" class="btn btn-default">Добавить группу</button>
        </div>




    </form>

    <h2>Группы в компании [{{$campaingId}}]</h2>
    <table class="table">
        <thead>
        <tr>
            <th>Идентификатор</th>
            <th>Название</th>
            <th>Регионы</th>
            <th>Функционал</th>
        </tr>
        </thead>
        <tbody>

        @if($result->getAdGroups()!=null)
            @foreach ($result->getAdGroups() as $group)
                <tr>
                    <td>{{$group->getId()}}</td>
                    <td><a href={{url('/test/apidirect/keywords/list/')}}/{{$group->getId()}}>{{$group->getName()}}</a></td>
                    <td>
                        @foreach($group->getRegionIds() as $re)
                            @foreach($regions->getGeoRegions() as $r)
                                @if($r->getGeoRegionId()==$re)
                                    {{$r->getGeoRegionName()}}
                                @endif
                            @endforeach
                            ,
                        @endforeach
                    </td>
                    <td><a href={{url('/test/apidirect/groups/remove')}}/{{$group->getId()}}/{{$campaingId}}>удалить</a>,
                        <a href={{url('/test/apidirect/keywords/add')}}/{{$group->getId()}}>добавить ключевые слова</a>

                    </td>
                </tr>

            @endforeach
        @endif
        </tbody>
    </table>

@endsection





