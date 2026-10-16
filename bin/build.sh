#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-3.0-or-later
# Copyright (C) 2026 Yielder Group B.V.
#
# Builds what a release publishes, in dist/:
#   yga-wordpress-plugin.zip  the plugin, with its licence and readme, without its tests
#   manifest.json             what includes/updates.php reads from the latest release
#
# With a tag (bin/build.sh v0.2.0) it first checks that the tag, the Version header and
# readme.txt's Stable tag agree, so a release can never offer a zip with another version.
set -euo pipefail
cd "$(dirname "$0")/.."

SLUG=yga-wordpress-plugin

# A header value: `header Version yga-wordpress-plugin.php`.
header() { sed -n -E "s/^[ *]*$1:[[:space:]]*(.*[^[:space:]])[[:space:]]*$/\1/p" "$2" | head -n1; }

version=$(header Version "$SLUG.php")
stable=$(header "Stable tag" readme.txt)
if [[ ! $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Version header '$version' is not x.y.z" >&2; exit 1
fi
if [[ $stable != "$version" ]]; then
  echo "readme.txt Stable tag $stable does not match Version $version" >&2; exit 1
fi
if [[ $# -gt 0 && $1 != "v$version" ]]; then
  echo "tag $1 does not match Version $version" >&2; exit 1
fi

find . -name '*.php' -not -path './dist/*' -not -path './.git/*' -print0 | xargs -0 -n1 php -l >/dev/null

rm -rf dist && mkdir -p "dist/$SLUG"
cp "$SLUG.php" readme.txt LICENSE "dist/$SLUG/"
cp -R includes "dist/$SLUG/"
(cd dist && zip -qrX "$SLUG.zip" "$SLUG")

VERSION=$version \
REQUIRES=$(header "Requires at least" "$SLUG.php") \
REQUIRES_PHP=$(header "Requires PHP" "$SLUG.php") \
TESTED=$(header "Tested up to" readme.txt) \
  php -r 'echo json_encode(array(
    "version"      => getenv("VERSION"),
    "requires"     => getenv("REQUIRES"),
    "requires_php" => getenv("REQUIRES_PHP"),
    "tested"       => getenv("TESTED"),
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";' >dist/manifest.json

echo "dist/$SLUG.zip, dist/manifest.json ($version)"
