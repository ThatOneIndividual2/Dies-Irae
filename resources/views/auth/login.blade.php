@extends('layouts.play')
@section('title', 'Sign in')
@section('content')
<div class="card" style="max-width:420px;">
    <h1>Sign in</h1>
    <p class="muted">Provence, November 1347. You are the Count of Salon.</p>
    <form method="post" action="{{ route('login') }}">
        @csrf
        <p><label>Email<br><input type="email" name="email" value="{{ old('email', 'lord@diesirae.test') }}" required></label></p>
        <p><label>Password<br><input type="password" name="password" value="password" required></label></p>
        <button type="submit">Enter the county</button>
    </form>
</div>
@endsection
