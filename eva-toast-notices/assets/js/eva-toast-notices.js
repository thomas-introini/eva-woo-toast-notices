/* global Toastify */
(function () {
  'use strict';

  function getData() {
    if (typeof window.EvaToastNoticesData !== 'object' || !window.EvaToastNoticesData) {
      return { notices: [], settings: {} };
    }
    var notices = Array.isArray(window.EvaToastNoticesData.notices)
      ? window.EvaToastNoticesData.notices
      : [];
    var settings = typeof window.EvaToastNoticesData.settings === 'object' && window.EvaToastNoticesData.settings
      ? window.EvaToastNoticesData.settings
      : {};
    return { notices: notices, settings: settings };
  }

  function mapTypeToStyle(type) {
    switch (type) {
      case 'success':
        return {
          backgroundColor: '#198754',
          ariaRole: 'status',
          ariaLive: 'polite'
        };
      case 'error':
        return {
          backgroundColor: '#dc3545',
          ariaRole: 'alert',
          ariaLive: 'assertive'
        };
      case 'warning':
        return {
          backgroundColor: '#fd7e14',
          ariaRole: 'status',
          ariaLive: 'polite'
        };
      case 'info':
      default:
        return {
          backgroundColor: '#0d6efd',
          ariaRole: 'status',
          ariaLive: 'polite'
        };
    }
  }

  function parsePosition(position) {
    position = position || 'top-right';
    var parts = position.split('-');
    return {
      gravity: parts[0] === 'bottom' ? 'bottom' : 'top',
      position: parts[1] === 'left' ? 'left' : 'right'
    };
  }

  function showToasts() {
    var data = getData();
    if (!data.notices.length || typeof Toastify !== 'function') {
      return;
    }

    var timeoutNonError = typeof data.settings.timeoutNonError === 'number'
      ? data.settings.timeoutNonError
      : 5000;
    var errorTimeout = typeof data.settings.errorTimeout === 'number'
      ? data.settings.errorTimeout
      : Math.max(timeoutNonError, 8000);
    var pos = parsePosition(data.settings.position);

    // Stagger toasts a bit to avoid sudden bursts.
    var baseDelay = 250;

    data.notices.forEach(function (notice, index) {
      if (!notice || typeof notice.message !== 'string') {
        return;
      }

      var type = notice.type || 'info';
      var style = mapTypeToStyle(type);
      var isError = type === 'error';

      var duration = isError ? errorTimeout : timeoutNonError;
      if (duration < 0) {
        duration = 0;
      }

      var delay = baseDelay * index;

      window.setTimeout(function () {
        Toastify({
          text: notice.message,
          duration: duration,
          gravity: pos.gravity,
          position: pos.position,
          close: !!notice.dismissible || isError,
          backgroundColor: style.backgroundColor,
          ariaRole: style.ariaRole,
          ariaLive: style.ariaLive,
          stopOnFocus: true,
          escapeMarkup: false
        }).showToast();
      }, delay);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', showToasts);
  } else {
    showToasts();
  }
}());


