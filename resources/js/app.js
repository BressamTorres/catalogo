import Alpine from 'alpinejs';
import registerCombobox from './components/combobox';
import registerConsumerRegistration from './components/consumer-registration';

window.Alpine = Alpine;

registerCombobox(Alpine);
registerConsumerRegistration(Alpine);

Alpine.start();
