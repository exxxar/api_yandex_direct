@extends('layouts.app')

@section('title', 'Page Title')



@section('content')

    @isset($link)
        <h2><a href="{{$link}}" target="_blank">Get token!</a></h2>
    @endisset


    @isset($token)
        <h2>Ваш тоукен:{{$token}}</h2>
    @endisset

    @isset($error)
        {{$error->getCode()}}
        {{$error->getMessage()}}
    @endisset




@endsection







