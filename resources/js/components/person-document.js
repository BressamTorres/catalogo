export const INVALID_CPF_MESSAGE = 'Informe um CPF válido.';

function normalizeDocument(value) {
    return String(value ?? '').replace(/\D/g, '').slice(0, 11);
}

export function formatDocument(value) {
    const document = normalizeDocument(value);

    return document
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
}

export function documentValidationMessage(value) {
    const document = normalizeDocument(value);

    if (document.length === 0) {
        return '';
    }

    return document.length === 11 && isValidCpf(document) ? '' : INVALID_CPF_MESSAGE;
}

function isValidCpf(cpf) {
    if (/^(\d)\1{10}$/.test(cpf)) {
        return false;
    }

    for (let checkDigitIndex = 9; checkDigitIndex <= 10; checkDigitIndex += 1) {
        let sum = 0;

        for (let digitIndex = 0; digitIndex < checkDigitIndex; digitIndex += 1) {
            sum += Number(cpf[digitIndex]) * ((checkDigitIndex + 1) - digitIndex);
        }

        const remainder = sum % 11;
        const expectedDigit = remainder < 2 ? 0 : 11 - remainder;

        if (Number(cpf[checkDigitIndex]) !== expectedDigit) {
            return false;
        }
    }

    return true;
}
