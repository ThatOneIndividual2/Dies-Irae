@extends('layouts.play')
@section('title', 'Realm')
@section('content')
<h1>{{ $realm->name ?? 'No realm cache' }}</h1>
@if($realm)
<p>Top liege: {{ optional($realm->topLiege)->displayName() }}</p>
<p>Primary title: {{ optional($realm->primaryTitle)->name }}</p>
<p>Realm treasury: {{ $realm->treasury }}</p>
@endif
<h3>Vassals</h3>
<table>
    <tr><th>Vassal</th><th>Tax</th><th>Levy</th></tr>
    @foreach($vassals as $link)
        <tr>
            <td>{{ $link->vassal->displayName() }}</td>
            <td>{{ optional($link->contract)->tax_rate }}%</td>
            <td>{{ optional($link->contract)->levy_rate }}%</td>
        </tr>
    @endforeach
</table>
@endsection
