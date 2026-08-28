@extends('layouts.play')
@section('title', 'Ruler dashboard')
@section('content')
<h1>{{ $realmName ?? 'County of Salon' }}</h1>
<p>{{ $play->ruler->displayName() }} · {{ $play->world->name }} · {{ $play->world->current_date->toDateString() }}</p>
<p>{{ $ageLine ?? ('Apocalypse: '.($apocalypse->phase_key ?? $apocalypse->stage ?? 'unknown')) }}
@if($church) · Church standing: {{ $church->standing }}@endif</p>
<form method="post" action="{{ route('time.advance') }}">@csrf<button>Advance one day</button></form>
<div class="grid" style="margin-top:16px;">
    <div class="card"><h3>Ruler</h3><p>{{ $play->ruler->displayName() }}</p><a href="{{ route('character') }}">Inspect character</a></div>
    <div class="card"><h3>Pending events</h3>
        @forelse($pending as $event)
            <p><a href="{{ route('events') }}">{{ $event->title }}</a></p>
        @empty
            <p class="muted">No decision waits today.</p>
        @endforelse
    </div>
    <div class="card"><h3>Treasury</h3><p>{{ $play->ruler->treasury }} coin on the person. Realm is a separate chest.</p></div>
    <div class="card"><h3>Campaign goals</h3>
        @forelse($goals as $goal)
            <p>{{ $goal->displayTitle() }} · {{ $goal->statusLabel() }} · {{ $goal->progressLabel() }}</p>
            @if($goal->displayDescription())
                <p class="muted">{{ $goal->displayDescription() }}</p>
            @endif
        @empty
            <p class="muted">No campaign goals yet.</p>
        @endforelse
    </div>
</div>
@endsection
