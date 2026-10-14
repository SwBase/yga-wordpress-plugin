set shell := ["bash", "-euo", "pipefail", "-c"]

# Recipes in this module run from packages/stuurlui-wp-plugin/.

COMPOSE := "docker compose -f tests/compose.yaml"
WP_PORT := env("WP_PORT", "8089")

_default:
    @just --justfile "{{ justfile() }}" --unsorted --list stuurlui-wp-plugin

# Syntax-check every PHP file.
lint:
    find . -name '*.php' -not -path './dist/*' -print0 | xargs -0 -n1 php -l >/dev/null

# The installable zip, dist/stuurlui-wp-plugin.zip: the plugin without its tests.
zip: lint
    rm -rf dist && mkdir -p dist/stuurlui-wp-plugin
    cp stuurlui-wp-plugin.php dist/stuurlui-wp-plugin/
    cp -R includes dist/stuurlui-wp-plugin/
    (cd dist && zip -qr stuurlui-wp-plugin.zip stuurlui-wp-plugin)
    @echo "dist/stuurlui-wp-plugin.zip"

# Start the test WordPress on 127.0.0.1:{{ WP_PORT }}: admin, a contributor, ACF and the plugin.
up:
    WP_PORT={{ WP_PORT }} {{ COMPOSE }} up -d --wait
    {{ COMPOSE }} run --rm cli wp core is-installed 2>/dev/null || \
      {{ COMPOSE }} run --rm cli wp core install --url=http://127.0.0.1:{{ WP_PORT }} \
        --title=Test --admin_user=admin --admin_email=admin@example.com --skip-email
    {{ COMPOSE }} run --rm cli wp plugin install advanced-custom-fields --activate
    {{ COMPOSE }} run --rm cli wp plugin activate stuurlui-wp-plugin
    {{ COMPOSE }} run --rm cli wp user get contributor >/dev/null 2>&1 || \
      {{ COMPOSE }} run --rm cli wp user create contributor contributor@example.com --role=contributor

# Remove the test WordPress and its data.
down:
    {{ COMPOSE }} down -v

# Run the REST tests against a fresh test WordPress, then remove it.
test: lint up
    status=0; WP_URL=http://127.0.0.1:{{ WP_PORT }} node --test tests/ || status=$?; \
      {{ COMPOSE }} down -v; exit $status
