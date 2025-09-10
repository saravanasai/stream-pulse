@extends('stream-pulse::layouts.app')

@section('content')
    <div class="container">
        <h1>Topic: {{ $topic }}</h1>

        <div class="card">
            <div class="card-header">Events</div>
            <div class="card-body">
                @if (count($events) > 0)
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event ID</th>
                                <th>Timestamp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr>
                                    <td>{{ $event['event_id'] }}</td>
                                    <td>{{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}</td>
                                    <td>
                                        <a href="{{ route('stream-pulse.event', [$topic, $event['event_id']]) }}"
                                            class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="pagination">
                        @if ($offset > 0)
                            <a href="{{ route('stream-pulse.topic', [$topic, 'offset' => max(0, $offset - $limit)]) }}"
                                class="btn btn-primary">
                                Previous
                            </a>
                        @endif

                        @if (count($events) >= $limit)
                            <a href="{{ route('stream-pulse.topic', [$topic, 'offset' => $offset + $limit]) }}"
                                class="btn btn-primary ml-2">
                                Next
                            </a>
                        @endif
                    </div>
                @else
                    <p>No events available for this topic.</p>
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
