@extends('layouts.app')

@section('title', 'Page Title')



@section('content')
    <form action="{{url('/test/apidirect/campaing/add')}}" method="post">
        <input type="text" name="campaing_name" placeholder="Campaing name" required>
        {{csrf_field()}}
        <input type="submit" value="отправить">
    </form>


@endsection





