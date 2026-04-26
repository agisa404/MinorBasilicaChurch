(function () {
    var activeKeys = new Set();
    var toastStack = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function ensureToastStack() {
        if (toastStack && document.body.contains(toastStack)) {
            return toastStack;
        }

        if (!document.body) {
            throw new Error('Document body is not ready.');
        }

        toastStack = document.createElement('div');
        toastStack.className = 'app-toast-stack';
        toastStack.setAttribute('aria-live', 'polite');
        toastStack.setAttribute('aria-atomic', 'true');
        document.body.appendChild(toastStack);
        return toastStack;
    }

    function releaseToast(toast, key) {
        if (!toast) {
            return;
        }

        activeKeys.delete(key);
        toast.classList.add('is-hiding');
        window.setTimeout(function () {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 180);
    }

    function showNotification(options) {
        var settings = options || {};
        var type = String(settings.type || 'info').toLowerCase();
        var message = String(settings.message || '').trim();
        var duration = Number(settings.duration || (type === 'danger' ? 6500 : 4500));
        var fallback = settings.fallback !== false;

        if (!message) {
            return false;
        }

        try {
            var stack = ensureToastStack();
            var key = type + '::' + message;
            if (activeKeys.has(key)) {
                return true;
            }

            activeKeys.add(key);

            var toast = document.createElement('div');
            toast.className = 'app-toast app-toast-' + type;
            toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');
            toast.innerHTML =
                '<div class="app-toast-copy">' +
                    '<strong class="app-toast-title">' + escapeHtml(type.charAt(0).toUpperCase() + type.slice(1)) + '</strong>' +
                    '<div class="app-toast-message">' + escapeHtml(message) + '</div>' +
                '</div>' +
                '<button class="app-toast-close" type="button" aria-label="Dismiss notification">&times;</button>';

            var closed = false;
            var closeToast = function () {
                if (closed) {
                    return;
                }
                closed = true;
                releaseToast(toast, key);
            };

            toast.querySelector('.app-toast-close').addEventListener('click', closeToast);
            stack.appendChild(toast);

            window.setTimeout(closeToast, duration);
            return true;
        } catch (error) {
            if (fallback && typeof window.alert === 'function') {
                window.alert(message);
            }
            return false;
        }
    }

    function bootFlashNotification() {
        var flash = document.querySelector('[data-flash-notification="true"]');
        if (!flash) {
            return;
        }

        var shown = showNotification({
            type: flash.getAttribute('data-flash-type') || 'info',
            message: flash.getAttribute('data-flash-message') || flash.textContent || '',
            fallback: false
        });

        if (shown) {
            flash.hidden = true;
            flash.classList.add('d-none');
        }
    }

    window.AppNotifications = {
        show: showNotification
    };

    document.addEventListener('DOMContentLoaded', bootFlashNotification);
})();
