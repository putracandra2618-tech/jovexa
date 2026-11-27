@php
    $whatsappLink = \App\Support\WhatsappContact::link(config('jovexa.contact.whatsapp'));
@endphp

@if ($whatsappLink)
    <a href="{{ $whatsappLink }}" class="jx-wa-bubble" target="_blank" rel="noopener"
       aria-label="Hubungi CS Jovexa lewat WhatsApp">
        <i class="bi bi-whatsapp" aria-hidden="true"></i>
        <span class="jx-wa-bubble-label">Chat CS</span>
    </a>
@endif
