@extends('layouts.app')

@section('title', 'Page Title')



@section('content')


    <table>
        <thead>
        <th>Ключевой слово</th>
        <th>Премиум клики</th>
        <th>Бюджет</th>
        </thead>
        <tbody>
        @foreach($result as $r)
            <tr>
                <td>{{$r->keyword}}</td>
                <td>{{$r->premium_clicks}}</td>
                <td>{{$r->budget}}</td>
            </tr>
        @endforeach

        </tbody>
    </table>

@endsection