@props(['url'])
@php($brand = App\Support\EmailBranding::company())
<tr>
<td class="header" align="center">
<table class="brand" align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-logo" valign="middle">
<a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ App\Support\EmailBranding::logoUrl() }}" class="logo" width="56" height="56" alt=""></a>
</td>
<td class="brand-name" valign="middle">
<a href="{{ $url }}" class="wordmark" target="_blank" rel="noopener">{{ $brand['name'] }}</a><br>
<span class="tagline">{{ $brand['tagline'] }}</span>
</td>
</tr>
</table>
</td>
</tr>
