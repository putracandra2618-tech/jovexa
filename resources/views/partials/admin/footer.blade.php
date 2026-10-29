<footer class="footer-custom">
    <div class="footer-left">
        <span class="footer-logo">
            <i class="bi bi-asterisk"></i> {{ config('app.name') }}
        </span>
        <span class="footer-separator">|</span>
        <span class="footer-copy">&copy; {{ now()->year }} {{ config('app.name') }}</span>
    </div>
    <div class="footer-right">
        <ul class="footer-links">
            <li><a href="{{ route('home') }}" class="footer-link" data-turbo="false">Beranda</a></li>
        </ul>
    </div>
</footer>
