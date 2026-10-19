export default function registerAuthModals(Alpine) {
    Alpine.data('authModals', (config = {}) => ({
        activeModal: config.initialModal ?? null,
        returnTo: config.initialReturnTo || config.currentUrl || '',
        statusMessage: config.initialStatusMessage ?? '',
        currentUrl: config.currentUrl ?? '',
        authenticated: Boolean(config.authenticated),
        cartAddUrl: config.cartAddUrl ?? '',
        cartCount: Number(config.cartCount ?? 0),
        cartLoginMessage: config.cartLoginMessage ?? 'Faça login para adicionar produtos ao carrinho.',
        cartStatusMessage: '',
        cartErrorMessage: '',
        csrfToken: config.csrfToken ?? '',
        pendingCartStorageKey: config.pendingCartStorageKey ?? '',
        pendingCartAddition: null,
        cartRequestInProgress: false,
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

            this.pendingCartAddition = this.loadPendingCartAddition();

            if (this.pendingCartAddition && this.authenticated) {
                this.$nextTick(() => this.submitPendingCartAddition());
            } else if (this.pendingCartAddition) {
                this.returnTo = this.currentUrl;
                this.statusMessage = this.cartLoginMessage;
                this.activeModal ||= 'login';
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
            this.clearPendingCartAddition();

            this.$nextTick(() => {
                this.lastFocusedElement?.focus();
                this.lastFocusedElement = null;
            });
        },

        async addToCart(event) {
            event.preventDefault();

            if (this.cartRequestInProgress) {
                return;
            }

            this.pendingCartAddition = this.createPendingCartAddition(event.currentTarget);
            this.storePendingCartAddition();

            if (!this.authenticated) {
                this.open('login', event.submitter, this.currentUrl, this.cartLoginMessage);

                return;
            }

            await this.submitPendingCartAddition();
        },

        createPendingCartAddition(form) {
            const fields = Array.from(new FormData(form).entries())
                .filter(([name, value]) => name !== '_token' && typeof value === 'string');

            return {
                requestId: this.createRequestId(),
                fields,
            };
        },

        createRequestId() {
            if (typeof window.crypto?.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
                const random = window.crypto.getRandomValues(new Uint8Array(1))[0] & 15;
                const value = character === 'x' ? random : (random & 3) | 8;

                return value.toString(16);
            });
        },

        storePendingCartAddition() {
            if (!this.pendingCartStorageKey || !this.pendingCartAddition) {
                return;
            }

            try {
                window.sessionStorage.setItem(
                    this.pendingCartStorageKey,
                    JSON.stringify(this.pendingCartAddition),
                );
            } catch {
                // The in-memory state still supports the current page when storage is unavailable.
            }
        },

        loadPendingCartAddition() {
            if (!this.pendingCartStorageKey) {
                return null;
            }

            try {
                const pending = JSON.parse(window.sessionStorage.getItem(this.pendingCartStorageKey));

                if (
                    typeof pending?.requestId !== 'string'
                    || !Array.isArray(pending?.fields)
                    || !pending.fields.every((field) => (
                        Array.isArray(field)
                        && field.length === 2
                        && typeof field[0] === 'string'
                        && typeof field[1] === 'string'
                    ))
                ) {
                    this.clearPendingCartAddition();

                    return null;
                }

                return pending;
            } catch {
                this.clearPendingCartAddition();

                return null;
            }
        },

        clearPendingCartAddition() {
            this.pendingCartAddition = null;

            if (!this.pendingCartStorageKey) {
                return;
            }

            try {
                window.sessionStorage.removeItem(this.pendingCartStorageKey);
            } catch {
                // Nothing else is required when storage is unavailable.
            }
        },

        async submitPendingCartAddition() {
            if (!this.pendingCartAddition || this.cartRequestInProgress || !this.cartAddUrl) {
                return;
            }

            this.cartRequestInProgress = true;
            this.cartStatusMessage = '';
            this.cartErrorMessage = '';

            const formData = new FormData();

            this.pendingCartAddition.fields.forEach(([name, value]) => formData.append(name, value));
            formData.set('cart_request_id', this.pendingCartAddition.requestId);

            try {
                const response = await fetch(this.cartAddUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (response.status === 401) {
                    this.authenticated = false;
                    this.open('login', null, this.currentUrl, this.cartLoginMessage);

                    return;
                }

                if (response.status === 419) {
                    this.storePendingCartAddition();
                    window.location.reload();

                    return;
                }

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationMessage = Object.values(payload.errors ?? {}).flat()[0];

                    this.cartErrorMessage = validationMessage
                        || 'Não foi possível adicionar o produto ao carrinho. Tente novamente.';

                    if (response.status === 422) {
                        this.clearPendingCartAddition();
                    }

                    return;
                }

                this.cartCount = Number(payload.cart_count ?? this.cartCount);
                this.cartStatusMessage = payload.message ?? 'Produto adicionado ao carrinho!';
                this.clearPendingCartAddition();
            } catch {
                this.cartErrorMessage = 'Não foi possível adicionar o produto ao carrinho. Tente novamente.';
            } finally {
                this.cartRequestInProgress = false;
            }
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
