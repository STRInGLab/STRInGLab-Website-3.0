(function () {
  const GOOGLE_TAG_ID = "G-SQW6G6NF3G";
  const RECAPTCHA_SITE_KEY = "6LewlTEoAAAAALZdv6G9WCyPvKve5I6D8Ql-2URf";
  const FORM_ENDPOINT = "php/send_email.php";
  const RECAPTCHA_SCRIPT_ID = "recaptcha-api-script";

  function ensureHeadLinks() {
    const head = document.head;
    if (!head) return;

    if (!head.querySelector('link[rel="icon"][href="assets/favicon.png"]')) {
      const icon = document.createElement("link");
      icon.rel = "icon";
      icon.type = "image/png";
      icon.href = "assets/favicon.png";
      head.appendChild(icon);
    }

    if (!head.querySelector('link[rel="apple-touch-icon"][href="assets/favicon.png"]')) {
      const touch = document.createElement("link");
      touch.rel = "apple-touch-icon";
      touch.href = "assets/favicon.png";
      head.appendChild(touch);
    }
  }

  function ensureGoogleTag() {
    if (window.gtag || document.getElementById("google-tag-script")) return;

    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
      window.dataLayer.push(arguments);
    };
    window.gtag("js", new Date());
    window.gtag("config", GOOGLE_TAG_ID);

    const script = document.createElement("script");
    script.id = "google-tag-script";
    script.async = true;
    script.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(GOOGLE_TAG_ID);
    document.head.appendChild(script);
  }

  function ensureRecaptcha() {
    if (window.grecaptcha || document.getElementById(RECAPTCHA_SCRIPT_ID)) return;
    const script = document.createElement("script");
    script.id = RECAPTCHA_SCRIPT_ID;
    script.async = true;
    script.defer = true;
    script.src = "https://www.google.com/recaptcha/api.js?render=explicit";
    document.head.appendChild(script);
  }

  function setStatus(form, kind, message) {
    const status = form.querySelector("[data-form-status]");
    if (!status) return;
    status.textContent = message;
    status.className = "form-status is-visible " + (kind === "success" ? "is-success" : "is-error");
  }

  function clearStatus(form) {
    const status = form.querySelector("[data-form-status]");
    if (!status) return;
    status.textContent = "";
    status.className = "form-status";
  }

  function createHiddenField(name, value) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    return input;
  }

  function ensureSpamFields(form) {
    if (!form.querySelector('input[name="action"]')) {
      form.appendChild(createHiddenField("action", "sendEmail"));
    }
    if (!form.querySelector('input[name="form_loaded_time"]')) {
      form.appendChild(createHiddenField("form_loaded_time", String(Date.now())));
    }
    if (!form.querySelector('input[name="page_source"]')) {
      form.appendChild(createHiddenField("page_source", window.location.href));
    }

    [
      ["website", ""],
      ["backup_email", ""],
      ["fax_number", ""]
    ].forEach(([name, value]) => {
      if (form.querySelector('input[name="' + name + '"]')) return;
      const field = createHiddenField(name, value);
      field.className = "form-honeypot";
      field.tabIndex = -1;
      field.setAttribute("autocomplete", "off");
      form.appendChild(field);
    });
  }

  function mapField(form, selectors, targetName) {
    const existing = form.querySelector('[name="' + targetName + '"]');
    if (existing) return existing;
    for (const selector of selectors) {
      const input = form.querySelector(selector);
      if (input) {
        input.name = targetName;
        return input;
      }
    }
    return null;
  }

  function renderRecaptcha(container) {
    if (!container) return Promise.resolve(null);

    ensureRecaptcha();

    return new Promise((resolve) => {
      let attempts = 0;
      const timer = window.setInterval(() => {
        attempts += 1;
        if (window.grecaptcha && typeof window.grecaptcha.render === "function") {
          window.clearInterval(timer);
          if (container.dataset.widgetId) {
            resolve(container.dataset.widgetId);
            return;
          }
          const widgetId = window.grecaptcha.render(container, {
            sitekey: RECAPTCHA_SITE_KEY,
            theme: "dark"
          });
          container.dataset.widgetId = String(widgetId);
          resolve(String(widgetId));
          return;
        }
        if (attempts > 100) {
          window.clearInterval(timer);
          resolve(null);
        }
      }, 150);
    });
  }

  function serialiseForm(form) {
    const data = new FormData(form);
    return data;
  }

  function resetRecaptcha(form) {
    const container = form.querySelector(".g-recaptcha");
    if (!container || !window.grecaptcha || !container.dataset.widgetId) return;
    window.grecaptcha.reset(Number(container.dataset.widgetId));
  }

  function appendMessageDetails(formData, extras) {
    const baseMessage = formData.get("con_message") || "";
    const detailLines = extras.filter(Boolean);
    if (!detailLines.length) return;
    const combined = [baseMessage, "", detailLines.join("\n")].filter(Boolean).join("\n");
    formData.set("con_message", combined);
  }

  function normaliseForm(form) {
    const formType = form.dataset.formType || "contact";
    const section = form.dataset.section || "Website Form";

    ensureSpamFields(form);

    const firstName = mapField(form, ['[name="fname"]', '[name="first_name"]', '[name="con_fname"]'], "con_fname");
    const lastName = mapField(form, ['[name="lname"]', '[name="last_name"]', '[name="con_lname"]'], "con_lname");
    const phone = mapField(form, ['[name="phone"]', '[name="con_phone"]'], "con_phone");
    const email = mapField(form, ['[name="email"]', '[name="con_email"]'], "con_email");
    const message = mapField(form, ['[name="message"]', '[name="con_message"]'], "con_message");

    if (!firstName && formType !== "contact") {
      form.appendChild(createHiddenField("con_fname", form.dataset.defaultFirstName || "Website"));
    }
    if (!lastName && formType !== "contact") {
      form.appendChild(createHiddenField("con_lname", form.dataset.defaultLastName || "Lead"));
    }
    if (!phone && formType !== "contact") {
      form.appendChild(createHiddenField("con_phone", form.dataset.defaultPhone || "0000000000"));
    }
    if (!message && formType !== "contact") {
      form.appendChild(createHiddenField("con_message", form.dataset.defaultMessage || "Newsletter or callback signup"));
    }

    let sectionField = form.querySelector('[name="section"]');
    if (!sectionField) {
      sectionField = createHiddenField("section", section);
      form.appendChild(sectionField);
    } else {
      sectionField.value = section;
    }

    let typeField = form.querySelector('[name="form_type"]');
    if (!typeField) {
      typeField = createHiddenField("form_type", formType);
      form.appendChild(typeField);
    } else {
      typeField.value = formType;
    }
  }

  async function submitForm(form) {
    clearStatus(form);
    normaliseForm(form);

    const submit = form.querySelector('[type="submit"]');
    const originalLabel = submit ? submit.textContent : "";
    if (submit) {
      submit.disabled = true;
      submit.classList.add("form-submit");
      submit.textContent = form.dataset.loadingLabel || "Sending...";
    }

    const recaptchaContainer = form.querySelector(".g-recaptcha");
    if (recaptchaContainer) {
      const widgetId = await renderRecaptcha(recaptchaContainer);
      if (widgetId === null) {
        if (submit) {
          submit.disabled = false;
          submit.textContent = originalLabel;
        }
        setStatus(form, "error", "Captcha could not load. Please refresh and try again.");
        return;
      }
      const token = window.grecaptcha ? window.grecaptcha.getResponse(Number(widgetId)) : "";
      if (!token) {
        if (submit) {
          submit.disabled = false;
          submit.textContent = originalLabel;
        }
        setStatus(form, "error", "Please complete the captcha before submitting.");
        return;
      }
    }

    const formData = serialiseForm(form);
    const company = form.querySelector('[name="company"]')?.value?.trim();
    const interest = form.querySelector('[name="interest"]')?.value?.trim();

    if (company || interest) {
      appendMessageDetails(formData, [
        company ? "Company: " + company : "",
        interest ? "Interest: " + interest : ""
      ]);
    }

    try {
      const response = await fetch(FORM_ENDPOINT, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        },
        body: formData
      });

      let result = null;
      try {
        result = await response.json();
      } catch (error) {
        result = null;
      }

      if (!response.ok || !result || result.response !== "success") {
        const message = result?.message || "We couldn't submit the form just now. Please try again.";
        throw new Error(message);
      }

      setStatus(form, "success", result.message || form.dataset.successMessage || "Thanks. Your submission is in.");
      form.reset();
      form.querySelectorAll('input[name="form_loaded_time"]').forEach((field) => {
        field.value = String(Date.now());
      });
      resetRecaptcha(form);

      if (window.gtag) {
        window.gtag("event", "generate_lead", {
          form_type: form.dataset.formType || "contact",
          section: form.dataset.section || "Website Form"
        });
      }
    } catch (error) {
      setStatus(form, "error", error.message || "Something went wrong. Please try again.");
      resetRecaptcha(form);
    } finally {
      if (submit) {
        submit.disabled = false;
        submit.textContent = originalLabel;
      }
    }
  }

  function initForms() {
    const forms = document.querySelectorAll("[data-form-handler]");
    forms.forEach((form) => {
      normaliseForm(form);
      if (form.dataset.recaptcha === "required") {
        renderRecaptcha(form.querySelector(".g-recaptcha"));
      }
      form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitForm(form);
      });
    });
  }

  ensureHeadLinks();
  ensureGoogleTag();

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initForms, { once: true });
  } else {
    initForms();
  }
})();
