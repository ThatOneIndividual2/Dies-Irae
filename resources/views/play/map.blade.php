@extends('layouts.play')
@section('title', 'Map')
@section('content')
@php
    $modeLabel = match ($mode) {
        'plague' => 'Plague',
        'faith' => 'Faith',
        'corruption' => 'Corruption',
        default => 'Political',
    };
    $mapTitle = $modeLabel.' map of settlements';
@endphp
<h1>Map</h1>
<p>
    Mode:
    <a href="{{ route('map', ['mode'=>'political']) }}"@if($mode === 'political') aria-current="page"@endif>political</a> ·
    <a href="{{ route('map', ['mode'=>'plague']) }}"@if($mode === 'plague') aria-current="page"@endif>plague</a> ·
    <a href="{{ route('map', ['mode'=>'faith']) }}"@if($mode === 'faith') aria-current="page"@endif>faith</a> ·
    <a href="{{ route('map', ['mode'=>'corruption']) }}"@if($mode === 'corruption') aria-current="page"@endif>corruption</a>
</p>
<svg class="map" viewBox="0 0 720 400">
<title>{{ $mapTitle }}</title>
@foreach($territories as $t)
    @php
        $box = $t->map_box ?? ['x'=>10,'y'=>10,'w'=>80,'h'=>40];
        $fill = '#5a4638';
        if ($mode === 'plague' && $t->plagueState) $fill = '#7a1f1f';
        if ($mode === 'faith') $fill = '#35506a';
        if ($mode === 'corruption' && optional($t->overlay)->state !== 'ordinary') $fill = '#4b2a6a';
        if ($mode === 'political' && (int) $t->owner_character_id === (int) $play->ruler->id) $fill = '#6a4a22';
        if ($mode === 'political' && $t->key === 'salon') $fill = '#6a4a22';
        if ($mode === 'political' && $t->key === 'miramas') $fill = '#5a2222';
    @endphp
    <a href="{{ route('settlement', $t) }}">
        <rect x="{{ $box['x'] }}" y="{{ $box['y'] }}" width="{{ $box['w'] }}" height="{{ $box['h'] }}" fill="{{ $fill }}" stroke="#e2c48a"/>
        <text x="{{ $box['x']+6 }}" y="{{ $box['y']+20 }}" fill="#f3e6c8" font-size="11">{{ $t->name }}</text>
    </a>
@endforeach
</svg>
@if($mode === 'political')
<p class="muted">Legend: your lands and Salon in darker brown; Miramas in deep red; other settlements in base brown.</p>
@elseif($mode === 'plague')
<p class="muted">Legend: plague-touched settlements in deep red; clear settlements in base brown.</p>
@elseif($mode === 'faith')
<p class="muted">Legend: settlements shown in faith blue.</p>
@elseif($mode === 'corruption')
<p class="muted">Legend: corrupted or overlaid settlements in purple; ordinary settlements in base brown.</p>
@endif
<p class="muted">Select a settlement. Your lands are marked on the political layer.</p>
@endsection
