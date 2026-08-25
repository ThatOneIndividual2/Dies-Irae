@extends('layouts.play')
@section('title', 'Spiritual state')
@section('content')
<h1>Faith and corruption</h1>
<h3>Overlays</h3>
<table>
    <tr><th>Place</th><th>Overlay</th><th>Despair</th></tr>
    @forelse($territories as $t)
        <tr><td><a href="{{ route('settlement', $t) }}">{{ $t->name }}</a></td><td>{{ optional($t->overlay)->state }}</td><td>{{ optional($t->despair)->intensity ?? 0 }}</td></tr>
    @empty
        <tr><td colspan="3" class="muted">No places recorded.</td></tr>
    @endforelse
</table>
<h3>Corruption</h3>
<table>
    <tr><th>Subject</th><th>Intensity</th><th>Source</th></tr>
    @forelse($corruptions as $c)
        <tr><td>{{ $c->subjectDisplayName() }}</td><td>{{ $c->intensity }}</td><td>{{ $c->source }}</td></tr>
    @empty
        <tr><td colspan="3" class="muted">None recorded.</td></tr>
    @endforelse
</table>
<h3>Heresy</h3>
<ul>@forelse($heresy as $h)<li>{{ $h->heresy->name }} in {{ $h->territory->name }} ({{ $h->is_public ? 'public' : 'hidden' }}, {{ $h->intensity }})</li>@empty<li class="muted">None recorded.</li>@endforelse</ul>
<h3>Cults</h3>
<ul>@forelse($cults as $cult)<li>{{ $cult->name }} at {{ $cult->territory->name }} · {{ $cult->revealed ? 'revealed' : 'hidden' }} · strength {{ $cult->strength }}</li>@empty<li class="muted">None.</li>@endforelse</ul>
<h3>Rumors of named pressures</h3>
<p class="muted">True names, ranks, and wounds stay hidden until clergy, books, confessions, relics, visions, or a broken cell teach them.</p>
<ul>
@forelse($rumors as $rumor)
    <li>
        {{ $rumor['shown'] }}
        @if($rumor['true_known']) (named) @endif
        @if($rumor['rank']) · office heard: {{ $rumor['rank'] }} @endif
        @if($rumor['motives']) · aims: {{ $rumor['motives'] }} @endif
        @if($rumor['location']) · place guessed @endif
        @if($rumor['wounds']) · wounds: {{ implode(', ', $rumor['wounds']) }} @endif
        @if($rumor['signs']) · signs: {{ implode('; ', $rumor['signs']) }} @endif
    </li>
@empty
    <li class="muted">No named pressure has a rumor yet.</li>
@endforelse
</ul>
@endsection
