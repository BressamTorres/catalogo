import { documentValidationMessage, formatDocument } from './person-document';
import { formatPhone } from './phone';

export default function registerConsumerRegistration(Alpine) {
    Alpine.data('consumerRegistrationForm', (config) => ({
        document: formatDocument(config.document),
        phone: formatPhone(config.phone),
        documentError: '',
        showPassword: false,
        showPasswordConfirmation: false,
        submitting: false,

        maskDocument(event) {
            const value = formatDocument(event.target.value);

            event.target.value = value;
            this.document = value;
            this.documentError = '';
        },

        validateDocument() {
            this.documentError = documentValidationMessage(this.document);

            return this.documentError === '';
        },

        maskPhone(event) {
            const value = formatPhone(event.target.value);

            event.target.value = value;
            this.phone = value;
        },

        submit(event) {
            if (!this.validateDocument()) {
                event.preventDefault();
                this.$nextTick(() => this.$refs.document?.focus());

                return;
            }

            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },
    }));
}
