@props(['url', 'mail' => null])
@php
    // Embedded inline when sending, so the logo shows without "load images";
    // previews (no mail message) fall back to the public file.
    $logo = $mail instanceof \Illuminate\Mail\Message
        ? $mail->embed(public_path('images/ke-logo.png'))
        : asset('images/ke-logo.png');
@endphp
<tr>
<td class="header">
<table class="header-band" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-logo" width="48" valign="middle">
<a href="{{ $url }}" class="logo-tile"><img src="{{ $logo }}" class="logo" width="40" height="40" alt="Kaduna Electric"></a>
</td>
<td class="header-name" valign="middle">
<p class="brand">Kaduna Electric</p>
<p class="brand-sub">New Service Department</p>
</td>
</tr>
</table>
</td>
</tr>
