# Take 4 — Descriptor Catalog (recommended template)

Replaces the hand-coded doc page with a generic `v-for` over a descriptor catalog. Each entry declares everything the page needs to render and run a tool — name, description, inputs, output kind, optional image rendering, and a `via:` field that selects the runner (`php`, `graphql`, or `js`). Adding a new tool is one entry in `02 API Tools.php`. No template editing.

## Files

- `00 Guzzle Client.php` — points at PokéAPI (CORS-permissive, distinctive payloads); API-key wiring kept as commented reminders for when you re-target at an authenticated API.
- `01 API Template.php` — PHP dispatch. Demonstrates REST via Guzzle (`GetPokemon`) and server-side GraphQL via `TiqUtilities\GraphQL\GraphQL` (`GetLibraryByNameViaPhp`).
- `02 API Tools.php` — the descriptor catalog (`apiDemoTools[]`). Single source of truth for what shows up on the doc page.
- `03 API Documentation.php` — generic Vue page that renders any descriptor. Supports image strips, GraphQL query previews, clipboard copy.

## What this take adds

A tool's full UI — input form, runner, output formatting, optional image previews — is one descriptor entry. The same page hosts PHP, GraphQL, and pure-JS tools side-by-side; "doublet" demos (`...ViaPhp` + `...ViaJs`) come for free, which is useful for teaching when each access path makes sense.

The `render.images` field on the GetPokemon descriptor demonstrates the extension hook for richer output. Same hook accommodates Raman spectra (line-chart kind), microscopy images (zoomable viewer kind), camera frames — a new render kind is built once, then declared per-descriptor.

## Try it in your SMIP

`library_export.json` in this folder is an importable SMIP library named `api_demo_take_4`. Import it (Settings → Libraries → Import), then open the "API Documentation" script and try `pikachu`, `mewtwo`, or pokédex number `25` against GetPokemon — sprites render above the JSON.

See the [topic README](../README.md) for the full four-take progression.
