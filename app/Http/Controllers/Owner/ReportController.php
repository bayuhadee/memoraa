<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ReportFilterRequest;
use App\Services\ReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(ReportFilterRequest $request)
    {
        return view('owner.reports.index', $this->data($request) + ['statuses' => PaymentStatus::options()]);
    }

    public function print(ReportFilterRequest $request)
    {
        return view('owner.reports.print', $this->data($request));
    }

    /** CSV dengan BOM UTF-8 dan pemisah ";" agar langsung rapi dibuka di Excel (locale Indonesia). */
    public function export(ReportFilterRequest $request): StreamedResponse
    {
        $data = $this->data($request);
        $filename = 'laporan-event-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID Event', 'Nama Mempelai', 'Tanggal Acara', 'Alamat', 'Jumlah Tamu', 'Jumlah Foto', 'Status Pembayaran'], ';');

            foreach ($data['events'] as $event) {
                fputcsv($out, [
                    $event->event_code,
                    $this->safeCell($event->bride_groom_names),
                    $event->event_date->format('d/m/Y'),
                    $this->safeCell($event->address),
                    $event->guests_count,
                    $event->photos_count,
                    $event->payment_status->label(),
                ], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['TOTAL', $data['summary']['events'].' event', '', '', $data['summary']['guests'], $data['summary']['photos'], ''], ';');
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function data(ReportFilterRequest $request): array
    {
        $filters = $request->filters();
        $events = $this->reports->query($filters)->get();

        return [
            'filters' => $filters,
            'events' => $events,
            'summary' => $this->reports->summary($events),
        ];
    }

    /** Cegah CSV/formula injection di Excel. */
    private function safeCell(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
