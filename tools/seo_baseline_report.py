#!/usr/bin/env python3
"""Generate StringLab SEO baseline reports from GSC, GA4, and live crawl data.

Secrets are intentionally not stored in this repository. Provide OAuth client and
refresh-token files via environment variables, for example:

  STRINGLAB_GOOGLE_CLIENT=/secure/path/oauth_client.json \
  STRINGLAB_GOOGLE_TOKEN=/secure/path/google_token.json \
  python3 tools/seo_baseline_report.py

Outputs default to ./seo-reports/ and are gitignored because they contain
analytics/search-performance data.
"""
from __future__ import annotations

import argparse
import csv
import datetime as dt
import json
import os
import re
import sys
import time
import urllib.parse
import urllib.request
from collections import Counter, defaultdict
from html.parser import HTMLParser
from pathlib import Path
from typing import Any

try:
    import requests
except ImportError as exc:  # pragma: no cover - runtime dependency guard
    raise SystemExit("Missing dependency: requests. Install with `python3 -m pip install requests`.") from exc

DEFAULT_GSC_SITE = "sc-domain:stringlab.org"
DEFAULT_GA4_PROPERTY = "411599658"
DEFAULT_CORPORATE_HOSTS = {"stringlab.org", "www.stringlab.org"}
DEFAULT_EXCLUDED_HOSTS = {"shine.stringlab.org"}
DEFAULT_OUTPUT_DIR = "seo-reports"


class PageParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.title = ""
        self.in_title = False
        self.metas: list[dict[str, str]] = []
        self.links: list[dict[str, str]] = []
        self.h1: list[str] = []
        self.in_h1 = False
        self.schema_payloads: list[str] = []
        self.in_schema = False
        self.schema_buffer: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        a = {k: (v or "") for k, v in attrs}
        if tag == "title":
            self.in_title = True
        elif tag == "meta":
            self.metas.append(a)
        elif tag == "link":
            self.links.append(a)
        elif tag == "h1":
            self.in_h1 = True
        elif tag == "script" and a.get("type") == "application/ld+json":
            self.in_schema = True
            self.schema_buffer = []

    def handle_endtag(self, tag: str) -> None:
        if tag == "title":
            self.in_title = False
        elif tag == "h1":
            self.in_h1 = False
        elif tag == "script" and self.in_schema:
            self.schema_payloads.append("".join(self.schema_buffer).strip())
            self.in_schema = False

    def handle_data(self, data: str) -> None:
        text = data.strip()
        if self.in_title:
            self.title += text
        if self.in_h1 and text:
            self.h1.append(text)
        if self.in_schema:
            self.schema_buffer.append(data)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Generate StringLab segmented SEO baseline reports.")
    parser.add_argument("--gsc-site", default=os.getenv("STRINGLAB_GSC_SITE", DEFAULT_GSC_SITE))
    parser.add_argument("--ga4-property", default=os.getenv("STRINGLAB_GA4_PROPERTY", DEFAULT_GA4_PROPERTY))
    parser.add_argument("--client-file", default=os.getenv("STRINGLAB_GOOGLE_CLIENT", ""))
    parser.add_argument("--token-file", default=os.getenv("STRINGLAB_GOOGLE_TOKEN", ""))
    parser.add_argument("--output-dir", default=os.getenv("STRINGLAB_SEO_REPORT_DIR", DEFAULT_OUTPUT_DIR))
    parser.add_argument("--days", type=int, default=int(os.getenv("STRINGLAB_SEO_DAYS", "28")))
    parser.add_argument("--crawl-limit", type=int, default=int(os.getenv("STRINGLAB_CRAWL_LIMIT", "120")))
    parser.add_argument("--corporate-host", action="append", default=[], help="Corporate host to include; repeatable.")
    parser.add_argument("--excluded-host", action="append", default=[], help="Subdomain/host to report separately; repeatable.")
    return parser.parse_args()


def require_file(path_value: str, label: str) -> Path:
    if not path_value:
        raise SystemExit(f"Missing {label}. Set the matching environment variable or pass CLI flag.")
    path = Path(path_value).expanduser()
    if not path.exists():
        raise SystemExit(f"{label} does not exist: {path}")
    return path


def access_token(client_file: Path, token_file: Path) -> str:
    token_data = json.loads(token_file.read_text())
    if time.time() < token_data.get("created_at", 0) + token_data.get("expires_in", 0) - 120:
        return token_data["access_token"]

    client_data = json.loads(client_file.read_text())
    client = client_data.get("installed") or client_data.get("web") or client_data
    payload = {
        "client_id": client["client_id"],
        "refresh_token": token_data["refresh_token"],
        "grant_type": "refresh_token",
    }
    if client.get("client_secret"):
        payload["client_secret"] = client["client_secret"]
    response = requests.post("https://oauth2.googleapis.com/token", data=payload, timeout=30)
    response.raise_for_status()
    token_data.update(response.json())
    token_data["created_at"] = int(time.time())
    token_file.write_text(json.dumps(token_data, indent=2))
    token_file.chmod(0o600)
    return token_data["access_token"]


def request_json(method: str, url: str, headers: dict[str, str], body: dict[str, Any] | None = None) -> dict[str, Any]:
    response = requests.request(method, url, headers=headers, json=body, timeout=45)
    content_type = response.headers.get("content-type", "")
    payload: Any = response.json() if content_type.startswith("application/json") else response.text[:1000]
    return {"status": response.status_code, "request": body, "response": payload}


def gsc_query(headers: dict[str, str], site: str, dimensions: list[str], days: int, row_limit: int = 250) -> dict[str, Any]:
    end = dt.date.today() - dt.timedelta(days=3)  # GSC final-data lag.
    start = end - dt.timedelta(days=days - 1)
    body = {
        "startDate": start.isoformat(),
        "endDate": end.isoformat(),
        "dimensions": dimensions,
        "rowLimit": row_limit,
        "dataState": "final",
    }
    encoded_site = urllib.parse.quote(site, safe="")
    return request_json("POST", f"https://www.googleapis.com/webmasters/v3/sites/{encoded_site}/searchAnalytics/query", headers, body)


def ga4_run(headers: dict[str, str], property_id: str, dimensions: list[str], metrics: list[str], days: int, limit: int = 250) -> dict[str, Any]:
    body = {
        "dateRanges": [{"startDate": f"{days}daysAgo", "endDate": "yesterday"}],
        "dimensions": [{"name": d} for d in dimensions],
        "metrics": [{"name": m} for m in metrics],
        "limit": limit,
        "orderBys": [{"metric": {"metricName": metrics[0]}, "desc": True}],
    }
    return request_json("POST", f"https://analyticsdata.googleapis.com/v1beta/properties/{property_id}:runReport", headers, body)


def rows(api_response: dict[str, Any]) -> list[dict[str, Any]]:
    response = api_response.get("response", {})
    return response.get("rows", []) if isinstance(response, dict) else []


def gsc_row(row: dict[str, Any]) -> dict[str, Any]:
    return {
        "keys": row.get("keys", []),
        "clicks": row.get("clicks", 0),
        "impressions": row.get("impressions", 0),
        "ctr": row.get("ctr", 0),
        "position": row.get("position", 0),
    }


def ga_row(row: dict[str, Any]) -> dict[str, Any]:
    return {
        "dimensions": [value.get("value") for value in row.get("dimensionValues", [])],
        "metrics": [value.get("value") for value in row.get("metricValues", [])],
    }


def url_host(value: str) -> str:
    parsed = urllib.parse.urlparse(value if "://" in value else f"https://stringlab.org{value if value.startswith('/') else '/' + value}")
    return parsed.netloc.lower()


def segment_for_url(value: str, corporate_hosts: set[str], excluded_hosts: set[str]) -> str:
    host = url_host(value)
    if host in corporate_hosts or not host:
        return "corporate"
    if host in excluded_hosts:
        return host
    if host.endswith(".stringlab.org"):
        return "other_subdomain"
    return "external_or_other"


def fetch_text(url: str, timeout: int = 20) -> tuple[int | None, str, dict[str, str], str]:
    req = urllib.request.Request(url, headers={"User-Agent": "StringLabSEOReporter/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as response:
            raw = response.read(1_500_000)
            return response.status, response.geturl(), dict(response.headers), raw.decode("utf-8", "replace")
    except Exception as exc:  # noqa: BLE001 - report fetch failures as crawl rows.
        return None, url, {}, str(exc)


def crawl_sitemap(crawl_limit: int) -> list[dict[str, Any]]:
    sitemap_status, _, _, sitemap_xml = fetch_text("https://stringlab.org/sitemap.xml")
    urls = re.findall(r"<loc>(.*?)</loc>", sitemap_xml) if sitemap_status == 200 else []
    crawl: list[dict[str, Any]] = []
    for url in urls[:crawl_limit]:
        status, final_url, headers, html = fetch_text(url)
        parser = PageParser()
        schema_valid = True
        if status == 200 and "<html" in html[:1000].lower():
            parser.feed(html)
            for payload in parser.schema_payloads:
                try:
                    json.loads(payload)
                except json.JSONDecodeError:
                    schema_valid = False
        desc = next((m.get("content", "") for m in parser.metas if m.get("name", "").lower() == "description"), "")
        robots = next((m.get("content", "") for m in parser.metas if m.get("name", "").lower() == "robots"), "")
        canonical = next((l.get("href", "") for l in parser.links if "canonical" in (l.get("rel") or "")), "")
        crawl.append({
            "url": url,
            "status": status,
            "final": final_url,
            "last_modified": headers.get("Last-Modified", ""),
            "title": parser.title,
            "title_len": len(parser.title),
            "description": desc,
            "desc_len": len(desc),
            "robots": robots,
            "canonical": canonical,
            "h1_count": len(parser.h1),
            "h1": " | ".join(parser.h1[:3]),
            "schema_count": len(parser.schema_payloads),
            "schema_valid": schema_valid,
        })
    return crawl


def detect_crawl_issues(crawl: list[dict[str, Any]]) -> list[dict[str, str]]:
    issues: list[dict[str, str]] = []
    for row in crawl:
        url = row["url"]
        if row["status"] != 200:
            issues.append({"severity": "error", "url": url, "issue": f"HTTP {row['status']}"})
        if not row["title"]:
            issues.append({"severity": "critical", "url": url, "issue": "missing title"})
        elif row["title_len"] > 65:
            issues.append({"severity": "warning", "url": url, "issue": f"long title {row['title_len']}"})
        if not row["description"]:
            issues.append({"severity": "critical", "url": url, "issue": "missing meta description"})
        elif row["desc_len"] > 170:
            issues.append({"severity": "warning", "url": url, "issue": f"long meta description {row['desc_len']}"})
        if not row["canonical"]:
            issues.append({"severity": "critical", "url": url, "issue": "missing canonical"})
        if row["h1_count"] != 1:
            issues.append({"severity": "warning", "url": url, "issue": f"h1_count={row['h1_count']}"})
        if not row["schema_valid"]:
            issues.append({"severity": "critical", "url": url, "issue": "invalid JSON-LD"})
    return issues


def summarize_gsc_pages(page_rows: list[dict[str, Any]], corporate_hosts: set[str], excluded_hosts: set[str]) -> dict[str, dict[str, float]]:
    segments: dict[str, dict[str, float]] = defaultdict(lambda: {"clicks": 0.0, "impressions": 0.0, "weighted_position_sum": 0.0})
    for row in page_rows:
        page = row.get("keys", [""])[0]
        segment = segment_for_url(page, corporate_hosts, excluded_hosts)
        impressions = float(row.get("impressions", 0) or 0)
        clicks = float(row.get("clicks", 0) or 0)
        segments[segment]["clicks"] += clicks
        segments[segment]["impressions"] += impressions
        segments[segment]["weighted_position_sum"] += impressions * float(row.get("position", 0) or 0)
    result: dict[str, dict[str, float]] = {}
    for segment, metrics in segments.items():
        impressions = metrics["impressions"]
        result[segment] = {
            "clicks": metrics["clicks"],
            "impressions": impressions,
            "avg_position_impression_weighted": (metrics["weighted_position_sum"] / impressions) if impressions else 0.0,
        }
    return result


def summarize_ga_hosts(host_rows: list[dict[str, Any]], corporate_hosts: set[str], excluded_hosts: set[str]) -> dict[str, dict[str, float]]:
    segments: dict[str, dict[str, float]] = defaultdict(lambda: {"sessions": 0.0, "activeUsers": 0.0, "eventCount": 0.0})
    for row in host_rows:
        host = (row.get("dimensions") or [""])[0]
        segment = "corporate" if host in corporate_hosts else host if host in excluded_hosts else "other_subdomain" if host.endswith(".stringlab.org") else "external_or_other"
        metrics = row.get("metrics") or []
        for key, idx in (("sessions", 0), ("activeUsers", 1), ("eventCount", 2)):
            if idx < len(metrics):
                try:
                    segments[segment][key] += float(metrics[idx])
                except (TypeError, ValueError):
                    pass
    return dict(segments)


def write_csv(path: Path, rows_: list[dict[str, Any]], fieldnames: list[str]) -> None:
    with path.open("w", newline="") as handle:
        writer = csv.DictWriter(handle, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows_)


def main() -> None:
    args = parse_args()
    client_file = require_file(args.client_file, "OAuth client file")
    token_file = require_file(args.token_file, "OAuth token file")
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    corporate_hosts = set(args.corporate_host or DEFAULT_CORPORATE_HOSTS)
    excluded_hosts = set(args.excluded_host or DEFAULT_EXCLUDED_HOSTS)
    now = dt.datetime.utcnow().replace(microsecond=0)
    stamp = now.strftime("%Y-%m-%d_%H-%M-%SZ")
    headers = {"Authorization": f"Bearer {access_token(client_file, token_file)}"}

    gsc_queries_api = gsc_query(headers, args.gsc_site, ["query"], args.days, 250)
    gsc_pages_api = gsc_query(headers, args.gsc_site, ["page"], args.days, 500)
    gsc_query_page_api = gsc_query(headers, args.gsc_site, ["query", "page"], args.days, 500)
    gsc_country_api = gsc_query(headers, args.gsc_site, ["country"], args.days, 50)
    gsc_device_api = gsc_query(headers, args.gsc_site, ["device"], args.days, 20)

    ga_landing_api = ga4_run(headers, args.ga4_property, ["hostName", "landingPagePlusQueryString"], ["sessions", "activeUsers", "engagementRate"], args.days, 500)
    ga_hosts_api = ga4_run(headers, args.ga4_property, ["hostName"], ["sessions", "activeUsers", "eventCount"], args.days, 50)
    ga_events_api = ga4_run(headers, args.ga4_property, ["hostName", "eventName"], ["eventCount", "activeUsers"], args.days, 250)
    ga_channels_api = ga4_run(headers, args.ga4_property, ["hostName", "sessionDefaultChannelGroup"], ["sessions", "activeUsers", "engagementRate"], args.days, 250)

    gsc_queries = [gsc_row(row) for row in rows(gsc_queries_api)]
    gsc_pages = [gsc_row(row) for row in rows(gsc_pages_api)]
    gsc_query_pages = [gsc_row(row) for row in rows(gsc_query_page_api)]
    gsc_countries = [gsc_row(row) for row in rows(gsc_country_api)]
    gsc_devices = [gsc_row(row) for row in rows(gsc_device_api)]

    ga_landing = [ga_row(row) for row in rows(ga_landing_api)]
    ga_hosts = [ga_row(row) for row in rows(ga_hosts_api)]
    ga_events = [ga_row(row) for row in rows(ga_events_api)]
    ga_channels = [ga_row(row) for row in rows(ga_channels_api)]

    crawl = crawl_sitemap(args.crawl_limit)
    issues = detect_crawl_issues(crawl)

    gsc_segment_summary = summarize_gsc_pages(gsc_pages, corporate_hosts, excluded_hosts)
    ga_host_summary = summarize_ga_hosts(ga_hosts, corporate_hosts, excluded_hosts)
    issue_counts = Counter(issue["severity"] for issue in issues)

    report = {
        "generated_at_utc": now.isoformat() + "Z",
        "days": args.days,
        "gsc_site": args.gsc_site,
        "ga4_property": args.ga4_property,
        "segmentation": {
            "corporate_hosts": sorted(corporate_hosts),
            "excluded_hosts": sorted(excluded_hosts),
            "gsc_page_segments": gsc_segment_summary,
            "ga4_host_segments": ga_host_summary,
        },
        "gsc": {
            "api_statuses": {
                "queries": gsc_queries_api["status"],
                "pages": gsc_pages_api["status"],
                "query_pages": gsc_query_page_api["status"],
                "countries": gsc_country_api["status"],
                "devices": gsc_device_api["status"],
            },
            "queries": gsc_queries,
            "pages": gsc_pages,
            "query_pages": gsc_query_pages,
            "countries": gsc_countries,
            "devices": gsc_devices,
        },
        "ga4": {
            "api_statuses": {
                "landing": ga_landing_api["status"],
                "hosts": ga_hosts_api["status"],
                "events": ga_events_api["status"],
                "channels": ga_channels_api["status"],
            },
            "landing": ga_landing,
            "hosts": ga_hosts,
            "events": ga_events,
            "channels": ga_channels,
        },
        "crawl": crawl,
        "issues": issues,
        "issue_counts": dict(issue_counts),
    }

    json_path = output_dir / f"stringlab-seo-baseline-{stamp}.json"
    md_path = output_dir / f"stringlab-seo-baseline-{stamp}.md"
    pages_csv = output_dir / f"stringlab-gsc-pages-{stamp}.csv"
    queries_csv = output_dir / f"stringlab-gsc-queries-{stamp}.csv"
    landing_csv = output_dir / f"stringlab-ga4-landing-{stamp}.csv"
    crawl_csv = output_dir / f"stringlab-crawl-{stamp}.csv"

    json_path.write_text(json.dumps(report, indent=2))
    write_csv(pages_csv, [
        {
            "page": (row.get("keys") or [""])[0],
            "segment": segment_for_url((row.get("keys") or [""])[0], corporate_hosts, excluded_hosts),
            "clicks": row.get("clicks", 0),
            "impressions": row.get("impressions", 0),
            "ctr": row.get("ctr", 0),
            "position": row.get("position", 0),
        }
        for row in gsc_pages
    ], ["page", "segment", "clicks", "impressions", "ctr", "position"])
    write_csv(queries_csv, [
        {
            "query": (row.get("keys") or [""])[0],
            "clicks": row.get("clicks", 0),
            "impressions": row.get("impressions", 0),
            "ctr": row.get("ctr", 0),
            "position": row.get("position", 0),
        }
        for row in gsc_queries
    ], ["query", "clicks", "impressions", "ctr", "position"])
    write_csv(landing_csv, [
        {
            "host": (row.get("dimensions") or ["", ""])[0],
            "landing_page": (row.get("dimensions") or ["", ""])[1] if len(row.get("dimensions") or []) > 1 else "",
            "sessions": (row.get("metrics") or [""])[0],
            "active_users": (row.get("metrics") or ["", ""])[1] if len(row.get("metrics") or []) > 1 else "",
            "engagement_rate": (row.get("metrics") or ["", "", ""])[2] if len(row.get("metrics") or []) > 2 else "",
        }
        for row in ga_landing
    ], ["host", "landing_page", "sessions", "active_users", "engagement_rate"])
    write_csv(crawl_csv, crawl, ["url", "status", "final", "last_modified", "title", "title_len", "description", "desc_len", "robots", "canonical", "h1_count", "h1", "schema_count", "schema_valid"])

    md: list[str] = []
    md.append(f"# StringLab SEO Baseline — {now.date()}\n\n")
    md.append(f"Window: last {args.days} days. GSC uses final data ending ~3 days ago; GA4 ends yesterday.\n\n")
    md.append("## Data sources\n")
    md.append(f"- GSC: `{args.gsc_site}`\n")
    md.append(f"- GA4 property: `{args.ga4_property}`\n")
    md.append("- Live crawl: `https://stringlab.org/sitemap.xml`\n\n")
    md.append("## Hostname segmentation\n")
    md.append(f"- Corporate hosts: `{', '.join(sorted(corporate_hosts))}`\n")
    md.append(f"- Separately tracked/excluded hosts: `{', '.join(sorted(excluded_hosts))}`\n\n")
    md.append("### GSC page segments\n")
    for segment, metrics in sorted(gsc_segment_summary.items()):
        md.append(f"- **{segment}** — clicks {metrics['clicks']:.0f}, impressions {metrics['impressions']:.0f}, avg pos {metrics['avg_position_impression_weighted']:.1f}\n")
    md.append("\n### GA4 hostname segments\n")
    for segment, metrics in sorted(ga_host_summary.items()):
        md.append(f"- **{segment}** — sessions {metrics.get('sessions', 0):.0f}, active users {metrics.get('activeUsers', 0):.0f}, events {metrics.get('eventCount', 0):.0f}\n")

    md.append("\n## GSC top queries\n")
    for row in gsc_queries[:15]:
        query = (row.get("keys") or [""])[0]
        md.append(f"- `{query}` — clicks {row['clicks']:.0f}, impressions {row['impressions']:.0f}, CTR {row['ctr']:.2%}, pos {row['position']:.1f}\n")

    md.append("\n## GSC top corporate pages\n")
    corporate_pages = [row for row in gsc_pages if segment_for_url((row.get("keys") or [""])[0], corporate_hosts, excluded_hosts) == "corporate"]
    for row in corporate_pages[:15]:
        page = (row.get("keys") or [""])[0]
        md.append(f"- {page} — clicks {row['clicks']:.0f}, impressions {row['impressions']:.0f}, pos {row['position']:.1f}\n")

    md.append("\n## GA4 top landing pages by host\n")
    for row in ga_landing[:20]:
        dims = row.get("dimensions") or []
        metrics = row.get("metrics") or []
        md.append(f"- `{dims[0] if len(dims) > 0 else ''}` `{dims[1] if len(dims) > 1 else ''}` — sessions {metrics[0] if len(metrics) > 0 else ''}, users {metrics[1] if len(metrics) > 1 else ''}\n")

    md.append("\n## Crawl summary\n")
    md.append(f"- URLs crawled: {len(crawl)}\n")
    md.append(f"- Issues detected: {len(issues)} ({', '.join(f'{k}: {v}' for k, v in sorted(issue_counts.items())) or 'none'})\n")
    md.append("\n## Top crawl issues\n")
    for issue in issues[:30]:
        md.append(f"- **{issue['severity']}** {issue['url']}: {issue['issue']}\n")

    md_path.write_text("".join(md))

    print(json.dumps({
        "status": "ok",
        "generated_at_utc": report["generated_at_utc"],
        "json": str(json_path),
        "markdown": str(md_path),
        "csv": [str(pages_csv), str(queries_csv), str(landing_csv), str(crawl_csv)],
        "gsc_rows": {"queries": len(gsc_queries), "pages": len(gsc_pages), "query_pages": len(gsc_query_pages)},
        "ga4_rows": {"landing": len(ga_landing), "hosts": len(ga_hosts), "events": len(ga_events), "channels": len(ga_channels)},
        "crawl_urls": len(crawl),
        "issue_count": len(issues),
        "segments": report["segmentation"],
        "api_statuses": {"gsc": report["gsc"]["api_statuses"], "ga4": report["ga4"]["api_statuses"]},
    }, indent=2))


if __name__ == "__main__":
    main()
