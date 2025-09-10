@extends('stream-pulse::layouts.app')

@section('content')
    <div class="container">
        <h1>Failed Events</h1>

        <div class="card">
            <div class="card-header">All Failed Events</div>
            <div class="card-body">
                @if (count($failedEvents) > 0)
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Topic</th>
                                <th>Event ID</th>
                                <th>Timestamp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($failedEvents as $event)
                                <tr>
                                    <td>{{ $event['topic'] }}</td>
                                    <td>{{ $event['event_id'] }}</td>
                                    <td>{{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}</td>
                                    <td>
                                        <a href="{{ route('stream-pulse.event', [$event['topic'], $event['event_id']]) }}"
                                            class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No failed events found.</p>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('stream-pulse.dashboard') }}" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>
    </div>
@endsection
