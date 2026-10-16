// SPDX-License-Identifier: GPL-3.0-or-later
// Copyright (C) 2026 Yielder Group B.V.
//
// Against the WordPress in tests/compose.yaml; `just test` starts it.
// Users authenticate with the X-Test-User header the fixture honours.
import assert from "node:assert/strict";
import { describe, it } from "node:test";

const BASE_URL = process.env.WP_URL ?? "http://127.0.0.1:8089";

async function rest(method, route, { user, body, query = "" } = {}) {
  const response = await fetch(`${BASE_URL}/?rest_route=${route}${query}`, {
    method,
    headers: {
      ...(user ? { "X-Test-User": user } : {}),
      ...(body ? { "Content-Type": "application/json" } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  return { status: response.status, json: await response.json() };
}

const create = (user, body) =>
  rest("POST", "/wp/v2/knowledge_hub", {
    user,
    body: { title: "Test", status: "draft", ...body },
  });

const read = (id, user = "admin") =>
  rest("GET", `/wp/v2/knowledge_hub/${id}`, { user, query: "&context=edit" });

describe("the yga field", () => {
  it("stores content and excerpt in the theme's ACF fields", async () => {
    const created = await create("admin", {
      yga: { content: "<p>Dit is waar de content komt.</p>", excerpt: "Kort." },
    });
    assert.equal(created.status, 201, JSON.stringify(created.json));
    // The create response already carries what was stored.
    assert.deepEqual(created.json.yga, {
      content: "<p>Dit is waar de content komt.</p>",
      excerpt: "Kort.",
    });

    const fetched = await read(created.json.id);
    assert.deepEqual(fetched.json.yga, created.json.yga);
  });

  it("stores them where the theme's templates read them, by field name", async () => {
    const created = await create("admin", {
      yga: { content: "<p>Inhoud</p>", excerpt: "Kort." },
    });
    const template = await rest("GET", "/stuurlui-test/v1/template", {
      query: `&id=${created.json.id}`,
    });
    // ACF formats the wysiwyg value through wpautop on output; the text is what matters.
    assert.match(template.json.content, /<p>Inhoud<\/p>/);
    assert.equal(template.json.excerpt, "Kort.");
  });

  it("changes the excerpt without blanking the rest of the settings group", async () => {
    const created = await create("admin", { yga: { content: "<p>x</p>" } });
    const id = created.json.id;
    // Set breadcrumbs the way the edit screen would, through the test-only route.
    const set = await rest("POST", "/stuurlui-test/v1/breadcrumbs", {
      user: "admin",
      body: { id, on: true },
    });
    assert.equal(set.status, 200, JSON.stringify(set.json));

    const updated = await rest("POST", `/wp/v2/knowledge_hub/${id}`, {
      user: "admin",
      body: { yga: { excerpt: "Nieuw." } },
    });
    assert.equal(updated.status, 200, JSON.stringify(updated.json));
    assert.equal(updated.json.yga.excerpt, "Nieuw.");
    assert.equal(updated.json.yga.content, "<p>x</p>");

    const after = await rest("GET", "/stuurlui-test/v1/breadcrumbs", {
      user: "admin",
      query: `&id=${id}`,
    });
    assert.equal(after.json.on, true);
  });

  it("strips scripts for users without unfiltered_html", async () => {
    const created = await create("contributor", {
      yga: { content: '<p onclick="x()">Hoi</p><script>alert(1)</script>' },
    });
    assert.equal(created.status, 201, JSON.stringify(created.json));
    assert.equal(created.json.yga.content, "<p>Hoi</p>alert(1)");
  });

  it("keeps HTML as-is for users with unfiltered_html", async () => {
    const html = '<div style="background: #170647;"><p>Hoi</p></div>';
    const created = await create("admin", { yga: { content: html } });
    assert.equal(created.json.yga.content, html);
  });

  it("rejects unknown properties", async () => {
    const created = await create("admin", { yga: { body: "<p>x</p>" } });
    assert.equal(created.status, 400);
  });

  it("is not visible to anonymous readers", async () => {
    const created = await create("admin", {
      status: "publish",
      yga: { content: "<p>x</p>" },
    });
    const anonymous = await rest(
      "GET",
      `/wp/v2/knowledge_hub/${created.json.id}`,
    );
    assert.equal(anonymous.status, 200);
    assert.equal(anonymous.json.yga, undefined);
  });
});

// Until 0.3.0: geo-contentmotor still sends and checks `geo`.
describe("the geo alias", () => {
  it("writes like yga, and both carry the stored values", async () => {
    const created = await create("admin", {
      geo: { content: "<p>Oud veld</p>", excerpt: "Kort." },
    });
    assert.equal(created.status, 201, JSON.stringify(created.json));
    const stored = { content: "<p>Oud veld</p>", excerpt: "Kort." };
    assert.deepEqual(created.json.geo, stored);
    assert.deepEqual(created.json.yga, stored);
  });

  it("is ignored when the request also has yga", async () => {
    const created = await create("admin", {
      yga: { content: "<p>Nieuw</p>" },
      geo: { content: "<p>Oud</p>", excerpt: "Oud." },
    });
    assert.equal(created.status, 201, JSON.stringify(created.json));
    assert.deepEqual(created.json.yga, { content: "<p>Nieuw</p>", excerpt: "" });
  });

  it("is not visible to anonymous readers", async () => {
    const created = await create("admin", {
      status: "publish",
      geo: { content: "<p>x</p>" },
    });
    const anonymous = await rest(
      "GET",
      `/wp/v2/knowledge_hub/${created.json.id}`,
    );
    assert.equal(anonymous.json.geo, undefined);
    assert.equal(anonymous.json.yga, undefined);
  });
});
