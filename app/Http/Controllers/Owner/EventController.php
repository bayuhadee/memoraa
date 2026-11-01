<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\EventRequest;
use App\Http\Requests\Owner\UpdatePaymentStatusRequest;
use App\Models\Event;
use App\Services\EventService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private readonly EventService $events) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $events = Event::withCount(['guests', 'photos'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('event_code', 'like', "%{$search}%")
                ->orWhere('bride_groom_names', 'like', "%{$search}%")))
            ->orderByDesc('event_date')
            ->paginate(20)
            ->withQueryString();

        return view('owner.events.index', [
            'events' => $events,
            'search' => $search,
            'statuses' => PaymentStatus::options(),
        ]);
    }

    public function create()
    {
        return view('owner.events.form', ['event' => new Event(['payment_status' => PaymentStatus::Baru, 'is_active' => true]), 'statuses' => PaymentStatus::options()]);
    }

    public function store(EventRequest $request)
    {
        $event = $this->events->create($request->eventData());

        return redirect()->route('owner.events.show', $event)
            ->with('success', "Event dibuat. Password host: {$event->password_plain}");
    }

    public function show(Event $event)
    {
        $event->loadCount(['guests', 'photos']);

        return view('owner.events.show', [
            'event' => $event,
            'photos' => $event->photos()->visible()->with('guest:id,name')->latest('id')->paginate(48, ['*'], 'foto'),
            'guests' => $event->guests()->withCount('photos')->latest()->get(),
            'statuses' => PaymentStatus::options(),
        ]);
    }

    public function edit(Event $event)
    {
        return view('owner.events.form', ['event' => $event, 'statuses' => PaymentStatus::options()]);
    }

    public function update(EventRequest $request, Event $event)
    {
        $this->events->update($event, $request->eventData());

        return redirect()->route('owner.events.show', $event)->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy(Event $event)
    {
        $this->events->delete($event);

        return redirect()->route('owner.events.index')->with('success', 'Event beserta seluruh fotonya telah dihapus.');
    }

    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, Event $event)
    {
        $event->update(['payment_status' => $request->validated('payment_status')]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Status pembayaran diperbarui.']);
        }

        return back()->with('success', "Status pembayaran {$event->event_code} diperbarui.");
    }

    public function regeneratePassword(Event $event)
    {
        $password = $this->events->regeneratePassword($event);

        return back()->with('success', "Password baru untuk {$event->event_code}: {$password}");
    }

    public function qrcode(Request $request, Event $event, QrCodeService $qr)
    {
        $png = $qr->png($event->galleryUrl(), $event->bride_groom_names);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline')
                .'; filename="qr-'.$event->event_code.'.png"',
        ]);
    }
}
