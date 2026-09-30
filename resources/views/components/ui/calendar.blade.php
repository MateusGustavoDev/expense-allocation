{{--
    Grade do calendário (uso interno de x-ui.date-picker e x-ui.date-range-picker).
    Usa o escopo Alpine do componente pai: days(), pick(), isSelected(), isInRange(), prevMonth(), nextMonth().
--}}
<div class="flex flex-col gap-2">
    <div class="flex items-center justify-between">
        <button type="button" x-on:click="prevMonth()" aria-label="Mês anterior" class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-ds-gray-600 hover:bg-ds-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600">
            <x-ui.icon name="chevron-left" class="size-4" />
        </button>
        <p class="text-sm font-semibold text-ds-gray-900" x-text="monthLabel()" aria-live="polite"></p>
        <button type="button" x-on:click="nextMonth()" aria-label="Próximo mês" class="flex size-8 cursor-pointer items-center justify-center rounded-lg text-ds-gray-600 hover:bg-ds-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600">
            <x-ui.icon name="chevron-right" class="size-4" />
        </button>
    </div>

    <div class="grid grid-cols-7 text-center text-xs font-medium text-ds-gray-500" aria-hidden="true">
        <template x-for="(weekday, index) in weekdays" :key="index">
            <span class="py-1" x-text="weekday"></span>
        </template>
    </div>

    <div class="grid grid-cols-7 gap-y-1">
        <template x-for="day in days()" :key="day.iso">
            <button
                type="button"
                x-on:click="pick(day.iso)"
                x-bind:disabled="day.disabled"
                x-bind:aria-label="ariaLabel(day.iso)"
                x-bind:aria-pressed="isSelected(day.iso)"
                x-bind:aria-current="day.isToday ? 'date' : null"
                x-text="day.day"
                class="h-9 cursor-pointer text-sm tabular-nums transition-colors focus-visible:relative focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-primary-600 disabled:cursor-not-allowed disabled:opacity-40"
                x-bind:class="{
                    'rounded-lg bg-ds-primary-500 font-semibold text-ds-black hover:bg-ds-primary-600': isSelected(day.iso),
                    'bg-ds-primary-50 text-ds-primary-900': isInRange(day.iso),
                    'rounded-lg hover:bg-ds-gray-100': ! isSelected(day.iso) && ! isInRange(day.iso),
                    'text-ds-gray-900': day.inMonth && ! isSelected(day.iso) && ! isInRange(day.iso),
                    'text-ds-gray-400': ! day.inMonth && ! isSelected(day.iso),
                    'font-semibold underline decoration-ds-primary-500 decoration-2 underline-offset-4': day.isToday && ! isSelected(day.iso),
                }"
            ></button>
        </template>
    </div>
</div>
