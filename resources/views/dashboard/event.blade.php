@extends('stream-pulse::layouts.app')

@section('content')
    <div class="container">
        <h1>Event Details</h1>

        <div class="card">
            <div class="card-header">
                {{ $topic }} - {{ $event['event_id'] }}
                @if ($event['is_failed'])
                    <span class="badge badge-danger">Failed</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Event ID:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $event['event_id'] }}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <strong>Topic:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $event['topic'] }}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <strong>Timestamp:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <strong>Payload:</strong>
                        <pre class="mt-2 bg-light p-3 rounded">{{ json_encode($event['payload'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('streampulse.topic', $topic) }}" class="btn btn-secondary">
                Back to Topic
            </a>
        </div>
    </div>
@endsection
