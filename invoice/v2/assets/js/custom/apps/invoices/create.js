"use strict";
var KTAppInvoicesCreate = (function () {
  var e,
    t = function () {
      var t = [].slice.call(
          e.querySelectorAll(
            '[data-kt-element="items"] [data-kt-element="item"]'
          )
        ),
        a = 0,
        n = wNumb({ decimals: 2, thousand: "," });

      t.map(function (e) {
        var t = e.querySelector('[data-kt-element="quantity"]'),
          l = e.querySelector('[data-kt-element="price"]'),
          d = e.querySelector('[data-kt-element="discount"]'),
          r = n.from(l.value),
          discount = n.from(d.value);
          
        r = !r || r < 0 ? 0 : r;
        discount = !discount || discount < 0 ? 0 : discount;

        var i = parseInt(t.value);
        (i = !i || i < 0 ? 1 : i),
          (l.value = n.to(r)),
          (d.value = n.to(discount)),
          (t.value = i);
          
        var total = (r * i) - discount;
        total = total < 0 ? 0 : total;
        
        e.querySelector('[data-kt-element="total"]').innerText = n.to(total);
        a += total;
      });

      var subtotal = a;
      var advancePaid = n.from(e.querySelector('[data-kt-element="advance_amt"]').value) || 0;
      
      // Retrieve the selected tax option
      var taxOption = e.querySelector('[data-kt-element="tax-select"]').value;
      var tax = 0;

      if (taxOption === "inter_state" || taxOption === "intra_state" || taxOption === "union_teritory") {
        tax = subtotal * 0.18;
      }

      var grandTotal = subtotal + tax - advancePaid;

      e.querySelector('[data-kt-element="sub-total"]').innerText = n.to(subtotal);
      e.querySelector('[data-kt-element="grand-total"]').innerText = n.to(grandTotal);
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
    init: function (n) {
      (e = document.querySelector("#kt_invoice_form"))
        .querySelector('[data-kt-element="items"] [data-kt-element="add-item"]')
        .addEventListener("click", function (n) {
          n.preventDefault();
          var l = e
            .querySelector('[data-kt-element="item-template"] tr')
            .cloneNode(!0);
          e.querySelector('[data-kt-element="items"] tbody').appendChild(l),
            a(),
            t();
        }),
        KTUtil.on(
          e,
          '[data-kt-element="items"] [data-kt-element="remove-item"]',
          "click",
          function (e) {
            e.preventDefault(),
              KTUtil.remove(this.closest('[data-kt-element="item"]')),
              a(),
              t();
          }
        ),
        KTUtil.on(
          e,
          '[data-kt-element="items"] [data-kt-element="quantity"], [data-kt-element="items"] [data-kt-element="price"], [data-kt-element="items"] [data-kt-element="discount"], [data-kt-element="advance_amt"], [data-kt-element="tax-select"]',
          "change",
          function (e) {
            e.preventDefault(), t();
          }
        ),
        $(e.querySelector('[name="invoice_date"]')).flatpickr({
          enableTime: !1,
          dateFormat: "d, M Y",
        }),
        $(e.querySelector('[name="invoice_due_date"]')).flatpickr({
          enableTime: !1,
          dateFormat: "d, M Y",
        }),
        t();
    },
  };
})();
KTUtil.onDOMContentLoaded(function () {
  KTAppInvoicesCreate.init();
});