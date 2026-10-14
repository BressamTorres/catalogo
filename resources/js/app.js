import Alpine from 'alpinejs';
import registerCombobox from './components/combobox';

window.Alpine = Alpine;

registerCombobox(Alpine);

Alpine.start();
