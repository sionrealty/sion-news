# Sion public indexing policy

Preferred homepage URL: https://sionrealtypm.com/ . The homepage canonical consolidates /index.html and the www host without changing their availability.

Index the main site, its public rental catalog at /sion-imoveis/index.php, published news and approved public contact content. Services are homepage sections, not separate pages.

Do not include the Owners Portal, reports, administration, investor information, tests or private files in sitemaps. Portal responses use authentication, access controls, private caching and X-Robots-Tag: noindex, nofollow, nosnippet. Search directives are not access control.

/regioes.html is approved by Patrick for public search indexing; it uses index,follow and its own canonical URL. /calculadora.html retains noindex,nofollow until specifically approved for public search indexing.

site/robots.txt and site/sitemap.xml are baseline copies of the live public files captured on 2026-10-26; adding them to this repository does not deploy them. The current managed release changes only homepage metadata and the portal indexing header. Preserve live sitemap changes until a deployment path for these files is reviewed.

Search Console warnings require reviewing the exact affected URLs, live inspection, and then validation or an indexing request where appropriate. Do not remove noindex globally or claim Google has indexed a page based on a code commit. Never commit Search Console credentials, private reports, client information or account data.
