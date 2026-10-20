<?php

namespace App\Http\Controllers\Payments;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Traveller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Travellers submit passport / insurance details for their booking (link in the confirmation
 * e-mail). Sensitive values are encrypted by the Traveller model casts and never echoed back.
 */
class TravellerController extends Controller
{
    /** Fields that are stored encrypted; blank input keeps the value already on file. */
    private const SENSITIVE = ['date_of_birth', 'passport_number', 'passport_expiry', 'insurance_provider', 'insurance_policy', 'medical_notes'];

    public function edit(string $uuid): View
    {
        $booking = $this->find($uuid);

        return view('bookings.travellers', ['booking' => $booking, 'travellers' => $this->slots($booking)]);
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $booking = $this->find($uuid);
        $max = max(1, (int) $booking->pax, $booking->travellers()->count());

        // Ignore completely empty traveller slots (details can be added later).
        $request->merge(['travellers' => array_filter((array) $request->input('travellers', []),
            fn ($t) => is_array($t) && collect($t)->filter(fn ($v) => filled($v))->isNotEmpty())]);

        $data = $request->validate([
            'travellers' => ['required', 'array', 'min:1', 'max:'.$max],
            'travellers.*.first_name' => ['required', 'string', 'max:80'],
            'travellers.*.last_name' => ['required', 'string', 'max:80'],
            'travellers.*.email' => ['nullable', 'email', 'max:190'],
            'travellers.*.phone' => ['nullable', 'string', 'max:40'],
            'travellers.*.gender' => ['nullable', 'in:female,male,other'],
            'travellers.*.nationality' => ['nullable', 'string', 'max:80'],
            'travellers.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'travellers.*.passport_number' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9 -]+$/'],
            'travellers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
            'travellers.*.insurance_provider' => ['nullable', 'string', 'max:120'],
            'travellers.*.insurance_policy' => ['nullable', 'string', 'max:120'],
            'travellers.*.dietary' => ['nullable', 'string', 'max:190'],
            'travellers.*.medical_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $existing = $booking->travellers()->orderByDesc('is_lead')->orderBy('id')->get()->values();

        foreach ($data['travellers'] as $index => $input) {
            if (! is_int($index) || $index < 0 || $index >= $max) {
                continue;
            }

            /** @var Traveller $traveller */
            $traveller = $existing[$index] ?? $booking->travellers()->make(['is_lead' => $index === 0 && $existing->isEmpty()]);

            foreach ($input as $field => $value) {
                if (in_array($field, self::SENSITIVE, true) && blank($value)) {
                    continue; // keep what is on file
                }

                $traveller->{$field} = $value === '' ? null : $value;
            }

            $traveller->save();
        }

        return redirect()->route('bookings.travellers.edit', $uuid)->with('status', 'Thank you — traveller details saved securely.');
    }

    private function find(string $uuid): Booking
    {
        $booking = Booking::where('uuid', $uuid)->with(['trip', 'contact'])->firstOrFail();

        abort_if(in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Completed], true), 404);

        return $booking;
    }

    /** @return array<int, array<string, mixed>> one slot per traveller (pax), prefilled with non-sensitive data */
    private function slots(Booking $booking): array
    {
        $existing = $booking->travellers()->orderByDesc('is_lead')->orderBy('id')->get()->values();
        $slots = [];

        for ($i = 0; $i < max(1, (int) $booking->pax, $existing->count()); $i++) {
            $t = $existing[$i] ?? null;
            $slots[] = [
                'first_name' => $t?->first_name,
                'last_name' => $t?->last_name,
                'email' => $t?->email,
                'phone' => $t?->phone,
                'gender' => $t?->gender,
                'nationality' => $t?->nationality,
                'dietary' => $t?->dietary,
                'on_file' => collect(self::SENSITIVE)->mapWithKeys(fn ($f) => [$f => $t && filled($t->{$f})])->all(),
            ];
        }

        return $slots;
    }
}
