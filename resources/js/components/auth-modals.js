export default function registerAuthModals(Alpine) {
    Alpine.data('authModals', (config = {}) => ({
        activeModal: config.initialModal ?? null,
        returnTo: config.initialReturnTo || config.currentUrl || '',
        statusMessage: config.initialStatusMessage ?? '',
        currentUrl: config.currentUrl ?? '',
        lastFocusedElement: null,
        originalDocumentOverflow: '',

        init() {
            this.originalDocumentOverflow = document.documentElement.style.overflow;

            this.$watch('activeModal', (modal) => {
                document.documentElement.style.overflow = modal
                    ? 'hidden'
                    : this.originalDocumentOverflow;

                if (modal) {
                    this.$nextTick(() => this.focusModal());
                }
            });

            if (this.activeModal) {
                document.documentElement.style.overflow = 'hidden';
                this.$nextTick(() => this.focusModal());
            }
        },

        open(modal, trigger = null, returnTo = null, statusMessage = '') {
            this.lastFocusedElement = trigger;
            this.returnTo = returnTo || this.currentUrl;
            this.statusMessage = statusMessage;
            this.activeModal = modal;
        },

        switchTo(modal) {
            this.activeModal = modal;
        },

        close() {
            if (!this.activeModal) {
                return;
            }

            this.activeModal = null;
            this.statusMessage = '';

            this.$nextTick(() => {
                this.lastFocusedElement?.focus();
                this.lastFocusedElement = null;
            });
        },

        focusModal() {
            const dialog = this.activeModal === 'register'
                ? this.$refs.registerDialog
                : this.$refs.loginDialog;

            dialog?.querySelector('[data-auth-autofocus]')?.focus();
        },

        trapFocus(event) {
            const dialog = event.currentTarget;
            const focusableElements = Array.from(dialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )).filter((element) => element.offsetParent !== null);

            if (focusableElements.length === 0) {
                event.preventDefault();
                return;
            }

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        },
    }));
}
