"use strict";
var KTAppInvoicesCreate = (function () {
  var e,
    recalcTimer,
    n = function () {
      var t = [].slice.call(
          e.querySelectorAll(
            '[data-kt-element="items"] [data-kt-element="item"]'
          )
        ),
        a = 0,
        fmt = wNumb({ decimals: 2, thousand: "," });

      t.map(function (row) {
        var qtyEl = row.querySelector('[data-kt-element="quantity"]'),
          priceEl = row.querySelector('[data-kt-element="price"]'),
          discountEl = row.querySelector('[data-kt-element="discount"]'),
          price = fmt.from(priceEl.value) || 0,
          discount = fmt.from(discountEl.value) || 0;

        price = price < 0 ? 0 : price;
        discount = discount < 0 ? 0 : discount;

        var qty = parseInt(qtyEl.value, 10);
        qty = !qty || qty < 1 ? 1 : qty;

        priceEl.value = fmt.to(price);
        discountEl.value = fmt.to(discount);
        qtyEl.value = qty;

        var total = price * qty - discount;
        total = total < 0 ? 0 : total;

        row.querySelector('[data-kt-element="total"]').innerText = fmt.to(total);
        a += total;
      });

      var subtotal = a;
      var advancePaid = fmt.from(e.querySelector('[data-kt-element="advance_amt"]').value) || 0;

      // Use the dedicated tax_type select (not the invoice_type select).
      var cfg = window.invoiceTaxConfig || { intraState: "intra-state", interState: "inter-state", unionTerritory: "union-teritory", igstRate: 0.18 };
      var taxOption = e.querySelector('[name="tax_type"]').value;
      var tax = 0;

      if (taxOption === cfg.intraState || taxOption === cfg.interState || taxOption === cfg.unionTerritory) {
        tax = subtotal * cfg.igstRate;
      }

      var grandTotal = subtotal + tax - advancePaid;
      grandTotal = grandTotal < 0 ? 0 : grandTotal;

      e.querySelector('[data-kt-element="sub-total"]').innerText = fmt.to(subtotal);
      e.querySelector('[data-kt-element="grand-total"]').innerText = fmt.to(grandTotal);
    },
    debouncedRecalc = function () {
      clearTimeout(recalcTimer);
      recalcTimer = setTimeout(n, 50);
    },
    a = function () {
      if (
        0 ===
        e.querySelectorAll('[data-kt-element="items"] [data-kt-element="item"]')
          .length
      ) {
        var t = e
          .querySelector('[data-kt-element="empty-template"] tr')
          .cloneNode(!0);
        e.querySelector('[data-kt-element="items"] tbody').appendChild(t);
      } else
        KTUtil.remove(
          e.querySelector('[data-kt-element="items"] [data-kt-element="empty"]')
        );
    };
  return {
    init: function () {
      e = document.querySelector("#kt_invoice_form");

      e.querySelector('[data-kt-element="items"] [data-kt-element="add-item"]')
        .addEventListener("click", function (evt) {
          evt.preventDefault();
          var l = e
            .querySelector('[data-kt-element="item-template"] tr')
            .cloneNode(!0);
          e.querySelector('[data-kt-element="items"] tbody').appendChild(l);
          a();
          n();
        });

      KTUtil.on(
        e,
        '[data-kt-element="items"] [data-kt-element="remove-item"]',
        "click",
        function (evt) {
          evt.preventDefault();
          KTUtil.remove(this.closest('[data-kt-element="item"]'));
          a();
          n();
        }
      );

      KTUtil.on(
        e,
        '[data-kt-element="items"] [data-kt-element="quantity"], [data-kt-element="items"] [data-kt-element="price"], [data-kt-element="items"] [data-kt-element="discount"], [data-kt-element="advance_amt"], [name="tax_type"]',
        "input",
        function (evt) {
          evt.preventDefault();
          debouncedRecalc();
        }
      );

      // Tax type select fires change, not input.
      KTUtil.on(e, '[name="tax_type"]', "change", function (evt) {
        evt.preventDefault();
        n();
      });

      var dateEl = e.querySelector('[name="invoice_date"]');
      $(dateEl).flatpickr({
        enableTime: !1,
        dateFormat: "d, M Y",
        defaultDate: dateEl.value || new Date(),
      });
      var dueEl = e.querySelector('[name="invoice_due_date"]');
      $(dueEl).flatpickr({
        enableTime: !1,
        dateFormat: "d, M Y",
        defaultDate: dueEl.value || new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
      });

      n();
    },
  };
})();
KTUtil.onDOMContentLoaded(function () {
  KTAppInvoicesCreate.init();
});
