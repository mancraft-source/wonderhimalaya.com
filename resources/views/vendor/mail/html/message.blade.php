<x-mail::layout>
{{-- Preheader (inbox preview text) --}}
@isset($preheader)
<x-slot:preheader>{{ $preheader }}</x-slot:preheader>
@endisset

{{-- Header --}}
<x-slot:header>
<x-mail::header :url="App\Support\EmailBranding::siteUrl()">
{{ App\Support\EmailBranding::company()['name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ App\Support\EmailBranding::company()['legal_name'] }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
