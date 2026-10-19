import Alpine from 'alpinejs';
import registerCombobox from './components/combobox';
import registerConsumerRegistration from './components/consumer-registration';
import registerAuthModals from './components/auth-modals';

window.Alpine = Alpine;

registerCombobox(Alpine);
registerConsumerRegistration(Alpine);
registerAuthModals(Alpine);

Alpine.start();
