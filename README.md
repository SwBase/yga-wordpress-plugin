# stuurlui-wp-plugin

Yielder's own WordPress plugin for yielder.com, a site built and maintained by Stuurlui
on their `stuurlui` theme. It adds what Yielder needs without touching Stuurlui's code.

## WAT HET DOET

A Knowledge Hub article (`knowledge_hub`) keeps its body and summary in ACF fields the
theme registers in PHP, without `show_in_rest`, and the post type has no editor. The REST
API can create one but not fill it: `content`, `excerpt` and `acf` are dropped silently.

The plugin adds one REST field, `geo`, to the `knowledge_hub` endpoint:

```jsonc
// POST /wp-json/wp/v2/knowledge_hub
{
  "title": "...",
  "status": "draft",
  "knowledge_hub_category": [11], // Types: Blog, Nieuws, ...
  "knowledge_hub_cat": [84], // Categorieën
  "geo": {
    "content": "<p>html</p>", // ACF knowledge_hub_content ("Artikel content")
    "excerpt": "...", // ACF settings-group → post-excerpt-content ("Samenvatting")
  },
}
```

- **Edit context only.** The create response carries `geo`, so a client can check its
  write landed. Anonymous readers never see it.
- **The rest of the group stays put.** The summary shares an ACF group with the page's
  breadcrumbs, menu colour and CTA buttons; writing it leaves those alone.
- **kses for everyone without `unfiltered_html`**, as WordPress does for `post_content`.
- **Fails loudly.** If the theme stops registering one of the field keys, writes answer
  500 `yielder_acf_fields_missing` instead of dropping the article.

The field keys are in `includes/knowledge-hub-rest.php`, taken from the live edit screen.

## GEBRUIK

```bash
just stuurlui-wp-plugin::test   # REST tests against a throwaway WordPress in Docker
just stuurlui-wp-plugin::zip    # dist/stuurlui-wp-plugin.zip
```

Install the zip through Plugins → Nieuwe plugin → Plugin uploaden on yielder.com. To
update, upload the new zip and choose to replace the installed version.

`up` and `down` start and remove the test WordPress (127.0.0.1:8089, `WP_PORT` to move
it) for poking at by hand. It runs WordPress 7.1, as yielder.com does, with free ACF
instead of PRO and `tests/fixtures/stuurlui-theme.php` standing in for the theme.

## OPMERKINGEN

- **Tell Stuurlui** the plugin is there. They manage the site through MainWP; a plugin
  they do not know about is one they might disable.
- **When Stuurlui changes the field group**, check the keys in the edit screen's HTML
  (`data-key` on each `.acf-field`) against the constants and the test fixture.
