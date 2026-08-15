@extends('layouts.play')
@section('title', 'Events')
@section('content')
<h1>Decisions</h1>
@foreach($events as $event)
    <div class="card" style="margin-bottom:12px;">
        <h3>{{ $event->title }}</h3>
        <p class="muted">{{ $event->event_key }} · due {{ $event->due_on }} · {{ $event->status }}</p>
        <p>{{ $event->body }}</p>
        @if($event->status === 'awaiting_decision')
            @foreach($event->options as $key => $label)
                <form class="inline" method="post" action="{{ route('events.resolve', $event) }}">
                    @csrf
                    <input type="hidden" name="option" value="{{ $key }}">
                    <button>{{ $label }}</button>
                </form>
            @endforeach
        @elseif($event->chosen_option)
            <p>Chosen: {{ $event->chosen_option }}</p>
        @endif
    </div>
@endforeach
@endsection
