(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('.dn-page');
    if (!page) return;

    if (page.querySelector('.dn-fab')) {
      page.classList.add('dn-has-fab');
    }
  });
})();
