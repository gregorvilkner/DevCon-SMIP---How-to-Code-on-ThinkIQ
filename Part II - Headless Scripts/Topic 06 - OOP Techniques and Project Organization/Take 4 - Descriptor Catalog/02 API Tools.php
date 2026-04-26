<script>
//
// apiDemoTools — descriptor catalog for the API Demo Documentation page.
//
// The catalog deliberately mixes data-access methods so the page doubles as a
// teaching demo. Where possible, tools are paired ("…ViaPhp" + JS twin) so the
// reader can compare server-side vs. browser-side access to the same thing.
//
//   Plumbing            GetEchoViaPhp           +  GetEchoViaJs
//   GraphQL             GetLibraryByNameViaJs   +  GetLibraryByNameViaPhp
//   REST (no twin)      GetPokemon
//
// GetPokemon has no JS twin on purpose — a browser-side fetch to a third-party
// REST host gets blocked by CORS in almost every case. Server-to-server HTTP
// is not subject to CORS, so the PHP path is the natural home for REST.
//
// Schema:
//   key          — camelCase identifier; used internally for state keys (must be unique).
//   name         — display name shown in the H1.
//   category     — short badge string rendered next to the name (e.g. "GraphQL (PHP)").
//   description  — H6 blurb shown next to the toggle caret.
//   via          — runner selector:
//                    "php"     → tiqJSHelper.invokeScriptAsync(apiFileName, fn, argument)
//                    "graphql" → tiqJSHelper.invokeGraphQLAsync(query(args)) → transform()
//                    "js"      → handler(args). Pure browser-side; never hits the server.
//   inputs       — array of input declarations (see below). Empty = no input form.
//                  When `via:"php"` and inputs is empty, the third arg to
//                  invokeScriptAsync is `null`.
//   output       — { kind } where kind is one of:
//                    "array"        — array result; clear → []; counter "{N} results."
//                    "single"       — single object or null; clear → null; counter.
//                    "single-quiet" — single value; clear → null; no counter (Echo-style).
//   render       — optional. Declarative hints for how to display the output above
//                  the raw JSON dump. The JSON dump is always shown — render is
//                  additive. Currently supported:
//                    images — array of { label, path } where path is a list of
//                             keys into the output object (e.g. ["sprites","front_default"]).
//                             Missing paths are silently skipped, so half-populated
//                             responses don't break the layout. Path is an array
//                             of segments (not a dotted string) so keys with
//                             hyphens like "official-artwork" work without escaping.
//
// PHP-specific fields (via: "php"):
//   fn           — string passed as the second argument to invokeScriptAsync;
//                  must match a "case" label in api_demo_api.php.
//
// GraphQL-specific fields (via: "graphql"):
//   query            — function (args) => string. Receives the parsed inputs object
//                      keyed by argName/key and returns the rendered GraphQL query.
//                      Use JSON.stringify() to interpolate string scalars safely.
//   transform        — optional function (response) => any. Pulls the meaningful
//                      result out of the GraphQL envelope. Defaults to r => r?.data.
//   showQueryPreview — if true, renders the rendered GraphQL query in a live <pre>
//                      below the inputs (mirrors the showJsonPreview behaviour).
//
// JS-specific fields (via: "js"):
//   handler          — function (args) => any  (or async (args) => any).
//                      Receives the parsed inputs object and returns the result
//                      directly. No network round-trip.
//
// Input declaration:
//   key             — camelCase key under inputs (used for v-model state binding).
//   argName         — name of the field on the args object passed to the runner.
//                       PHP runner    → matches $a->… in the API Template.
//                       GraphQL runner→ matches the property name read by query(args).
//                       JS runner     → matches the property name read by handler(args).
//                     Defaults to `key` if omitted.
//   label           — text shown in the <label> before the input.
//   type            — HTML input type ("text" | "number").
//   default         — initial v-model value (string).
//   parse           — "raw" | "json" | "number". The doc page parses this before
//                     building the args object.
//   showJsonPreview — if true, renders a live JSON-validation <pre> below the input.
//

var apiDemoTools = [

    //
    // Plumbing doublet — Echo
    //

    {
        key:         "getEchoViaPhp",
        name:        "GetEchoViaPhp",
        category:    "Plumbing (PHP)",
        description: "Round-trip echo through the PHP API. Browser → tiqJSHelper.invokeScriptAsync → api_demo_api.php → back. SDK plumbing check; touches no library or external service.",
        via:         "php",
        fn:          "GetEchoViaPhp",
        inputs: [
            { key: "input", argName: "hello", label: "Input: ", type: "text",
              default: '{"a":[23, 23, "asd"]}',
              parse: "json", showJsonPreview: true }
        ],
        output: { kind: "single-quiet" }
    },

    {
        key:         "getEchoViaJs",
        name:        "GetEchoViaJs",
        category:    "Plumbing (JS)",
        description: "Round-trip echo entirely in the browser — never leaves the page. JS twin of GetEchoViaPhp; demonstrates the descriptor system can host pure-JS tools alongside server-backed ones.",
        via:         "js",
        handler:     (args) => args.hello,
        inputs: [
            { key: "input", argName: "hello", label: "Input: ", type: "text",
              default: '{"a":[23, 23, "asd"]}',
              parse: "json", showJsonPreview: true }
        ],
        output: { kind: "single-quiet" }
    },

    //
    // GraphQL doublet — library lookup by display name
    //

    {
        key:         "getLibraryByNameViaJs",
        name:        "GetLibraryByNameViaJs",
        category:    "GraphQL (JS)",
        description: "Looks up a library by display name via direct GraphQL — bypasses the PHP API entirely. Browser → tiqJSHelper.invokeGraphQLAsync → SMIP PostGraphile endpoint.",
        via:         "graphql",
        inputs: [
            { key: "input", argName: "displayName", label: "Display name: ", type: "text",
              default: 'ThinkIQ Base Library',
              parse: "raw" }
        ],
        // (args) => GraphQL query string. JSON.stringify quotes & escapes the
        // scalar correctly for a GraphQL string literal.
        query: (args) => `
            query GetLibraryByName {
                libraries(condition: { displayName: ${JSON.stringify(args.displayName)} }) {
                    id
                    displayName
                }
            }
        `,
        // PostGraphile returns an array under data.libraries; collapse to first match.
        transform:        r => (r && r.data && r.data.libraries && r.data.libraries[0]) || null,
        showQueryPreview: true,
        output:           { kind: "single" }
    },

    {
        key:         "getLibraryByNameViaPhp",
        name:        "GetLibraryByNameViaPhp",
        category:    "GraphQL (PHP)",
        description: "Same lookup, same query, but the GraphQL round-trip happens server-side. Browser → api_demo_api.php → TiqUtilities\\GraphQL\\GraphQL::MakeRequest → SMIP PostGraphile. The natural path for callers that have no browser (cron jobs, integrations, server-to-server).",
        via:         "php",
        fn:          "GetLibraryByNameViaPhp",
        inputs: [
            { key: "input", argName: "displayName", label: "Display name: ", type: "text",
              default: 'ThinkIQ Base Library',
              parse: "raw" }
        ],
        output: { kind: "single" }
    },

    //
    // REST — no JS twin, intentionally. Browser fetch to a third-party REST host
    // gets blocked by CORS; PHP is the only home for this path.
    //

    {
        key:         "getPokemon",
        name:        "GetPokemon",
        category:    "REST (PHP only — CORS)",
        description: "Looks up a Pokémon by name or Pokédex number on PokéAPI (https://pokeapi.co). PHP-side: api_demo_api.php → Guzzler::GetAsync(\"pokemon/{nameOrId}\"). Try: pikachu (25), charizard (6), bulbasaur (1), eevee (133), mewtwo (150), mew (151), snorlax (143), gengar (94). General principle: REST goes through PHP because a browser-side fetch to a third-party host typically gets blocked by CORS. (PokéAPI happens to be CORS-permissive, so a JS twin would work for this specific endpoint — but you can't assume that for third-party APIs in general.)",
        via:         "php",
        fn:          "GetPokemon",
        inputs: [
            { key: "input", argName: "nameOrId", label: "Name or Pokédex #: ", type: "text",
              default: 'pikachu',
              parse: "raw" }
        ],
        output: { kind: "single" },
        render: {
            images: [
                { label: "Default",        path: ["sprites", "front_default"] },
                { label: "Shiny",          path: ["sprites", "front_shiny"] },
                { label: "Back",           path: ["sprites", "back_default"] },
                { label: "Official art",   path: ["sprites", "other", "official-artwork", "front_default"] }
            ]
        }
    }

];

</script>
