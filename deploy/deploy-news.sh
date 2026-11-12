#!/bin/sh
set -eu
umask 077
# Managed website/portal code updates, authorized by Patrick; never deploy data.
for SITE_PHP in /usr/local/bin/php /usr/bin/php; do
  if test -x "$SITE_PHP"; then
    "$SITE_PHP" /home2/sionreal/repositories/sion-news/deploy/deploy-managed.php || echo "Managed code update failed; previous files retained."
    break
  fi
done
REPO=/home2/sionreal/repositories/sion-news
TARGET=/home2/sionreal/public_html/sion-imoveis/news.php
SOURCE="$REPO/site/sion-imoveis/news.php"
BACKUPS=/home2/sionreal/news-code-backups
test -s "$SOURCE"
test -d /home2/sionreal/public_html/sion-imoveis
test -f /home2/sionreal/public_html/sion-imoveis/news-common.php
if test -L "$TARGET"; then echo "Refusing a symlink destination" >&2; exit 1; fi
PHP=
for candidate in /usr/local/bin/php /usr/bin/php; do
  if test -x "$candidate"; then PHP="$candidate"; break; fi
done
if test -z "$PHP"; then echo "PHP executable not found; no deployment performed" >&2; exit 1; fi
"$PHP" -l "$SOURCE"
# Add narrowly scoped public aliases without replacing existing host rules.
ROOT=/home2/sionreal/public_html
RULES="$ROOT/.htaccess"
test ! -L "$RULES"
if ! test -f "$RULES" || ! grep -Fxq '# BEGIN SION PUBLIC ALIASES' "$RULES"; then
  test ! -e "$ROOT/news" && test ! -e "$ROOT/imoveis"
  mkdir -p "$BACKUPS"
  chmod 700 "$BACKUPS"
  ROUTE_TEMP=$(mktemp "$ROOT/.sion-routes.XXXXXX")
  trap 'rm -f "$ROUTE_TEMP"' 0 HUP INT TERM
  cat > "$ROUTE_TEMP" <<'SION_ROUTES'
# BEGIN SION PUBLIC ALIASES
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^news/?$ sion-imoveis/news.php [END]
RewriteRule ^news/([a-z0-9-]{1,120})/?$ sion-imoveis/news.php?article=$1 [END,QSA]
RewriteRule ^imoveis/?$ sion-imoveis/index.php [END]
</IfModule>
# END SION PUBLIC ALIASES
SION_ROUTES
  if test -f "$RULES"; then
    ROUTE_BACKUP="$BACKUPS/root-htaccess-$(date -u +%Y%m%dT%H%M%SZ)-$"
    cp -p "$RULES" "$ROUTE_BACKUP"
    cat "$ROUTE_BACKUP" >> "$ROUTE_TEMP"
    cmp -s "$RULES" "$ROUTE_BACKUP" || exit 1
  fi
  chmod 644 "$ROUTE_TEMP"
  mv -f "$ROUTE_TEMP" "$RULES"
  # Keep the original rules if the new aliases fail the public smoke check.
  if ! curl -fsS --max-time 10 https://sionrealtypm.com/news | grep -Fq 'THE SION JOURNAL' ||
     ! curl -fsS --max-time 10 https://sionrealtypm.com/news/onda-azul-2026-en | grep -Fq 'Patrick Portela' ||
     ! curl -fsS --max-time 10 https://sionrealtypm.com/imoveis > /dev/null; then
    if test -n "${ROUTE_BACKUP:-}"; then
      cp -p "$ROUTE_BACKUP" "$ROUTE_TEMP"
      mv -f "$ROUTE_TEMP" "$RULES"
    else
      rm -f "$RULES"
    fi
    echo "Public alias verification failed; restored original routing." >&2
  fi
  trap - 0 HUP INT TERM
fi

# Defer the new Rentals link if the managed route could not be installed.
if grep -Fq 'href="/rent"' "$SOURCE" &&
   { ! test -f "$ROOT/sion-imoveis/rent-route.php" || ! grep -Fxq '# BEGIN SION RENT ROUTE' "$RULES"; }; then
  echo "Rent route unavailable; keeping the previous News template." >&2
  exit 1
fi

if test -f "$TARGET" && cmp -s "$SOURCE" "$TARGET"; then
  echo "News page already matches this release."
  exit 0
fi
mkdir -p "$BACKUPS"
chmod 700 "$BACKUPS"
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
if test -f "$TARGET"; then
  cp -p "$TARGET" "$BACKUPS/news-$STAMP-$$.php"
  chmod 600 "$BACKUPS/news-$STAMP-$$.php"
fi
TEMP=$(mktemp /home2/sionreal/public_html/sion-imoveis/.news-deploy.XXXXXX)
trap 'rm -f "$TEMP"' 0 HUP INT TERM
cp "$SOURCE" "$TEMP"
chmod 644 "$TEMP"
mv -f "$TEMP" "$TARGET"
trap - 0 HUP INT TERM
echo "Published news.php at $STAMP; previous file retained outside public_html."
