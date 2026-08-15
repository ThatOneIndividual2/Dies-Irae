@extends('layouts.inspect')

@section('title', 'Events '.$world->name)

@section('content')
    <p><a href="{{ route('dev.inspect.index') }}">Worlds</a></p>
    <h1>{{ $world->name }} events</h1>
    <p class="muted">{{ $world->current_date?->toDateString() }} · {{ $report['phase'] }} · pending {{ $report['pending'] }}</p>
    @if (session('status'))
        <p class="ok">{{ session('status') }}</p>
    @endif

    <form method="post" action="{{ route('dev.inspect.events.pulse', $world) }}">
        @csrf
        <button>Run pulse</button>
    </form>
    <form method="post" action="{{ route('dev.inspect.events.force', $world) }}">
        @csrf
        <select name="definition_key">
            @foreach ($keys as $key)
                <option value="{{ $key }}">{{ $key }}</option>
            @endforeach
        </select>
        <input name="scope_type" value="territory" size="12">
        <input name="scope_id" type="number" value="1" size="6">
        <button>Force trigger</button>
    </form>

    <h2>Eligibility / weights / cooldowns</h2>
    <table>
        <tr><th>Event</th><th>Scope</th><th>Weight</th><th>Reason</th><th>Cooldown</th></tr>
        @foreach ($report['rows'] as $row)
            <tr>
                <td>{{ $row['key'] }}</td>
                <td>{{ $row['scope_type'] }} #{{ $row['scope_id'] }}</td>
                <td>{{ $row['weight'] }}</td>
                <td>{{ $row['reason'] }}</td>
                <td>{{ $row['cooldown_until'] ?? 'open' }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Hooks</h2>
    <table>
        <tr><th>Hook</th><th>Scope</th><th>Intensity</th><th>Expires</th></tr>
        @forelse ($report['hooks'] as $hook)
            <tr>
                <td>{{ $hook->hook_key }}</td>
                <td>{{ $hook->scope_type }} #{{ $hook->scope_id }}</td>
                <td>{{ $hook->intensity }}</td>
                <td>{{ $hook->expires_on?->toDateString() ?? 'none' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">No hooks</td></tr>
        @endforelse
    </table>

    <h2>Occurrences</h2>
    <table>
        <tr><th>Id</th><th>Key</th><th>Status</th><th>Choice</th><th>Vis</th></tr>
        @foreach ($events as $event)
            <tr>
                <td>{{ $event->id }}</td>
                <td>{{ $event->definition_key }} {{ $event->scope_type }}#{{ $event->scope_id }}</td>
                <td>{{ $event->status }}</td>
                <td>{{ $event->chosen_option }}</td>
                <td>{{ $event->visibility }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Chain state</h2>
    <table>
        <tr><th>Id</th><th>Chain</th><th>To</th><th>Due</th><th>Status</th><th>Skip</th></tr>
        @foreach ($chains as $link)
            <tr>
                <td>{{ $link->id }}</td>
                <td>{{ $link->chain_key }}</td>
                <td>{{ $link->to_definition_key ?: 'ops' }}</td>
                <td>{{ $link->due_on?->toDateString() }}</td>
                <td>{{ $link->status }}</td>
                <td>{{ $link->skip_reason }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Cooldowns</h2>
    <table>
        <tr><th>Definition</th><th>Scope</th><th>Available</th></tr>
        @foreach ($cooldowns as $cd)
            <tr>
                <td>{{ $cd->definition_key }}</td>
                <td>{{ $cd->scope_type }} #{{ $cd->scope_id }}</td>
                <td>{{ $cd->available_on?->toDateString() }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Hidden consequences</h2>
    <table>
        <tr><th>When</th><th>Event</th><th>Op</th></tr>
        @foreach ($hidden as $row)
            <tr>
                <td>{{ $row->applied_on?->toDateString() }}</td>
                <td>{{ $row->definition_key }} / {{ $row->choice_key }}</td>
                <td>{{ $row->op }}</td>
            </tr>
        @endforeach
    </table>
@endsection
