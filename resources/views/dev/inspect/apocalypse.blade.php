<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Apocalypse — {{ $world->name }}</title>
    <style>
        body { font-family: Georgia, serif; background: #140f0c; color: #e8dcc8; margin: 2rem; max-width: 1100px; }
        a { color: #d4a574; }
        h1, h2 { font-weight: normal; }
        table { border-collapse: collapse; margin-bottom: 1.5rem; width: 100%; }
        td, th { padding: 0.3rem 0.6rem; text-align: left; border-bottom: 1px solid #3a2f26; vertical-align: top; }
        .status { color: #c4e0a8; }
        form { display: inline-block; margin-right: 1rem; margin-bottom: 1rem; }
        input, select, button { font: inherit; background: #241c16; color: #e8dcc8; border: 1px solid #6a5340; padding: 0.2rem 0.4rem; }
        .muted { color: #9a8b76; }
        .floor { color: #c9896a; }
    </style>
</head>
<body>
    <p><a href="{{ route('dev.inspect.index') }}">Worlds</a></p>
    <h1>{{ $world->name }}</h1>
    <p>{{ $world->current_date->toDateString() }} — {{ $state->phase_key }} ({{ $state->phase_ordinal }}) — pressure {{ $state->pressure }}</p>
    <p class="muted">{{ $phase['summary'] ?? '' }}</p>
    @if (session('status'))
        <p class="status">{{ session('status') }}</p>
    @endif

    <h2>Meters</h2>
    <table>
        <tr><th>Meter</th><th>Value</th><th>Floor</th><th>Ceiling</th></tr>
        @foreach ($state->meters()->all() as $key => $value)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $value }}</td>
                <td class="floor">{{ $state->meter_floors[$key] ?? 0 }}</td>
                <td>{{ $state->meter_ceilings[$key] ?? 100 }}</td>
            </tr>
        @endforeach
    </table>
    <p class="muted">Broken assumptions: {{ implode(', ', $state->broken_assumptions ?? []) ?: 'none' }}</p>
    <p class="muted">Resistance efficiency this phase: {{ $phase['resistance_efficiency'] ?? 1 }}</p>

    <h2>Debug controls</h2>
    <form method="post" action="{{ route('dev.inspect.apocalypse.signal', $world) }}">
        @csrf
        <label>
            Signal
            <select name="signal_key">
                @foreach ($signalKeys as $key)
                    <option value="{{ $key }}">{{ $key }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Magnitude
            <input type="number" name="magnitude" value="10" min="1" max="100">
        </label>
        <label>
            Territory id
            <input type="number" name="territory_id" placeholder="territory id">
        </label>
        <button type="submit">Record signal</button>
    </form>
    <form method="post" action="{{ route('dev.inspect.apocalypse.tick', $world) }}">
        @csrf
        <label><input type="checkbox" name="force" value="1"> force</label>
        <button type="submit">Tick</button>
    </form>
    <form method="post" action="{{ route('dev.inspect.apocalypse.evaluate', $world) }}">
        @csrf
        <button type="submit">Evaluate phase</button>
    </form>
    <p class="muted">Milestones cannot be deleted. Resistance cannot lower meters below floors or reverse a phase.</p>

    <h2>Milestones</h2>
    <table>
        <tr><th>Key</th><th>Reached</th><th>Phase then</th></tr>
        @forelse ($milestones as $row)
            <tr>
                <td>{{ $row->milestone_key }}</td>
                <td>{{ $row->reached_on->toDateString() }}</td>
                <td>{{ $row->phase_key }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="muted">None</td></tr>
        @endforelse
    </table>

    <h2>Local weather</h2>
    <table>
        <tr><th>Territory</th><th>Corruption</th><th>Despair</th><th>Manifestation</th><th>Sanctity</th><th>Sanctuary</th></tr>
        @forelse ($weather as $row)
            <tr>
                <td>{{ $row->territory_id }}</td>
                <td>{{ $row->local_corruption }}</td>
                <td>{{ $row->local_despair }}</td>
                <td>{{ $row->local_manifestation }}</td>
                <td>{{ $row->local_sanctity }}</td>
                <td>{{ $row->is_sanctuary ? 'yes' : 'no' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">None</td></tr>
        @endforelse
    </table>

    <h2>Chronicle</h2>
    <table>
        @forelse ($chronicle as $entry)
            <tr>
                <td>{{ $entry->world_date->toDateString() }}</td>
                <td>{{ $entry->entry_type }}</td>
                <td>{{ $entry->title }}</td>
                <td class="muted">{{ $entry->body }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">None</td></tr>
        @endforelse
    </table>

    <h2>Recent signals</h2>
    <table>
        <tr><th>Date</th><th>Key</th><th>Pol</th><th>Mag</th><th>Territory</th></tr>
        @forelse ($signals as $row)
            <tr>
                <td>{{ $row->world_date->toDateString() }}</td>
                <td>{{ $row->signal_key }}</td>
                <td>{{ $row->polarity }}</td>
                <td>{{ $row->magnitude }}</td>
                <td>{{ $row->territory_id ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">None</td></tr>
        @endforelse
    </table>
</body>
</html>
