{{-- <head> comum aos layouts app e guest: título, favicon, fontes e assets --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ? "{$title} · " : '' }}{{ config('app.name') }}</title>
{{-- SVG para navegadores modernos; ICO como fallback; PNG sem cantos para a tela inicial do iOS (que aplica a própria máscara) --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
