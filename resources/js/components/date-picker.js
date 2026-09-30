// Seletores de data (x-ui.date-picker e x-ui.date-range-picker) como componentes Alpine.
//
// Datas trafegam como string AAAA-MM-DD (mesmo formato da API). Nunca usar toISOString(): ela converte para UTC
// e, no Brasil, uma data à meia-noite vira o dia anterior. Tudo é montado com as partes locais (ano, mês, dia).

const pad = (number) => String(number).padStart(2, '0');

export const toIso = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

export const fromIso = (iso) => {
    if (!iso) {
        return null;
    }
    const [year, month, day] = iso.split('-').map(Number);

    return new Date(year, month - 1, day);
};

const addDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);

const todayIso = () => toIso(new Date());

const formatBr = (iso) => (iso ? fromIso(iso).toLocaleDateString('pt-BR') : '');

const monthFormatter = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' });
const longFormatter = new Intl.DateTimeFormat('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

// Comportamento comum aos dois seletores: navegação entre meses e a grade de 6 semanas
function calendar({ min = null, max = null } = {}) {
    return {
        min,
        max,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        weekdays: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],

        showMonthOf(iso) {
            const date = fromIso(iso) ?? new Date();
            this.viewYear = date.getFullYear();
            this.viewMonth = date.getMonth();
        },

        prevMonth() {
            this.showMonthOf(toIso(new Date(this.viewYear, this.viewMonth - 1, 1)));
        },

        nextMonth() {
            this.showMonthOf(toIso(new Date(this.viewYear, this.viewMonth + 1, 1)));
        },

        monthLabel() {
            const label = monthFormatter.format(new Date(this.viewYear, this.viewMonth, 1));

            return label.charAt(0).toUpperCase() + label.slice(1);
        },

        days() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const start = addDays(first, -first.getDay());
            const today = todayIso();

            return Array.from({ length: 42 }, (_, index) => {
                const date = addDays(start, index);
                const iso = toIso(date);

                return {
                    iso,
                    day: date.getDate(),
                    inMonth: date.getMonth() === this.viewMonth,
                    isToday: iso === today,
                    disabled: (this.min && iso < this.min) || (this.max && iso > this.max),
                };
            });
        },

        ariaLabel(iso) {
            return longFormatter.format(fromIso(iso));
        },
    };
}

export function datePicker({ value = null, min = null, max = null } = {}) {
    return {
        ...calendar({ min, max }),
        value,
        open: false,

        label() {
            return formatBr(this.value);
        },

        toggle() {
            this.open ? this.close() : this.openPanel();
        },

        openPanel() {
            this.showMonthOf(this.value ?? todayIso());
            this.open = true;
        },

        close() {
            this.open = false;
        },

        isSelected(iso) {
            return iso === this.value;
        },

        isInRange() {
            return false;
        },

        pick(iso) {
            this.value = iso;
            this.close();
            this.$refs.trigger.focus();
        },

        pickToday() {
            this.pick(todayIso());
        },

        clear() {
            this.value = null;
            this.close();
        },
    };
}

// Atalhos de período: aplicados com um clique. O período personalizado exige "Aplicar".
const presets = () => {
    const today = new Date();
    const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

    return [
        { key: 'today', label: 'Hoje', from: today, to: today },
        { key: 'yesterday', label: 'Ontem', from: addDays(today, -1), to: addDays(today, -1) },
        { key: 'last7', label: 'Últimos 7 dias', from: addDays(today, -6), to: today },
        { key: 'last30', label: 'Últimos 30 dias', from: addDays(today, -29), to: today },
        { key: 'thisMonth', label: 'Este mês', from: firstOfMonth, to: today },
        {
            key: 'lastMonth',
            label: 'Mês passado',
            from: new Date(today.getFullYear(), today.getMonth() - 1, 1),
            to: new Date(today.getFullYear(), today.getMonth(), 0),
        },
        { key: 'thisYear', label: 'Este ano', from: new Date(today.getFullYear(), 0, 1), to: today },
    ].map((preset) => ({ ...preset, from: toIso(preset.from), to: toIso(preset.to) }));
};

export function dateRangePicker({ value = null, min = null, max = null } = {}) {
    return {
        ...calendar({ min, max }),
        value: { from: value?.from ?? null, to: value?.to ?? null },
        draft: { from: null, to: null },
        presets: presets(),
        open: false,

        label() {
            const { from, to } = this.value;
            if (!from) {
                return '';
            }

            return from === to || !to ? formatBr(from) : `${formatBr(from)} – ${formatBr(to)}`;
        },

        activePreset() {
            return this.presets.find((preset) => preset.from === this.value.from && preset.to === this.value.to)?.key ?? null;
        },

        toggle() {
            this.open ? this.close() : this.openPanel();
        },

        openPanel() {
            this.draft = { ...this.value };
            this.showMonthOf(this.value.from ?? todayIso());
            this.open = true;
        },

        close() {
            this.open = false;
        },

        isSelected(iso) {
            return iso === this.draft.from || iso === this.draft.to;
        },

        isInRange(iso) {
            return Boolean(this.draft.from && this.draft.to && iso > this.draft.from && iso < this.draft.to);
        },

        // Primeiro clique define o início; o segundo, o fim (invertendo se vier antes do início)
        pick(iso) {
            const { from, to } = this.draft;

            if (!from || to) {
                this.draft = { from: iso, to: null };
            } else if (iso < from) {
                this.draft = { from: iso, to: from };
            } else {
                this.draft = { from, to: iso };
            }
        },

        applyPreset(preset) {
            this.value = { from: preset.from, to: preset.to };
            this.close();
            this.$refs.trigger.focus();
        },

        canApply() {
            return Boolean(this.draft.from && this.draft.to);
        },

        apply() {
            if (!this.canApply()) {
                return;
            }
            this.value = { ...this.draft };
            this.close();
            this.$refs.trigger.focus();
        },

        clear() {
            this.value = { from: null, to: null };
            this.draft = { from: null, to: null };
            this.close();
        },
    };
}
