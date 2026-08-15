@extends('layouts.inspect')

@section('title', 'Dies Irae inspect')

@section('content')
    <h1>Worlds</h1>
    <p class="muted">Local/testing only.</p>
    <table>
        <thead>
            <tr><th>World</th><th>Date</th><th>Phase</th><th>Pressure</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($worlds as $world)
            <tr>
                <td>{{ $world->slug }} · {{ $world->name }}</td>
                <td>{{ $world->current_date?->toDateString() }}</td>
                <td>{{ $world->apocalypseState->phase_key ?? '—' }}</td>
                <td>{{ $world->apocalypseState->pressure ?? '—' }}</td>
                <td>
                    <a href="{{ route('dev.inspect.show', $world) }}">inspect</a>
                    ·
                    <a href="{{ route('dev.inspect.apocalypse', $world) }}">apocalypse</a>
                    ·
                    <a href="{{ route('dev.inspect.events', $world) }}">events</a>
                    ·
                    <a href="{{ route('dev.inspect.named', $world) }}">named infernal</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No worlds</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
