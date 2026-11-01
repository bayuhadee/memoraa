<?php

namespace App\Http\Requests\Owner;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Tambah & ubah event. */
class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route dilindungi auth:owner
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('event_code')) {
            $this->merge(['event_code' => strtoupper(trim($this->string('event_code')))]);
        }
    }

    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'event_code' => [
                $event ? 'required' : 'nullable',
                'string',
                'min:3',
                'max:40',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('events', 'event_code')->ignore($event?->id),
            ],
            'bride_groom_names' => ['required', 'string', 'max:150'],
            'event_date' => ['required', 'date'],
            'address' => ['required', 'string', 'max:1000'],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['event_code.regex' => 'ID event hanya boleh berisi huruf, angka, dan tanda strip (-).'];
    }

    public function attributes(): array
    {
        return [
            'event_code' => 'ID event',
            'bride_groom_names' => 'nama mempelai',
            'event_date' => 'tanggal acara',
            'address' => 'alamat',
            'payment_status' => 'status pembayaran',
        ];
    }

    public function eventData(): array
    {
        return [
            ...$this->safe()->only(['event_code', 'bride_groom_names', 'event_date', 'address', 'payment_status']),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
