@extends('layouts.app')

@section('title', 'Page Title')



@section('content')

    <div class="row" >
        <div class="col col-md-8 col-md-offset-3 ">
            <form action="{{url('/test/apidirect/campaing/add')}}" method="post">
                {{csrf_field()}}
                <div class="row">
                    <div class="col col-md-6"><input type="text" class="form-control" name="campaing_name" placeholder="Campaing name" required></div>
                    <div class="col col-md-6"> <input type="submit" class="btn btn-info" value="отправить"></div>
                </div>

            </form>


        </div>
    </div>

    <h2>Рекламная кампания</h2>
    <table class="table">
        <thead>
        <tr>
            <th>Идентификатор</th>
            <th>Название</th>
            <th>Функционал</th>
        </tr>
        </thead>
        <tbody>

        @foreach ($result as $campaign)
            <tr>
                <td>{{$campaign->getId()}}</td>
                <td><a href={{url('/test/apidirect/groups/list/')}}/{{$campaign->getId()}}>{{$campaign->getName()}}</a></td>
                <td><a href={{url('/test/apidirect/campaing/remove')}}/{{$campaign->getId()}}>удалить</a>,
                    <a href={{url('/test/apidirect/groups/add')}}/{{$campaign->getId()}}>добавить группу</a>

                </td>
            </tr>

        @endforeach
        </tbody>
    </table>




@endsection







