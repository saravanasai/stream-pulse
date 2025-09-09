<?php

namespace StreamPulse\StreamPulse\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use StreamPulse\StreamPulse\Contracts\StreamUIInterface;

class StreamPulseDashboardController extends Controller
{
    /**
     * The UI interface implementation.
     *
     * @var \StreamPulse\StreamPulse\Contracts\StreamUIInterface
     */
    protected $streamUI;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(StreamUIInterface $streamUI)
    {
        $this->streamUI = $streamUI;
    }

    /**
     * Display the dashboard overview.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $topics = $this->streamUI->listTopics();
        $failedEvents = $this->streamUI->listFailedEvents();

        return view('stream-pulse::dashboard.index', [
            'topics' => $topics,
            'failedEvents' => $failedEvents,
        ]);
    }

    /**
     * Display events for a specific topic.
     *
     * @return \Illuminate\View\View
     */
    public function topic(Request $request, string $topic)
    {
        $limit = config('stream-pulse.ui.page_size', 50);
        $offset = $request->input('offset', 0);

        $events = $this->streamUI->getEventsByTopic($topic, $limit, $offset);

        return view('stream-pulse::dashboard.topic', [
            'topic' => $topic,
            'events' => $events,
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }

    /**
     * Display details for a specific event.
     *
     * @return \Illuminate\View\View
     */
    public function event(string $topic, string $eventId)
    {
        $event = $this->streamUI->getEventDetails($topic, $eventId);

        if (empty($event)) {
            abort(404, 'Event not found');
        }

        return view('stream-pulse::dashboard.event', [
            'topic' => $topic,
            'event' => $event,
        ]);
    }

    /**
     * Display failed events.
     *
     * @return \Illuminate\View\View
     */
    public function failed()
    {
        $failedEvents = $this->streamUI->listFailedEvents();

        return view('stream-pulse::dashboard.failed', [
            'failedEvents' => $failedEvents,
        ]);
    }
}
