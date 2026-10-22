#!/bin/sh
set -eu
umask 077
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
