/* Shared nav + footer injection + behaviors */
(function(){
  const path = location.pathname.split('/').pop() || 'index.html';

  const navHTML = `
  <nav class="topnav" id="topnav">
    <a href="/" class="logo-mark" aria-label="S.T.R.In.G">
      <img src="assets/string-logo.png" alt="S.T.R.In.G"/>
      <span class="acc-dot"></span>
    </a>
    <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="primaryNav">
      <span></span><span></span><span></span>
    </button>
    <div class="nav-links" id="primaryNav">
      <a href="/services" class="nav-link" data-r="services">Services</a>
      <a href="/solutions" class="nav-link" data-r="solutions">Solutions</a>
      <a href="/work" class="nav-link" data-r="work">Work</a>
      <a href="/industries" class="nav-link" data-r="industries">Industries</a>
      <a href="/about" class="nav-link" data-r="about">About</a>
      <a href="/insights" class="nav-link" data-r="insights">Insights</a>
      <a href="/book" class="nav-link nav-mobile-cta">Book a call</a>
    </div>
    <div class="nav-right">
      <a href="/book" class="btn primary">Book a call
        <svg class="arrow" viewBox="0 0 14 14" fill="none"><path d="M3 11L11 3M11 3H5M11 3V9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      </a>
    </div>
  </nav>`;

  const footHTML = `
  <footer>
    <div class="container">
      <div class="foot-grid">
        <div class="foot-brand">
          <a href="/" class="logo-mark" aria-label="S.T.R.In.G"><img src="assets/string-logo.png" alt="S.T.R.In.G"/></a>
          <p>Your trusted partner in innovation and technology — AI, data science, automation and engineering, shipped weekly from Mumbai to global teams.</p>
        </div>
        <div>
          <h5>Services</h5>
          <ul>
            <li><a href="/service-ai">AI & Machine Learning</a></li>
            <li><a href="/service-automation">Workflow Automation</a></li>
            <li><a href="/service-data">Data & Analytics</a></li>
            <li><a href="/service-product">Product Engineering</a></li>
            <li><a href="/service-or">Operations Research</a></li>
            <li><a href="/service-or">UI/UX & Design</a></li>
          </ul>
        </div>
        <div>
          <h5>Solutions</h5>
          <ul>
            <li><a href="/solution-muneem">Digital Muneem</a></li>
            <li><a href="/solution-edtech">Learning Engine</a></li>
            <li><a href="/solution-remit">Remit</a></li>
            <li><a href="/solution-forecast">Forecast Kit</a></li>
            <li><a href="/solution-stocksense">Stock Sense</a></li>
            <li><a href="/solution-docreader">Document Reader</a></li>
          </ul>
        </div>
        <div>
          <h5>Company</h5>
          <ul>
            <li><a href="/about">About</a></li>
            <li><a href="/team">Team</a></li>
            <li><a href="/careers">Careers</a></li>
            <li><a href="/insights">Insights</a></li>
            <li><a href="/press">Press</a></li>
            <li><a href="/contact">Contact</a></li>
          </ul>
        </div>
        <div class="foot-news">
          <h5>Contact</h5>
          <p style="font-size:13px; color:#a8a59c; line-height:1.5;">B/29, Plot No. 98, Sai Darshan, Gorai-1, Borivali West, Mumbai 400091.</p>
          <p style="font-family:var(--mono);font-size:12px;line-height:1.8;margin-top:10px;">
            <a href="tel:+919769628463">+91 97696 28463</a><br/>
            <a href="mailto:info@stringlab.org">info@stringlab.org</a>
          </p>
          <form class="newsletter-inline" data-form-handler data-form-type="callback" data-section="Footer Callback" data-default-first-name="Footer" data-default-last-name="Callback" data-default-message="Footer callback request" data-loading-label="Sending..." data-success-message="Thanks. We have your email and will reach out shortly.">
            <input type="email" name="email" placeholder="your email for a callback" aria-label="Email for a callback" required />
            <button type="submit" class="btn primary form-submit">→</button>
          </form>
          <span class="form-status" data-form-status aria-live="polite"></span>
        </div>
      </div>
      <div class="foot-bot">
        <span>© 2026 S.T.R.In.G Technology Solutions</span>
        <span style="display:flex; gap:20px;">
          <a href="/privacy">Privacy</a>
          <a href="/terms">Terms</a>
          <a href="/cookies">Cookies</a>
          <a href="/trust">Trust</a>
          <a href="/faq">FAQ</a>
        </span>
        <span>v.2026.04</span>
      </div>
    </div>
    <span class="foot-wm" aria-hidden="true">S<span class="green">.</span>T<span class="green">.</span>R<span class="green">.</span>In<span class="green">.</span>G</span>
  </footer>`;

  // Inject
  const navSlot = document.getElementById('nav-slot');
  const footSlot = document.getElementById('footer-slot');
  if (navSlot) navSlot.outerHTML = navHTML;
  if (footSlot) footSlot.outerHTML = footHTML;

  // Active link
  const route = (document.body.dataset.route || '').toLowerCase();
  document.querySelectorAll('.nav-link').forEach(l => {
    if (l.dataset.r === route) l.classList.add('active');
  });

  // Mobile menu
  const navToggle = document.querySelector('.nav-toggle');
  const navLinks = document.getElementById('primaryNav');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      const isOpen = document.body.classList.toggle('nav-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
      navToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    });
    navLinks.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        document.body.classList.remove('nav-open');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open navigation');
      });
    });
  }

  // Scroll + paper detection
  const nav = document.getElementById('topnav');
  function onScroll(){
    const y = window.scrollY;
    nav.classList.toggle('scrolled', y > 20);
    let onPaper = false;
    document.querySelectorAll('.paper-bg').forEach(s => {
      const r = s.getBoundingClientRect();
      if (r.top <= 60 && r.bottom >= 60) onPaper = true;
    });
    nav.classList.toggle('on-paper', onPaper);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Reveal
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
  }, { threshold: 0.12 });
  document.querySelectorAll('.card, .sec-head > *').forEach(el => io.observe(el));
})();
