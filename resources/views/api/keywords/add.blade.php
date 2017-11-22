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
                <input type="checkbox" name="autotargeting" id="autotargeting"><label for="autotargeting"> autotargeting</label>

            </div>
        </div>
        <div class="col col-md-4">
            <button type="submit" class="btn btn-default">Добавить ключевое слово</button>
        </div>


    </form>


@endsection





