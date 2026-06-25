/**
 * Global TMS currency formatter — reads window.TMS_CURRENCY from PHP config.
 */
(function (global) {
  'use strict';

  global.TMS = global.TMS || {};

  function cfg() {
    return global.TMS_CURRENCY || {
      symbol: 'LKR',
      locale: 'en-LK',
      decimals: 2,
    };
  }

  function formatAmount(n, withSymbol) {
    var c = cfg();
    var decimals = typeof c.decimals === 'number' ? c.decimals : 2;
    var formatted = (parseFloat(n) || 0).toLocaleString(c.locale || 'en-LK', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    });
    if (withSymbol === false) {
      return formatted;
    }
    return (c.symbol || 'LKR') + ' ' + formatted;
  }

  global.TMS.formatMoney = formatAmount;
  global.TMS.currencySymbol = function () {
    return cfg().symbol || 'LKR';
  };
})(typeof window !== 'undefined' ? window : this);
