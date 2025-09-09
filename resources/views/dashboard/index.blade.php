@extends('stream-pulse::layouts.app')

@section('content')
    <div class="container">
        <h1>StreamPulse Dashboard</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Topics</div>
                    <div class="card-body">
                        @if (count($topics) > 0)
                            <ul class="list-group">
                                @foreach ($topics as $topic)
                                    <li class="list-group-item">
                                        <a href="{{ route('streampulse.topic', $topic) }}">
                                            {{ $topic }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>No topics available.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Failed Events
                        <a href="{{ route('streampulse.failed') }}" class="float-right">View All</a>
                    </div>
                    <div class="card-body">
                        @if (count($failedEvents) > 0)
                            <ul class="list-group">
                                @foreach (array_slice($failedEvents, 0, 5) as $event)
                                    <li class="list-group-item">
                                        <a href="{{ route('streampulse.event', [$event['topic'], $event['event_id']]) }}">
                                            {{ $event['topic'] }} - {{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>No failed events.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
