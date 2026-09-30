// Livewire e Alpine carregados por este bundle (em vez da injeção automática do Livewire), para registrar
// os componentes Alpine da aplicação antes de o Alpine iniciar. O layout usa @livewireScriptConfig.
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { datePicker, dateRangePicker } from './components/date-picker';

Alpine.data('datePicker', datePicker);
Alpine.data('dateRangePicker', dateRangePicker);

Livewire.start();
