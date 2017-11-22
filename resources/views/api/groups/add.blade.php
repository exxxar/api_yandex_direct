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


@endsection





