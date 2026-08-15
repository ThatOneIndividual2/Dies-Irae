@extends('layouts.play')
@section('title', 'Ruler')
@section('content')
<h1>{{ $play->ruler->displayName() }}</h1>
<div class="grid">
    <div class="card">
        <p>Alive: {{ $play->ruler->is_alive ? 'yes' : 'no' }}</p>
        <p>Born: {{ optional($play->ruler->birth_date)->toDateString() }}</p>
        <p>Dynasty: {{ optional($play->ruler->dynasty)->name }}</p>
        <p>Faith: {{ optional($play->ruler->faith)->name }}</p>
        <p>Residence: {{ optional($play->ruler->residence)->name }}</p>
        <p>Clergy: {{ optional($play->ruler->clergyStatus)->status ?? optional($play->ruler->clergyStatus)->rank_key ?? 'none (lay lord)' }}</p>
        <p>Martial {{ $play->ruler->martial }} · Diplomacy {{ $play->ruler->diplomacy }} · Stewardship {{ $play->ruler->stewardship }} · Learning {{ $play->ruler->learning }}</p>
        <p>Prestige {{ $play->ruler->prestige }} · Personal treasury {{ $play->ruler->treasury }}</p>
    </div>
    <div class="card">
        <h3>Titles held</h3>
        <ul>
            @foreach($titles as $own)
                <li>{{ $own->title->name }} ({{ $own->title->rank }})</li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
