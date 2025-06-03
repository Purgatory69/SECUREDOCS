@extends('layouts.error')

@section('title', 'Too Many Requests')

@section('content')
    <div class="error-title">429</div>
    <div class="error-message">You have made too many failed login attempts.<br>Please wait before trying again.</div>
    <div class="countdown">
        Try again in <span id="countdown-message">60</span> seconds.
    </div>
    <div class="support">If you believe this is an error, please contact support.</div>
    <script>
        let seconds = 60;
        let countdown = document.getElementById('countdown-message');
        let interval = setInterval(function() {
            seconds--;
            countdown.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                location.reload();
            }
        }, 1000);
    </script>
@endsection
