<?php

namespace App\Http\Resources\V1;

use App\Models\Traveller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Traveller with sensitive fields masked: passport shows the last 3 characters only;
 * date of birth, insurance policy and medical notes are never returned in clear.
 *
 * @mixin Traveller
 */
class TravellerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $passport = (string) ($this->passport_number ?? '');

        return [
            'id' => $this->id,
            'is_lead' => $this->is_lead,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'passport_number' => self::mask($passport),
            'has_passport_expiry' => filled($this->passport_expiry),
            'has_date_of_birth' => filled($this->date_of_birth),
            'insurance_provider' => $this->insurance_provider,
            'has_insurance_policy' => filled($this->insurance_policy),
            'has_medical_notes' => filled($this->medical_notes),
            'dietary' => $this->dietary,
        ];
    }

    public static function mask(?string $value, int $visible = 3): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = mb_strlen($value);

        return $length <= $visible
            ? str_repeat('•', $length)
            : str_repeat('•', $length - $visible).mb_substr($value, -$visible);
    }
}
