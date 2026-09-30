{{--
    Layout das telas sem sessão (login): sem sidebar, conteúdo centralizado com a marca.
    Usado pelos componentes Livewire com #[Layout('layouts::guest')].
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    @include('layouts.partials.head', ['title' => $title])
</head>
<body class="min-h-screen" x-data>
    <main class="flex min-h-screen flex-col items-center justify-center gap-8 px-4 py-12">
        <div class="flex flex-col items-center gap-3 text-center">
            <x-ui.logo size="lg" />
            <div class="flex flex-col gap-0.5">
                <p class="text-xl font-bold text-ds-gray-900">Rateio</p>
                <p class="text-sm text-ds-gray-500">Despesas compartilhadas entre as unidades do grupo</p>
            </div>
        </div>

        <div class="w-full max-w-sm">
            {{ $slot }}
        </div>
    </main>

    <x-ui.toaster />
    @livewireScriptConfig
</body>
</html>
