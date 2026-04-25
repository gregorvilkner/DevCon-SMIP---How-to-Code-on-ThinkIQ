# Topic 06 - OOP Techniques and Project Organization

How do you serve data to a browser script without dumping all your fetch boilerplate into the page? This topic walks through four iterations of the same idea — turning a few PHP scripts into a small "API + SDK + docs" trio — so the trade-offs at each step are visible. Pick the structure that matches the size of your project.

The four takes are numbered chronologically. **Take 4 is the recommended starting template.** The earlier takes are kept in the repo because seeing what gets simpler at each step *is* the lesson.

---

## Take 1 — Inline API in browser script

Two files. A PHP "API" with a `switch($f)` over `function`/`argument` std_inputs, and a browser script that consumes it by hand-building a `FormData` and POSTing to `/index.php?option=com_thinkiq&task=invokeScript`.

It works. It's also how a lot of SMIP projects start: the first time you need server-side data, you write the request inline. The downside becomes obvious the second time you do it — every consumer page repeats the same fetch boilerplate, and rename-the-function refactors require chasing it everywhere.

## Take 2 — Extract a JavaScript SDK

Four files. The new pieces are:

- **`00 Guzzle Client.php`** — a small PHP wrapper around `GuzzleHttp\Client` for hitting third-party REST APIs from PHP. Lives next to the API so multiple endpoints can share it.
- **`02 JavaScript SDK.php`** — pulls the fetch boilerplate out of the browser script and into a named SDK object (`ApiDemoSdk.GetLibraryNamesAsync()` etc.), one method per API endpoint. Loaded into consumer pages via `Script::includeScript('api_demo.api_demo__hyphen__javascript_sdk')`.
- **`03 JS SDK Documentation.php`** — a Vue page that renders one section per SDK method, doubling as both human-readable docs and a live test harness. The pattern: when the API ships, the docs *are* the test page.

What's still painful: the docs page hand-codes every section. Adding a method means editing four places (PHP API switch case, JS SDK method, doc page Vue template, doc page `methods:` block).

## Take 3 — Skip the JS SDK shim

Three files (notice the missing `02` — that's deliberate). Same shape as Take 2 minus the JavaScript SDK file: instead of `ApiDemoSdk.EchoAsync(arg)`, the doc page calls `tiqJSHelper.invokeScriptAsync(apiFileName, 'Echo', arg)` directly. Also renames the std_input convention from `function`/`argument` to the more generic `method`/`data`.

This take asks: do we even need the SDK shim if `tiqJSHelper` already provides the round-trip? For small APIs with one or two consumers, no. The SDK starts to pay for itself when the same endpoints are called from many pages, or when IDE autocomplete on method names is worth the extra file.

What's still painful: the doc page is *still* hand-coded section by section.

## Take 4 — Descriptor Catalog (recommended)

Four files. The big change is `02 API Tools.php`, which is now a **descriptor catalog** (`apiDemoTools[]`). Each entry declares everything the doc page needs to render and run a tool — name, category, description, input schema, output kind, and a `via:` field that selects the runner:

- `via: "php"` — round-trip through the PHP API (the Take 1–3 path).
- `via: "graphql"` — direct GraphQL from the browser, with an optional live query preview.
- `via: "js"` — pure browser-side handler, no network round-trip.

The doc page (`03`) is now a generic `v-for` over the catalog. Adding a new tool means adding one entry to the descriptor file — no template editing, no method-by-method `methods:` block. The page also picks up "doublet" demos for free: `GetEchoViaPhp` + `GetEchoViaJs`, `GetLibraryByNameViaPhp` + `GetLibraryByNameViaJs`. Same input, same output, two different paths — useful for teaching when each path makes sense.

The PHP API (`01`) also demonstrates the GraphQL counterpart: `GetLibraryByNameViaPhp` runs the same query server-side via `TiqUtilities\GraphQL\GraphQL`. Useful for callers that have no browser (cron jobs, server-to-server integrations).

Take 4 also ships a more substantive Guzzle example — the client points at PokéAPI (unauthenticated) with the API-key wiring kept as commented-out reminders. Why PokéAPI? It's CORS-permissive and has nice distinctive payloads, which makes the "REST goes through PHP because of CORS" lesson concrete. Try `pikachu`, `charizard`, `mewtwo`.

---

## Install names

Each PHP file in the takes is loaded into the SMIP under a script name (used by `Script::includeScript`). The Take 4 wiring uses:

- `api_demo.guzzle_client`
- `api_demo.api_demo_api`
- `api_demo.api_demo_api_tools`
- `api_demo.api_demo_api_documentation`

Adjust the `includeScript` calls in `01` and `03` if your install uses different library / script names.

![Screenshot](./img/apiDemo.png#center)
*Image: Example documentation page with return data*

![Screenshot](./img/apiDemoLibrary.png#center)
*Image: Project / library structure*
