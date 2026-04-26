document.addEventListener('DOMContentLoaded', function () {
    var config = window.APP_DATE_TIME_VALIDATION || {};
    var timezone = config.timezone || 'Asia/Manila';
    var invalidMessage = config.message || 'Invalid input: The selected date and time has already passed.';
    var baseNowMs = Date.parse(config.nowIso || new Date().toISOString());
    var bootMs = Date.now();
    var fields = Array.prototype.slice.call(document.querySelectorAll('[data-datetime-future="true"]'));

    if (!fields.length) {
        return;
    }

    var pairMap = new Map();
    fields.forEach(function (field) {
        var pair = field.getAttribute('data-datetime-pair');
        if (!pair) {
            return;
        }

        if (!pairMap.has(pair)) {
            pairMap.set(pair, []);
        }
        pairMap.get(pair).push(field);
    });

    fields.forEach(function (field) {
        ['input', 'change', 'blur'].forEach(function (eventName) {
            field.addEventListener(eventName, function () {
                validateField(field);
                updateFormSubmitState(field.form);
            });
        });
    });

    refreshAll();
    window.setInterval(refreshAll, 30000);

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var firstInvalid = null;
            getFormFields(form).forEach(function (field) {
                if (!validateField(field) && !firstInvalid) {
                    firstInvalid = field;
                }
            });

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });

    function refreshAll() {
        fields.forEach(refreshConstraints);
        fields.forEach(validateField);
        document.querySelectorAll('form').forEach(updateFormSubmitState);
    }

    function getCurrentAppDate() {
        var elapsed = Date.now() - bootMs;
        return new Date(baseNowMs + elapsed);
    }

    function getNowParts() {
        var formatter = new Intl.DateTimeFormat('en-CA', {
            timeZone: timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23'
        });
        var labelFormatter = new Intl.DateTimeFormat('en-US', {
            timeZone: timezone,
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        var formatted = {};
        formatter.formatToParts(getCurrentAppDate()).forEach(function (part) {
            if (part.type !== 'literal') {
                formatted[part.type] = part.value;
            }
        });

        return {
            date: formatted.year + '-' + formatted.month + '-' + formatted.day,
            time: formatted.hour + ':' + formatted.minute,
            datetimeLocal: formatted.year + '-' + formatted.month + '-' + formatted.day + 'T' + formatted.hour + ':' + formatted.minute,
            label: labelFormatter.format(getCurrentAppDate()) + ' (' + timezone + ')'
        };
    }

    function normalizeTimeValue(value) {
        var raw = (value || '').trim();
        if (!raw) {
            return '';
        }
        if (raw.indexOf('-') !== -1) {
            raw = raw.split('-', 1)[0].trim();
        }

        var standard = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
        if (standard) {
            return standard[1].padStart(2, '0') + ':' + standard[2];
        }

        var meridiem = raw.match(/^(\d{1,2}):(\d{2})\s*([AP]M)$/i);
        if (!meridiem) {
            return '';
        }

        var hour = Number(meridiem[1]);
        var minute = meridiem[2];
        var period = meridiem[3].toUpperCase();
        if (period === 'PM' && hour < 12) {
            hour += 12;
        }
        if (period === 'AM' && hour === 12) {
            hour = 0;
        }

        return String(hour).padStart(2, '0') + ':' + minute;
    }

    function refreshConstraints(field) {
        var role = field.getAttribute('data-datetime-role') || field.type;
        var now = getNowParts();

        if (role === 'datetime-local') {
            field.min = now.datetimeLocal;
            return;
        }

        if (role === 'date') {
            field.min = now.date;
            return;
        }

        if (role !== 'time' && role !== 'time-range') {
            return;
        }

        var dateField = getPairField(field, 'date');
        if (!dateField) {
            return;
        }

        var isToday = dateField.value && dateField.value === now.date;
        if (field.tagName === 'INPUT' && field.type === 'time') {
            field.min = isToday ? now.time : '';
            return;
        }

        if (field.tagName === 'SELECT') {
            Array.prototype.forEach.call(field.options, function (option) {
                if (!option.value) {
                    option.disabled = false;
                    return;
                }
                var optionTime = normalizeTimeValue(option.value);
                option.disabled = Boolean(isToday && optionTime && optionTime < now.time);
            });
        }
    }

    function getPairField(field, role) {
        var pair = field.getAttribute('data-datetime-pair');
        if (!pair || !pairMap.has(pair)) {
            return null;
        }

        return pairMap.get(pair).find(function (candidate) {
            return (candidate.getAttribute('data-datetime-role') || candidate.type) === role;
        }) || null;
    }

    function getFormFields(form) {
        return fields.filter(function (field) {
            return field.form === form;
        });
    }

    function updateFormSubmitState(form) {
        if (!form) {
            return;
        }

        var hasInvalid = getFormFields(form).some(function (field) {
            return field.dataset.datetimeInvalid === 'true';
        });

        Array.prototype.forEach.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'), function (button) {
            if (button.hasAttribute('data-datetime-submit-ignore')) {
                return;
            }
            button.disabled = hasInvalid;
        });
    }

    function validateField(field) {
        var role = field.getAttribute('data-datetime-role') || field.type;
        if (role === 'datetime-local') {
            return validateDateTimeLocal(field);
        }

        var pair = field.getAttribute('data-datetime-pair');
        if (pair) {
            return validatePair(pair);
        }

        if (role === 'date') {
            return validateDateOnly(field);
        }

        return true;
    }

    function validateDateOnly(field) {
        var now = getNowParts();
        if (!field.value) {
            return setFieldValidity([field], true, '');
        }

        return setFieldValidity(
            [field],
            field.value >= now.date,
            field.value >= now.date ? '' : buildMessage(now)
        );
    }

    function validateDateTimeLocal(field) {
        var now = getNowParts();
        if (!field.value) {
            return setFieldValidity([field], true, '');
        }

        var value = field.value.length >= 16 ? field.value.slice(0, 16) : field.value;
        return setFieldValidity(
            [field],
            value >= now.datetimeLocal,
            value >= now.datetimeLocal ? '' : buildMessage(now)
        );
    }

    function validatePair(pair) {
        var items = pairMap.get(pair) || [];
        var dateField = items.find(function (field) {
            return (field.getAttribute('data-datetime-role') || field.type) === 'date';
        }) || null;
        var timeField = items.find(function (field) {
            var role = field.getAttribute('data-datetime-role') || field.type;
            return role === 'time' || role === 'time-range';
        }) || null;
        var now = getNowParts();

        if (!dateField) {
            return true;
        }

        if (!dateField.value) {
            return setFieldValidity(items, true, '');
        }

        if (dateField.value < now.date) {
            return setFieldValidity(items, false, buildMessage(now));
        }

        if (!timeField || !timeField.value) {
            return setFieldValidity(items, true, '');
        }

        var selectedTime = normalizeTimeValue(timeField.value);
        if (!selectedTime) {
            return setFieldValidity(items, true, '');
        }

        var isValid = dateField.value > now.date || selectedTime >= now.time;
        return setFieldValidity(items, isValid, isValid ? '' : buildMessage(now));
    }

    function buildMessage(now) {
        return invalidMessage + ' Current system time: ' + now.label + '.';
    }

    function setFieldValidity(targets, isValid, message) {
        var feedbackHost = targets[targets.length - 1] || targets[0];
        var feedback = ensureFeedback(feedbackHost);

        targets.forEach(function (target) {
            target.classList.toggle('is-invalid', !isValid);
            target.classList.toggle('datetime-future-invalid', !isValid);
            target.dataset.datetimeInvalid = isValid ? 'false' : 'true';
            target.setAttribute('aria-invalid', isValid ? 'false' : 'true');
        });

        if (feedback) {
            feedback.textContent = message;
        }

        return isValid;
    }

    function ensureFeedback(field) {
        var describedBy = field.getAttribute('aria-describedby') || '';
        var feedbackId = field.id ? field.id + '-datetime-feedback' : '';
        var existing = feedbackId ? document.getElementById(feedbackId) : null;
        if (existing) {
            return existing;
        }

        var fieldContainer = field.closest('[data-field]') || field.parentNode;
        if (fieldContainer) {
            var inlineError = fieldContainer.querySelector('.form-error, .form-group-error, .global-datetime-feedback');
            if (inlineError) {
                if (feedbackId && !inlineError.id) {
                    inlineError.id = feedbackId;
                }
                if (inlineError.id && describedBy.indexOf(inlineError.id) === -1) {
                    field.setAttribute('aria-describedby', (describedBy + ' ' + inlineError.id).trim());
                }
                return inlineError;
            }
        }

        var feedback = document.createElement('div');
        feedback.className = 'global-datetime-feedback';
        feedback.setAttribute('aria-live', 'polite');
        if (feedbackId) {
            feedback.id = feedbackId;
            if (describedBy.indexOf(feedbackId) === -1) {
                field.setAttribute('aria-describedby', (describedBy + ' ' + feedbackId).trim());
            }
        }

        if (field.parentNode) {
            field.parentNode.insertBefore(feedback, field.nextSibling);
        }

        return feedback;
    }
});
