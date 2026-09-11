const dateFormatter = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
const dateTimeFormatter = new Intl.DateTimeFormat('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
const numberFormatter = new Intl.NumberFormat('ru-RU');

export const STATUS_LABELS = {
    pending: 'В очереди',
    running: 'Идёт сбор',
    completed: 'Готово',
    failed: 'Ошибка',
};

export const ERROR_LABELS = {
    invalid_url: 'Некорректная ссылка',
    source_unavailable: 'Источник недоступен',
    blocked: 'Яндекс заблокировал запросы',
    markup_changed: 'Изменилась разметка Яндекс.Карт',
    empty_response: 'Пустой ответ источника',
    unexpected: 'Непредвиденная ошибка',
};

const isEmpty = (value) => value === null || value === undefined;

export const formatDate = (value) => (value ? dateFormatter.format(new Date(value)) : '—');

export const formatDateTime = (value) => (value ? dateTimeFormatter.format(new Date(value)) : '—');

export const formatNumber = (value) => (isEmpty(value) ? '—' : numberFormatter.format(value));

export const formatRating = (value) => (isEmpty(value) ? '—' : Number(value).toFixed(1).replace('.', ','));

export function plural(count, forms) {
    const n = Math.abs(count) % 100;
    const n1 = n % 10;
    const form = n > 10 && n < 20 ? forms[2] : n1 > 1 && n1 < 5 ? forms[1] : n1 === 1 ? forms[0] : forms[2];

    return `${formatNumber(count)} ${form}`;
}

export function errorMessage(error) {
    const payload = error?.response?.data;
    const firstValidationError = payload?.errors && Object.values(payload.errors).flat()[0];

    return firstValidationError || payload?.message || error?.message || 'Неизвестная ошибка';
}
