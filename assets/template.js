/* Tiny page template helper: builds a generic hub/detail page from JSON */
function stringPage(opts) {
  const { route, crumbs, eyebrow, title, sub, meta, body, bg = 'dark' } = opts;
  const bgCls = bg === 'paper' ? 'paper-bg' : '';
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>${title.replace(/<[^>]+>/g,'')} · S.T.R.In.G</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,300;0,6..72,400;0,6..72,500;1,6..72,400&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="assets/shared.min.css"/>
</head>
<body data-route="${route}">
<div id="nav-slot"></div>
<section class="pg-hero ${bgCls}" data-screen-label="${title}">
  <div class="container">
    <div class="crumbs">${crumbs}</div>
    <span class="label">${eyebrow}</span>
    <h1 class="display" style="margin-top:20px;">${title}</h1>
    ${sub ? `<p class="sub">${sub}</p>` : ''}
    ${meta ? `<div class="meta-row">${meta}</div>` : ''}
  </div>
</section>
${body}
<div id="footer-slot"></div>
<script src="assets/partials.min.js"></script>
</body>
</html>`;
}
