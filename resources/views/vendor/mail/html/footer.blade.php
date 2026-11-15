@php
    $brand = App\Support\EmailBranding::company();
    $social = App\Support\EmailBranding::socialLinks();
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
<p class="footer-name">{{ $brand['name'] }}</p>
<p>{{ $brand['address'] }}</p>
<p><a href="{{ App\Support\EmailBranding::telHref($brand['phone']) }}">{{ $brand['phone'] }}</a> &middot; <a href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a></p>
@if ($social !== [])
<p class="footer-social">
@foreach ($social as $label => $link)
<a href="{{ $link }}" target="_blank" rel="noopener">{{ $label }}</a>@unless ($loop->last) &middot; @endunless
@endforeach
</p>
@endif
<div class="footer-legal">{{ Illuminate\Mail\Markdown::parse($slot) }}</div>
</td>
</tr>
</table>
</td>
</tr>
