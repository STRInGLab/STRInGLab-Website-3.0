# StringLab SEO Baseline Reporting

`tools/seo_baseline_report.py` generates a segmented SEO baseline from:

- Google Search Console domain property: `sc-domain:stringlab.org`
- GA4 property: `411599658`
- Live sitemap crawl: `https://stringlab.org/sitemap.xml`

The script intentionally keeps OAuth secrets and generated analytics reports out of git.

## Manual access needed

Hermes can run the script end-to-end if the following private files exist on the runner:

```bash
export STRINGLAB_GOOGLE_CLIENT=/secure/path/stringlabseo_oauth_client.json
export STRINGLAB_GOOGLE_TOKEN=/secure/path/stringlabseo_google_token.json
```

If OAuth fails, manually verify that the operating Google account has:

| Product | Required access |
|---|---|
| Google Search Console | `stringlab.org` domain property, owner/full access |
| GA4 | `properties/411599658`, analyst/viewer or higher |
| Google Cloud OAuth | Search Console API, Analytics Data API enabled |

No website deploy access is required for the baseline report.

## Run

```bash
python3 -m pip install requests
STRINGLAB_GOOGLE_CLIENT=/secure/path/stringlabseo_oauth_client.json \
STRINGLAB_GOOGLE_TOKEN=/secure/path/stringlabseo_google_token.json \
python3 tools/seo_baseline_report.py
```

Optional flags:

```bash
python3 tools/seo_baseline_report.py \
  --days 28 \
  --crawl-limit 120 \
  --corporate-host stringlab.org \
  --corporate-host www.stringlab.org \
  --excluded-host shine.stringlab.org
```

## Outputs

Generated under `seo-reports/` by default:

| File | Purpose |
|---|---|
| `stringlab-seo-baseline-*.md` | Human-readable baseline summary |
| `stringlab-seo-baseline-*.json` | Full structured report |
| `stringlab-gsc-pages-*.csv` | GSC pages with segment labels |
| `stringlab-gsc-queries-*.csv` | GSC query table |
| `stringlab-ga4-landing-*.csv` | GA4 landing pages by hostname |
| `stringlab-crawl-*.csv` | Sitemap crawl metadata |

`seo-reports/` is gitignored because it may contain proprietary analytics/search data.

## Segmentation policy

| Segment | Hosts |
|---|---|
| `corporate` | `stringlab.org`, `www.stringlab.org` |
| `shine.stringlab.org` | reported separately/excluded from corporate SEO reads |
| `other_subdomain` | any other `*.stringlab.org` host |
| `external_or_other` | unexpected external/non-StringLab URLs |

This prevents Shine Academy traffic/query data from polluting StringLab corporate SEO decisions.
