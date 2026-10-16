set shell := ["bash", "-euo", "pipefail", "-c"]

COMPOSE := "docker compose -f tests/compose.yaml"
WP_PORT := env("WP_PORT", "8089")

_default:
    @just --list --unsorted

# Syntax-check every PHP file.
lint:
    find . -name '*.php' -not -path './dist/*' -not -path './.git/*' -print0 | xargs -0 -n1 php -l >/dev/null

# dist/yga-wordpress-plugin.zip and dist/manifest.json, as a release publishes them.
build:
    bin/build.sh

# Start the test WordPress on 127.0.0.1:{{ WP_PORT }}: admin, a contributor, ACF and the plugin.
up:
    WP_PORT={{ WP_PORT }} {{ COMPOSE }} up -d --wait
    {{ COMPOSE }} run --rm cli wp core is-installed 2>/dev/null || \
      {{ COMPOSE }} run --rm cli wp core install --url=http://127.0.0.1:{{ WP_PORT }} \
        --title=Test --admin_user=admin --admin_email=admin@example.com --skip-email
    {{ COMPOSE }} run --rm cli wp plugin install advanced-custom-fields --activate
    {{ COMPOSE }} run --rm cli wp plugin activate yga-wordpress-plugin
    {{ COMPOSE }} run --rm cli wp user get contributor >/dev/null 2>&1 || \
      {{ COMPOSE }} run --rm cli wp user create contributor contributor@example.com --role=contributor

# Remove the test WordPress and its data.
down:
    {{ COMPOSE }} down -v

# Run the tests against a fresh test WordPress, then remove it.
test: lint up
    status=0; WP_URL=http://127.0.0.1:{{ WP_PORT }} node --test tests/*.test.mjs || status=$?; \
      {{ COMPOSE }} down -v; exit $status

# Tag and push a release of the Version in yga-wordpress-plugin.php; GitHub builds and publishes it.
release: build
    version=$(sed -n -E 's/^[ *]*Version:[[:space:]]*//p' yga-wordpress-plugin.php | head -n1); \
      git diff --quiet HEAD || { echo "commit first" >&2; exit 1; }; \
      git tag -a "v$version" -m "v$version" && git push origin "v$version"
