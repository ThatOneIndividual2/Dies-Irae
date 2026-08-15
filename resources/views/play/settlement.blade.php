@extends('layouts.play')
@section('title', $territory->name)
@section('content')
<h1>{{ $territory->name }}</h1>
<p>Population {{ $territory->population }} · Levy {{ $territory->levy_available }} · Food {{ $territory->food_stores }}</p>
<p>Plague: {{ optional($territory->plagueState)->intensity ? 'intensity '. $territory->plagueState->intensity : 'none' }}</p>
<p>Overlay: {{ optional($territory->overlay)->state ?? 'ordinary' }}</p>
<p>Despair: {{ optional($territory->despair)->intensity ?? 0 }}</p>
<p>Corruption: {{ optional($corruption)->intensity ?? 0 }} ({{ optional($corruption)->source }})</p>
<p>Cult: {{ optional($territory->cult)->revealed ? $territory->cult->name : (optional($territory->cult)->name ? 'rumored' : 'none') }}</p>
@if($territory->monastery)<p>Monastery: <a href="{{ route('church') }}">{{ $territory->monastery->name }}</a></p>@endif
<h3>Holdings</h3>
<ul>@foreach($territory->holdings as $h)<li>{{ $h->name }} ({{ $h->holding_type }}){{ $h->is_seat ? ' · seat' : '' }}</li>@endforeach</ul>
<h3>Armies here</h3>
<ul>@foreach($territory->armies->where('is_active', true) as $a)<li>{{ $a->name }} ({{ $a->kind }}, {{ $a->strength }})</li>@endforeach</ul>
<p><a href="{{ route('map') }}">Back to map</a></p>
@endsection
