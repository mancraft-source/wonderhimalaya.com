{{-- Traveller details form: /booking/{uuid}/travellers (noindex). Sensitive values are never echoed back. --}}
@php
    $seo = \App\Support\Seo::make('Traveller details');
    $seo->noindex = true;
@endphp
<x-layouts.site :seo="$seo">
    <div class="container-narrow py-12 lg:py-20">
        <p class="eyebrow">Booking {{ $booking->number }}</p>
        <h1 class="mt-4 text-display-md">Traveller details</h1>
        <p class="mt-3 text-ink-soft">
            {{ $booking->trip?->title }} @if ($booking->start_date) · {{ $booking->start_date->format('j M Y') }} @endif.
            We need these details for trekking permits and insurance. Passport, date of birth, insurance and medical information is encrypted.
        </p>

        @if (session('status'))
            <div class="mt-8 rounded-2xl bg-juniper-50 p-5 text-juniper-800 ring-1 ring-juniper-100" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-8 rounded-2xl bg-terracotta-500/10 p-5 text-terracotta-600 ring-1 ring-terracotta-500/30" role="alert">Please check the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('bookings.travellers.update', $booking->uuid) }}" class="mt-10 space-y-10">
            @csrf
            @foreach ($travellers as $i => $t)
                @php $p = "travellers.$i"; $n = "travellers[$i]"; @endphp
                <fieldset class="card-surface p-6 sm:p-8">
                    <legend class="px-2 font-display text-2xl text-juniper-800">Traveller {{ $i + 1 }}@if ($i === 0) <span class="text-base text-ink-soft">(lead)</span>@endif</legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        @foreach ([
                            'first_name' => ['First name (as in passport)', 'text', true],
                            'last_name' => ['Last name (as in passport)', 'text', true],
                            'email' => ['E-mail', 'email', false],
                            'phone' => ['Phone / WhatsApp', 'tel', false],
                            'nationality' => ['Nationality', 'text', false],
                        ] as $field => [$label, $type, $required])
                            <div>
                                <label for="{{ $p }}.{{ $field }}" class="field-label">{{ $label }}</label>
                                <input id="{{ $p }}.{{ $field }}" name="{{ $n }}[{{ $field }}]" type="{{ $type }}" class="field" value="{{ old("$p.$field", $t[$field]) }}" @required($required) autocomplete="off">
                                @error("$p.$field")<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                        <div>
                            <label for="{{ $p }}.gender" class="field-label">Gender</label>
                            <select id="{{ $p }}.gender" name="{{ $n }}[gender]" class="field">
                                <option value="">Prefer not to say</option>
                                @foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old("$p.gender", $t['gender']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        @foreach ([
                            'date_of_birth' => ['Date of birth', 'date'],
                            'passport_number' => ['Passport number', 'text'],
                            'passport_expiry' => ['Passport expiry', 'date'],
                            'insurance_provider' => ['Travel insurance provider', 'text'],
                            'insurance_policy' => ['Insurance policy number', 'text'],
                        ] as $field => [$label, $type])
                            <div>
                                <label for="{{ $p }}.{{ $field }}" class="field-label">{{ $label }}</label>
                                <input id="{{ $p }}.{{ $field }}" name="{{ $n }}[{{ $field }}]" type="{{ $type }}" class="field" value="{{ old("$p.$field") }}" autocomplete="off"
                                    placeholder="{{ $t['on_file'][$field] ? 'On file — leave blank to keep' : '' }}">
                                @error("$p.$field")<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach

                        <div class="sm:col-span-2">
                            <label for="{{ $p }}.dietary" class="field-label">Dietary requirements</label>
                            <input id="{{ $p }}.dietary" name="{{ $n }}[dietary]" type="text" class="field" value="{{ old("$p.dietary", $t['dietary']) }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="{{ $p }}.medical_notes" class="field-label">Medical conditions we should know about</label>
                            <textarea id="{{ $p }}.medical_notes" name="{{ $n }}[medical_notes]" rows="3" class="field" placeholder="{{ $t['on_file']['medical_notes'] ? 'On file — leave blank to keep' : '' }}">{{ old("$p.medical_notes") }}</textarea>
                        </div>
                    </div>
                </fieldset>
            @endforeach

            <button type="submit" class="btn-primary">Save traveller details</button>
        </form>
    </div>
</x-layouts.site>
