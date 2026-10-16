// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Yielder Group B.V.
//
// Against the WordPress in tests/compose.yaml; `just test` starts it. The fixture
// tests/fixtures/github-releases.php fakes GitHub and api.wordpress.org.
import assert from "node:assert/strict";
import { describe, it } from "node:test";

const BASE_URL = process.env.WP_URL ?? "http://127.0.0.1:8089";
const REPOSITORY = "https://github.com/swbase/yga-wordpress-plugin";

async function updateCheck(manifest) {
  const response = await fetch(`${BASE_URL}/?rest_route=/yga-test/v1/update-check`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      manifest: manifest === null ? null : JSON.stringify(manifest),
    }),
  });
  assert.equal(response.status, 200);
  return response.json();
}

describe("updates from GitHub releases", () => {
  it("offers a newer release, with the zip from that release's tag", async () => {
    const result = await updateCheck({
      version: "9.0.0",
      requires: "6.5",
      requires_php: "8.1",
      tested: "7.1",
    });
    assert.ok(
      result.requested.includes(`${REPOSITORY}/releases/latest/download/manifest.json`),
      JSON.stringify(result.requested),
    );
    assert.equal(result.update.new_version, "9.0.0");
    assert.equal(
      result.update.package,
      `${REPOSITORY}/releases/download/v9.0.0/yga-wordpress-plugin.zip`,
    );
    assert.equal(result.update.requires_php, "8.1");
    assert.equal(result.update.tested, "7.1");
  });

  it("shows the release in the details dialog", async () => {
    const result = await updateCheck({ version: "9.0.0" });
    assert.equal(result.details.version, "9.0.0");
    assert.match(result.details.sections.changelog, /releases\/tag\/v9\.0\.0/);
  });

  it("offers nothing when the release is not newer", async () => {
    const result = await updateCheck({ version: "0.0.1" });
    assert.equal(result.update, null);
  });

  it("offers nothing for a version that is not x.y.z", async () => {
    const result = await updateCheck({ version: "9.0.0/../../../evil" });
    assert.equal(result.update, null);
  });

  it("offers nothing when there is no release", async () => {
    const result = await updateCheck(null);
    assert.equal(result.update, null);
  });

  it("always auto-updates this plugin", async () => {
    const result = await updateCheck({ version: "0.0.1" });
    assert.equal(result.auto_update, true);
  });
});
