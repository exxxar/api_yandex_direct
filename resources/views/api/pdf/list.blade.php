@extends('layouts.app')

@section('title', 'Page Title')



@section('content')

    <ul>
        @foreach($result as $r)

            <li>[{{++$index}}] <a href="{{url('/test/apidirect/pdf/gen/')}}/{{$r->ad_group_id}}" target="_blank">{{$r->ad_group_id}}</a> [{{$r['count(ad_group_id)']}}]</li>
       @endforeach
    </ul>
@endsection





