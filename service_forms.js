document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('[data-service-form]');
    if (!forms.length) {
        return;
    }

    forms.forEach(function (form) {
        form.setAttribute('novalidate', 'novalidate');

        // Keep validation generic so all service forms share one accessible behavior layer.
        var fields = form.querySelectorAll('[data-field]');
        fields.forEach(function (field) {
            var input = field.querySelector('input, select, textarea');
            if (!input) {
                return;
            }

            var updateState = function () {
                validateField(input);
            };

            input.addEventListener('blur', updateState);
            input.addEventListener('input', updateState);
            input.addEventListener('change', updateState);
        });

        var groups = form.querySelectorAll('[data-require-one]');
        groups.forEach(function (group) {
            group.querySelectorAll('input').forEach(function (input) {
                input.addEventListener('change', function () {
                    validateChoiceGroup(group);
                });
            });
        });

        form.querySelectorAll('[data-age-target]').forEach(function (input) {
            input.addEventListener('change', function () {
                var targetName = input.getAttribute('data-age-target');
                if (!targetName || !input.value) {
                    return;
                }

                var target = form.querySelector('[name="' + targetName + '"]');
                if (!target || (target.value || '').trim() !== '') {
                    return;
                }

                var age = computeAge(input.value);
                if (age !== null) {
                    target.value = String(age);
                    validateField(target);
                }
            });
        });

        form.addEventListener('submit', function (event) {
            var firstInvalid = null;

            fields.forEach(function (field) {
                var input = field.querySelector('input, select, textarea');
                if (!input) {
                    return;
                }

                if (!validateField(input) && !firstInvalid) {
                    firstInvalid = input;
                }
            });

            groups.forEach(function (group) {
                if (!validateChoiceGroup(group) && !firstInvalid) {
                    firstInvalid = group.querySelector('input');
                }
            });

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });

    function computeAge(value) {
        var birthDate = new Date(value + 'T00:00:00');
        if (Number.isNaN(birthDate.getTime())) {
            return null;
        }

        var now = new Date();
        var age = now.getFullYear() - birthDate.getFullYear();
        var monthDiff = now.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birthDate.getDate())) {
            age -= 1;
        }

        return age >= 0 ? age : null;
    }

    function validateField(input) {
        var field = input.closest('[data-field]');
        if (!field) {
            return true;
        }

        var error = field.querySelector('.form-error');
        var message = '';

        if (input.validity.valueMissing) {
            message = input.getAttribute('data-required-message') || 'Please complete this field.';
        } else if (input.validity.typeMismatch || input.validity.badInput) {
            message = input.getAttribute('data-type-message') || 'Please enter a valid value.';
        } else if (input.validity.patternMismatch) {
            message = input.getAttribute('data-pattern-message') || 'Please match the expected format.';
        } else if (input.validity.rangeOverflow || input.validity.rangeUnderflow) {
            message = input.getAttribute('data-range-message') || 'Please enter a valid range.';
        } else if (input.validity.tooShort) {
            message = input.getAttribute('data-length-message') || 'Please add a little more detail.';
        }

        field.classList.toggle('is-invalid', message !== '');
        input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');

        if (error) {
            error.textContent = message;
        }

        return message === '';
    }

    function validateChoiceGroup(group) {
        var inputs = group.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        if (!inputs.length) {
            return true;
        }

        var isChecked = Array.prototype.some.call(inputs, function (input) {
            return input.checked;
        });
        var message = isChecked ? '' : (group.getAttribute('data-group-message') || 'Please choose at least one option.');
        var error = group.querySelector('.form-group-error');

        group.classList.toggle('is-invalid', message !== '');
        inputs.forEach(function (input) {
            input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
        });
        if (error) {
            error.textContent = message;
        }

        return message === '';
    }
});
