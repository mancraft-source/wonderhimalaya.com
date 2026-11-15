<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="App\Support\EmailBranding::siteUrl()">
            {{ App\Support\EmailBranding::company()['name'] }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
@php($brand = App\Support\EmailBranding::company())
{{ $brand['name'] }} · {{ $brand['address'] }}
{{ $brand['phone'] }} · {{ $brand['email'] }}
© {{ date('Y') }} {{ $brand['legal_name'] }}. @lang('All rights reserved.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
