const initializedForms = new WeakSet();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('technicianRegistration', (role = null, serviceType = 'both') => ({
        role,
        selectingRole: !role,
        submitting: false,
        showPassword: false,
        showConfirmation: false,
        password: '',
        passwordConfirmation: '',
        serviceType,
        selectedCategory: '',
        validIdName: '',
        credentialsName: '',
        validIdError: '',
        credentialsError: '',
        get passwordStrengthLabel() {
            if (this.password.length === 0) {
                return 'Not entered';
            }

            const score = [
                this.password.length >= 8,
                /[a-z]/.test(this.password) && /[A-Z]/.test(this.password),
                /\d/.test(this.password),
                /[^A-Za-z0-9]/.test(this.password),
            ].filter(Boolean).length;

            return ['Weak', 'Weak', 'Fair', 'Good', 'Strong'][score];
        },
        get passwordStrengthClass() {
            return {
                'Not entered': 'text-gray-500',
                Weak: 'text-red-600',
                Fair: 'text-amber-600',
                Good: 'text-blue-600',
                Strong: 'text-emerald-600',
            }[this.passwordStrengthLabel];
        },
        get passwordsMatch() {
            return this.passwordConfirmation.length > 0 && this.password === this.passwordConfirmation;
        },
        chooseRole(value) {
            this.role = value;
            this.selectingRole = false;
            this.$nextTick(() => {
                const field = value === 'customer'
                    ? this.$refs.customerName
                    : this.$refs.technicianFirstName;

                field?.focus();
            });
        },
        changeRole() {
            this.role = '';
            this.selectingRole = true;
            this.submitting = false;
        },
        selectDocument(documentType, event) {
            const file = event.target.files[0];
            const nameProperty = `${documentType}Name`;
            const errorProperty = `${documentType}Error`;

            this[nameProperty] = file?.name || '';
            this[errorProperty] = '';

            if (file && file.size > 5 * 1024 * 1024) {
                this[nameProperty] = '';
                this[errorProperty] = 'Choose a file no larger than 5 MB.';
                event.target.value = '';
            }
        },
    }));
});

function setLocationStatus(form, message) {
    const status = form.querySelector('[data-technician-location-status]');

    if (!status) {
        return;
    }

    status.textContent = message;
    status.classList.toggle('hidden', message === '');
}

function initializeTechnicianRegistration() {
    document.querySelectorAll('[data-technician-registration-form]').forEach((form) => {
        if (initializedForms.has(form)) {
            return;
        }

        initializedForms.add(form);

        form.querySelector('[data-technician-location-button]')?.addEventListener('click', () => {
            if (!('geolocation' in navigator)) {
                setLocationStatus(form, 'Location access is not supported by this browser.');

                return;
            }

            setLocationStatus(form, 'Requesting your location…');

            navigator.geolocation.getCurrentPosition(
                ({ coords }) => {
                    const serviceArea = form.querySelector('[data-technician-service-area]');

                    if (serviceArea) {
                        serviceArea.value = `${coords.latitude.toFixed(5)}, ${coords.longitude.toFixed(5)}`;
                        serviceArea.dispatchEvent(new Event('input', { bubbles: true }));
                    }

                    setLocationStatus(form, 'Location added. You can replace it with your city or service area.');
                },
                () => setLocationStatus(form, 'Location could not be detected. Enter your service area manually.'),
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 },
            );
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeTechnicianRegistration);
document.addEventListener('livewire:navigated', initializeTechnicianRegistration);
