document.addEventListener('DOMContentLoaded', function () {
    var DEFAULT_CONFIRM_MESSAGE = 'Are you sure you want to proceed?';
    var DEFAULT_CANCEL_MESSAGE = 'Action cancelled.';

    function notify(type, message, suppressAlerts) {
        if (suppressAlerts || !message) {
            return;
        }

        if (window.AppNotifications && typeof window.AppNotifications.show === 'function') {
            var shown = window.AppNotifications.show({
                type: type,
                message: message
            });
            if (shown) {
                return;
            }
        }

        if (typeof window.alert === 'function') {
            window.alert(message);
        }
    }

    function getActionMessages(form, submitter) {
        var suppressAlerts = ((submitter && submitter.dataset.suppressAlerts) || form.dataset.suppressAlerts || 'false') === 'true';
        return {
            confirmMessage: (submitter && submitter.dataset.confirmMessage) || form.dataset.confirmMessage || DEFAULT_CONFIRM_MESSAGE,
            cancelMessage: (submitter && submitter.dataset.cancelMessage) || form.dataset.cancelMessage || DEFAULT_CANCEL_MESSAGE,
            suppressAlerts: suppressAlerts
        };
    }

    function requiresConfirmation(form, submitter) {
        return Boolean(
            (submitter && submitter.dataset.confirmMessage) ||
            form.dataset.confirmMessage
        );
    }

    function handleActionConfirmation(options, onProceed) {
        if (window.confirm(options.confirmMessage)) {
            onProceed();
            return true;
        }

        notify('warning', options.cancelMessage, options.suppressAlerts);
        return false;
    }

    window.handleActionConfirmation = handleActionConfirmation;

    document.querySelectorAll('form').forEach(function (form) {
        if (form.hasAttribute('data-confirm-ignore')) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmApproved === 'true') {
                delete form.dataset.confirmApproved;
                return;
            }

            var submitter = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitter && submitter.hasAttribute('data-confirm-ignore')) {
                return;
            }

            if (!requiresConfirmation(form, submitter)) {
                return;
            }

            event.preventDefault();

            var options = getActionMessages(form, submitter);
            handleActionConfirmation(options, function () {
                form.dataset.confirmApproved = 'true';

                if (submitter && typeof form.requestSubmit === 'function') {
                    form.requestSubmit(submitter);
                    return;
                }

                form.submit();
            });
        });
    });

    document.querySelectorAll('[data-confirm-action]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (element.dataset.confirmApproved === 'true') {
                delete element.dataset.confirmApproved;
                return;
            }

            event.preventDefault();

            handleActionConfirmation({
                confirmMessage: element.dataset.confirmMessage || DEFAULT_CONFIRM_MESSAGE,
                cancelMessage: element.dataset.cancelMessage || DEFAULT_CANCEL_MESSAGE
            }, function () {
                element.dataset.confirmApproved = 'true';

                if (typeof element.click === 'function' && element.tagName === 'A') {
                    window.location.href = element.href;
                    return;
                }

                element.click();
            });
        });
    });
});
