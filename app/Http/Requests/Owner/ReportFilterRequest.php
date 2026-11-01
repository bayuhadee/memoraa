<?php

namespace App\Http\Requests\Owner;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return ['from' => 'tanggal mulai', 'to' => 'tanggal sampai'];
    }

    /** @return array{from: ?string, to: ?string, status: ?string} */
    public function filters(): array
    {
        return [
            'from' => $this->validated('from'),
            'to' => $this->validated('to'),
            'status' => $this->validated('status'),
        ];
    }
}
