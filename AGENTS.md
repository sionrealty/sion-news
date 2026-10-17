# Sion news publishing

This repository contains only public news for https://sionrealtypm.com. Do not upload credentials, client/tenant records, rental listings or unrelated website code.

## Destination and writer

Use the connected GitHub tools to read and update `news.json` on `main`. Read the current file and its blob SHA immediately before `update_file`; pass that SHA and do not force a conflicting write. Read back the resulting file to verify it. Never claim a failed write succeeded.

The cPanel site consumes the fixed public URL https://raw.githubusercontent.com/sionrealty/sion-news/main/news.json through its News-only importer. It checks at most once every five minutes when News is requested. Installing that importer is a separate prerequisite: a GitHub commit alone is not proof the live website imported the feed. No cPanel session or credentials are needed for routine publishing.

## Content

Publish original English reporting for Sion Property Management, with concise summaries of four or five lines, dated primary-source attribution and links. Cover Orange County and nearby Orlando/Winter Garden/Windermere/MetroWest (32835) accurately; separate county, city and Florida-wide jurisdiction. Cover sales/rental market data with comparable periods and explicit geography. Do not represent broad sales statistics as local apartment rents. Separate rumor, proposal, legislative stage, approval, signature and effective date; verify legal/tax claims using current official primary sources. No competitor advertising, copied articles, invented data, fake freshness or guaranteed returns. Do not fabricate a new story simply to fill a scheduled update.

Preserve the archive; display ordering is handled by the site. Use a new unique slug for a new story. Correct existing stories transparently without relabeling an old story as new. Preserve Patrick's manual opinions and editorial additions. If a previously existing slug disappears from the feed, do not restore it automatically.

## JSON format

Top level: `version: 1`, `site: "https://sionrealtypm.com"`, `updated_at` (ISO 8601 UTC), `articles` (array, maximum 300; entire file under 2 MiB).

Every article requires these string fields in this order (the importer normalizes order):

- `slug`: unique lowercase ASCII words separated by hyphens; maximum 120 characters.
- `category`: exactly `Orange County`, `Market`, `Florida` or `Laws & proposals`.
- `kind`: brief content type, maximum 100 characters.
- `title`: maximum 200 characters.
- `summary`: maximum 650 characters, brief enough for a card.
- `body`: plain text, paragraphs separated by blank lines, maximum 20,000 characters. Do not include HTML.
- `source_name`: maximum 300 characters.
- `source_url`: full HTTPS primary-source URL without credentials; maximum 1,500 characters.
- `source_date`: publication date and data/reporting period, maximum 300 characters.
- `chart`: empty string or a JSON-encoded array of at most 12 `[periodLabel, numericValue]` pairs; maximum 3,000 characters. Use only comparable values with clearly named units and periods.
- `status`: `published`, `draft` or `archived` (normally publish only finished articles).
- `published`: valid `YYYY-MM-DD` date in America/New_York. Future dates stay hidden until that date.

The importer updates News only. It preserves website-side manual edits, opinions, archiving and deletions rather than silently overwriting them. It leaves the last valid content visible if this feed cannot be fetched or validated.
