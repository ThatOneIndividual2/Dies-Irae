@extends('layouts.inspect')

@section('title', $world->name.' / Dies Irae')

@section('content')
    <h1>{{ $world->name }}</h1>
    <p class="muted">{{ $world->slug }} · {{ $world->current_date?->toDateString() }} · {{ $world->status }}</p>

    <h2>Integrity</h2>
    <p class="{{ $report->passed() ? 'ok' : 'bad' }}">
        {{ $report->passed() ? 'Passed' : 'Failed' }}
        ({{ count($report->errors()) }} errors)
    </p>
    @foreach ($report->issues() as $issue)
        <p class="bad">{{ $issue->code }}: {{ $issue->message }}</p>
    @endforeach

    <h2>Characters</h2>
    <table>
        <thead><tr><th>Key</th><th>Name</th><th>Dynasty</th><th></th></tr></thead>
        <tbody>
        @foreach ($world->characters as $character)
            <tr>
                <td>{{ $character->key }}</td>
                <td>{{ $character->displayName() }}</td>
                <td>{{ $character->dynasty->name ?? 'none' }}</td>
                <td><a href="{{ route('dev.inspect.spiritual.character', $character) }}">spiritual</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Secular titles</h2>
    <table>
        <thead><tr><th>Key</th><th>Rank</th><th>Holder</th></tr></thead>
        <tbody>
        @foreach ($world->titles as $title)
            <tr>
                <td>{{ $title->key }}</td>
                <td>{{ $title->rank }}</td>
                <td>{{ $title->currentOwnership->holder->displayName() ?? 'vacant' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Realms</h2>
    <table>
        <thead><tr><th>Key</th><th>Top liege</th></tr></thead>
        <tbody>
        @foreach ($world->realms as $realm)
            <tr>
                <td>{{ $realm->key }}</td>
                <td>{{ $realm->topLiege->displayName() ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>Holdings</h2>
    <table>
        <thead><tr><th>Territory</th><th>Holding</th><th>Type</th></tr></thead>
        <tbody>
        @foreach ($world->territories as $territory)
            @foreach ($territory->holdings as $holding)
                <tr>
                    <td>{{ $territory->name }}</td>
                    <td>{{ $holding->name }}</td>
                    <td>{{ $holding->holding_type }}</td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>

    @if ($world->apocalypseState)
        <h2>Apocalypse</h2>
        <p>Stage: {{ $world->apocalypseState->stage }}</p>
    @endif

    <p><a href="{{ route('dev.inspect.named', $world) }}">Named infernal entities (true state)</a></p>
@endsection
