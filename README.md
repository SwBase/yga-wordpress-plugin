# yga-wordpress-plugin

Yielder's own WordPress plugin for yielder.com, a site built and maintained by Stuurlui
on their `stuurlui` theme. It adds what Yielder needs without touching Stuurlui's code.

## WAT HET DOET

A Knowledge Hub article (`knowledge_hub`) keeps its body and summary in ACF fields the
theme registers in PHP, without `show_in_rest`, and the post type has no editor. The REST
API can create one but not fill it: `content`, `excerpt` and `acf` are dropped silently.

The plugin adds one REST field, `yga`, to the `knowledge_hub` endpoint:

```jsonc
// POST /wp-json/wp/v2/knowledge_hub
{
  "title": "...",
  "status": "draft",
  "knowledge_hub_category": [11], // Types: Blog, Nieuws, ...
  "knowledge_hub_cat": [84], // Categorieën
  "yga": {
    "content": "<p>html</p>", // ACF knowledge_hub_content ("Artikel content")
    "excerpt": "...", // ACF settings-group → post-excerpt-content ("Samenvatting")
  },
}
```

- **Edit context only.** The create response carries `yga`, so a client can check its
  write landed. Anonymous readers never see it.
- **The rest of the group stays put.** The summary shares an ACF group with the page's
  breadcrumbs, menu colour and CTA buttons; writing it leaves those alone.
- **kses for everyone without `unfiltered_html`**, as WordPress does for `post_content`.
- **Fails loudly.** If the theme stops registering one of the field keys, writes answer
  500 `yielder_acf_fields_missing` instead of dropping the article.

The field keys are in `includes/knowledge-hub-rest.php`, taken from the live edit screen.

`geo` is the field's old name. Until 0.3.0 it is an alias: it reads and writes the
same, and a request with both writes `yga` and ignores `geo`. Release 0.3.0 once
geo-contentmotor sends `yga` and checks it in the response.

## GEBRUIK

```bash
just test      # tests against a throwaway WordPress in Docker
just build     # dist/yga-wordpress-plugin.zip and dist/manifest.json
just release   # tag and push the Version in yga-wordpress-plugin.php
```

`up` and `down` start and remove the test WordPress (127.0.0.1:8089, `WP_PORT` to move
it) for poking at by hand. It runs WordPress 7.1, as yielder.com does, with free ACF
instead of PRO. `tests/fixtures/` holds mu-plugins that stand in for the theme and for
GitHub; they are never part of the zip.

## UPDATES

The plugin updates itself from the releases of the public repository
[swbase/yga-wordpress-plugin](https://github.com/swbase/yga-wordpress-plugin), and turns
on auto-updates for itself (`includes/updates.php`):

1. `Update URI: https://github.com/swbase/yga-wordpress-plugin` keeps WordPress from
   asking wordpress.org; it calls the `update_plugins_github.com` filter instead.
2. That filter reads `releases/latest/download/manifest.json`: a plain download, so it
   counts against no GitHub API rate limit. The result is cached for six hours.
3. The zip URL is built from the manifest's version
   (`releases/download/v<version>/yga-wordpress-plugin.zip`), never taken from it.
4. WordPress's auto-updater (WP-cron, twice a day) installs a newer version, and rolls
   it back if it causes a fatal error.

The first install is by hand: upload the zip from the latest release through Plugins →
Nieuwe plugin → Plugin uploaden. Remove 0.1.0 (`stuurlui-wp-plugin`) first. The same
works when a site does not pick up a release by itself; step by step in
[docs/manual-install.md](docs/manual-install.md).

## RELEASES

1. Raise `Version:` in `yga-wordpress-plugin.php` and `Stable tag:` in `readme.txt`, and
   add a changelog entry to `readme.txt`. Commit.
2. `just release` tags `v<version>` and pushes it to GHE; the mirror takes it on.
3. `.github/workflows/release.yml` runs on github.com only. It checks that tag, Version
   and Stable tag agree, runs the tests, builds with `bin/build.sh` and publishes the
   zip and `manifest.json` as a GitHub release.

## REPOSITORIES

The source of truth is `yielder.ghe.com/yielder-platforms/yga-wordpress-plugin`; push
only there. `.github/workflows/mirror.yml` pushes `main` and every `v*` tag on to the
public repository [swbase/yga-wordpress-plugin](https://github.com/swbase/yga-wordpress-plugin),
where releases are built. Other branches stay on GHE.

- Never change the github.com repository directly: the mirror pushes without force, so
  a commit made there stops it until removed.
- The mirror authenticates with a deploy key with write access on the github.com
  repository; its private half is the GHE secret `MIRROR_SSH_KEY`. To rotate it,
  replace both.

Everything that reaches `main` is public. Keep secrets, customer data and internal
hostnames out of it.

## LICENTIE

Copyright (C) 2026 Yielder Group B.V. Free software under the
[GNU General Public License v3 or later](LICENSE), as WordPress requires of a plugin
(GPL-compatible) and as the plugin header and `readme.txt` declare. Every source file
carries `SPDX-License-Identifier: GPL-3.0-or-later`; the release zip contains
`LICENSE`. Code from elsewhere may only be added if its licence is GPLv3-compatible.
