{{--
    Linha de estado vazio ocupando a tabela inteira. Use títulos diferentes para "sem resultado no filtro"
    (icon="search") e "nada cadastrado ainda" (ícone do domínio + ação).
--}}
@props([
    'colspan',
    'icon' => 'inbox',
    'title',
    'description' => null,
])

<tr>
    <td colspan="{{ $colspan }}" class="p-0">
        <x-ui.empty :icon="$icon" :title="$title" :description="$description" bare>
            @isset($action)
                <x-slot:action>{{ $action }}</x-slot:action>
            @endisset
        </x-ui.empty>
    </td>
</tr>
