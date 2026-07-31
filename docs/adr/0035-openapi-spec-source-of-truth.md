# 35) OpenAPI spec source of truth {#adr_0035}

Date: 2026-08-04

## Status

accepted

## Context

The Wikibase REST API is described by a handcrafted OpenAPI specification, assembled by `redocly` from fragments under `repo/domains/*/specs/` into `repo/rest-api/src/openapi.json`. The route handlers under the same domains implement that API. The two are not peers: the spec is the description and the handlers are the implementation, and the team works spec-first. The description is designed, then implemented.

[T429335](https://phabricator.wikimedia.org/T429335) asks for the REST endpoints of Wikibase extensions, starting with WikibaseLexeme ([T433337](https://phabricator.wikimedia.org/T433337)), to be documented alongside Wikibase's own, reusing our existing definitions. [T433336](https://phabricator.wikimedia.org/T433336) is the aggregation piece of that, and it forces a decision: which pipeline do extension contributions plug into, and where does the specification itself live?

Serving a `/openapi.json` endpoint that reflects the extensions actually installed on a wiki was split into its own task, [T433593](https://phabricator.wikimedia.org/T433593). It is not decided here, but each option below is assessed for whether it leaves a workable path to it. One constraint holds across all of them: nothing at runtime resolves `$ref`s or runs the spec toolchain, so each contribution to the served document has to exist in its tree as a self-contained committed artifact — the merged document itself does not have to be pre-built on disk.

Before comparing aggregation approaches, we checked whether generating the specification from the handlers would make the question moot. It would not; the reasoning is recorded first because it constrains everything after it.

## Why we are not generating the specification from the route handlers

MediaWiki core can generate an OpenAPI document from route handlers: `Handler::getOpenApiSpec()` derives `parameters` from `getParamSettings()` and `getHeaderParamSettings()`, `requestBody` from `getBodyParamSettings()`, response schemas from `getResponseBodySchemaFileName()` (1.43) and examples from `getResponseBodyExampleFileName()` (1.47), plus security requirements and deprecation notices. `ModuleSpecHandler` assembles and serves the result per module. Wikibase is already half-migrated towards it: `repo/rest-api/wikibase.v1.json` is a real `mwapi 1.1.0` module file, and every CRUD handler implements `getParamSettings()` and `getBodyParamSettings()` because the request validator requires them.

Comparing core's generated document for `wikibase/v1` against the handcrafted one shows that route coverage is already solved. The two agree on all 33 paths, with an empty set difference in both directions. The gap is entirely in descriptive richness:

| | Generated | Handcrafted |
|---|---|---|
| Paths | 33 | 33 |
| Parameters on `GET /entities/items/{item_id}` | 2 | 7 |
| Parameter descriptions | placeholder (`"item_id parameter"`) | prose with examples |
| Response statuses | 2 (`200`, `default`) | 7 (`200`, `304`, `308`, `400`, `404`, `412`, `500`) |
| `200` response schema | absent | present |
| Summary and description | present, **translatable** | present, English only |

Most of that gap maps to hooks that exist and could be filled in, laboriously. The decisive obstacle is elsewhere: **core cannot resolve `$ref`s, and gives handlers no way to register reusable components.** `Module::loadJsonFile()` is `file_get_contents` plus `json_decode` with no reference resolution, so a `$ref` in a handler's schema file is emitted verbatim. `ModuleSpecHandler::getComponentsSpec()` carries an explicit upstream TODO ("XXX: also collect reusable components from handler specs (but how to avoid name collisions?)"), so the only resolvable components are core's own. The Wikibase specification makes **1,120 `$ref` uses against 94 shared components** (37 responses, 25 examples, 19 parameters, 7 schemas, 5 headers, 1 request body). All of it would have to be inlined per handler, which directly contradicts T429335's requirement to reuse existing definitions.

Further obstacles, measured and confirmed experimentally:

* Module-file `parameters` and `responses` are silently ignored: `Handler::getOpenApiSpec()` merges with `$spec += $this->openApiSpec` after pre-setting both keys, so PHP's array union discards them. A module file can contribute `summary`, `description`, `tags`, `externalDocs` and `operationId`; everything else needs a PHP override per handler.
* `generateResponseSpec()` hardcodes `200` plus a `default` `GenericErrorResponse`; documenting `400`, `404` and `412` individually requires overriding it per handler, with no declarative mechanism.
* `Router` throws on a duplicate module id, so an extension cannot add paths to `wikibase/v1` without its own module or an upstream hook.
* doc.wikimedia.org and the `@wmde/wikibase-rest-api` publishing workflow both need a static file, so runtime generation additionally needs a dump script or a CI step against a live wiki, and Redocly's `filterSchemas` decorator and tag groups would be lost.

## Considered Actions

All three options below have working proofs of concept, linked in each section.

### Option 1: Collect extension fragments in the Wikibase build

Proof of concept: [Wikibase change 1319428](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1319428).

Extensions declare a committed spec fragment through an `attributes.Wikibase.RestApiSpecFragments` entry in their own `extension.json`. A collector script in `repo/rest-api/` scans the extension tree, and the existing `redocly` build joins whatever fragments are on disk into an aggregated document. With no contributing extensions present, the build no-ops to today's output.

Pros:

* The spec toolchain stays in one place, next to the shared components it joins against. A contributing extension only commits a fragment and one attribute, no tooling of its own.
* Lands without touching any extension, and unblocks T433337 immediately.
* The aggregated build is a straight extension of the pipeline we already run.

Cons:

* The collector scans the working tree for sibling extensions, so the build output depends on what happens to be checked out.
* `redocly join` resolves each input file's `$ref`s before merging, so fragments cannot use bare `#/components/...` references. They must reference Wikibase's generated `specs/openapi-joined.json` by relative file path, which couples every fragment to Wikibase's on-disk layout and requires Wikibase's `spec:join` to have run first. Joining against the dereferenced `src/openapi.json` instead fails with component conflicts.
* A subset of Wikibase's shared components becomes a cross-repository interface that fragments reference by path, and it has to be nominated and kept stable by convention.

Change workflows:

* Spec change only: edit the spec sources (in Wikibase) or the fragment (in the contributing extension), rebuild, and commit the result in the same repo; one change in one review. The caveat is that a Wikibase change that moves or renames a shared component can break extension fragments referencing it by path, and that breakage surfaces wherever the aggregated build next runs, not in the change that caused it (the pre-merge quibble join above is the mitigation).
* Route change only: an ordinary code change in the owning repo; the spec pipeline is not involved.
* Both: one atomic change in the owning repo carrying the code and its description together. This is the tightest spec-and-code coupling of the three options.

Runtime endpoint and docs publishing: the docs gap has a two-part resolution, neither in place yet. The docs-publish job needs an extension tree (an `integration/config` change; today it runs on a Wikibase-only checkout, so the published doc would silently miss extension paths), and the aggregated join should also run in a job that already has the tree (the quibble jobs check out WikibaseLexeme via `zuul` dependencies) so a Wikibase change that breaks an extension's fragment fails pre-merge rather than later in the other repo. For the runtime endpoint (T433593) no clear path was found: aggregation happens at build time in Wikibase's tree, and fragments keep their `$ref`s for the join while the committed document is dereferenced, so serving an instance-accurate document would need machinery this option does not provide.

### Option 2: Build the combined document in the contributing extension

Proof of concept: [WikibaseLexeme change 1319503](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/WikibaseLexeme/+/1319503), CI green with zero Wikibase changes.

The Wikibase build stays exactly as it is and continues to produce the Wikibase-only document. Each contributing extension gets its own build step: it dereferences its fragment (`redocly bundle --dereferenced`) and joins it against Wikibase's committed `src/openapi.json` into a combined bundle. The combined bundle stays a build-time artifact throughout: gitignored, rebuilt on demand for linting and docs, never committed. What each extension commits is its dereferenced fragment, which satisfies the on-disk constraint from the context section for serving: Wikibase's handler joins the registered fragments into its own committed document at runtime. The raw fragment cannot be registered as-is, since its file `$ref`s need resolution that nothing at runtime provides; the dereferenced one is self-contained. Conflicts are catchable in a PHPUnit test. The committed dereferenced fragment and the runtime join are designed but not part of the proof.

Pros:

* Zero changes in Wikibase. Each extension owns its spec build and its combined artifact end to end.
* Extends naturally per extension: every contributing extension commits its dereferenced fragment, and the runtime endpoint (T433593) merges the fragments of what is installed into Wikibase's document. This is the most direct path to that task of the three.
* The join was proven with both sides dereferenced: the fragment joins cleanly against Wikibase's committed artifact, with the Wikibase side byte-identical in the output.

Cons:

* Every contributing extension duplicates the `redocly` toolchain, build scripts and lint configuration that Wikibase already carries.
* The spec build needs a Wikibase checkout next to the extension. Developers have one (other extension tests already assume the `../Wikibase` layout, and the path can be made configurable for non-standard setups), but the extension's node CI job checks out only the extension itself, so that job cannot run the spec build; the proof of concept's spec tests [skip themselves when the checkout is missing](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/WikibaseLexeme/+/1319503/2/tests/spec/specCombine.test.js#17). The api-testing job already checks out the dependency tree in the sibling layout the build assumes, so the build can run there; catching Wikibase-side staleness pre-merge additionally requires the check to run with the extension checked out on Wikibase's gate.
* Dereferencing orphans Wikibase's shared components in the combined output (`no-unused-components` has to be relaxed), so the combined artifact loses its component structure. This is minor and solvable, by scoping the lint relaxation to the combined artifact or pruning unused components in the build.

Change workflows:

* Spec change only, in a contributing extension: edit the fragment, rebuild the committed artifact, commit both in the extension; one change in one review.
* Spec change only, in Wikibase: commit the rebuilt `src/openapi.json` in Wikibase as today, and every contributing extension whose committed artifact embeds an affected definition needs a follow-up change rebuilding it. One Wikibase spec change fans out into refresh changes across the extensions whose fragments inline an affected shared definition, and until each lands, that extension serves and documents stale copies.
* Route change only: an ordinary code change in the owning repo; the spec pipeline is not involved.
* Both: atomic within a contributing extension (code, fragment and rebuilt artifact in one change); a Wikibase-side change of both still fans out as above.

Runtime endpoint and docs publishing: the runtime endpoint is part of this option's design rather than a leftover: each installed extension registers its committed dereferenced fragment through a hook, and Wikibase's single handler joins the registered fragments into its own document and serves the result, with conflicts catchable in a PHPUnit test. This registration and merge mechanism is the same one option 3 would use; what differs is only what each extension registers. For docs publishing there are two candidate resolutions, neither tried: a docs job that has the dependency tree (the same sibling-checkout requirement discussed in the cons) builds the combined bundle fresh at publish time, or the docs build consumes the merged document from the runtime endpoint, which ties the documentation pipeline to T433593 landing first and to fetching from a live wiki.

### Option 3: Move the specification sources to a versioned spec repository

Proof of concept: [wikibase-rest-spec on Wikimedia GitLab](https://gitlab.wikimedia.org/itamar/wikibase-rest-spec), with the consumer side in [Wikibase change 1319823](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1319823) (`spec:pull` / `spec:diff` scripts) and the registry pin plus CI guard in [Wikibase change 1319844](https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Wikibase/+/1319844), verified by Wikimedia CI.

All spec sources and the `redocly` tooling move verbatim into one standalone repository, which publishes per-consumer artifacts under a single semver version to its GitLab package registry as an npm package: the core document for Wikibase, and a self-contained dereferenced fragment of just its own paths for each contributing extension. Each consumer pins an exact devDependency, vendors its artifact (Wikibase keeps its committed `src/openapi.json` exactly as today, refreshed by `spec:pull`), and `spec:diff` in `npm test` guards against a stale vendored copy.

What the proof established: the built artifact is byte-identical to Wikibase's committed `src/openapi.json`; the repository's CI publishes snapshot versions on every push to main and guarded tag releases (`0.1.0-rc.1` is live, anonymous installs work); and Wikimedia CI reaches the GitLab registry, installs the pinned package and runs the `spec:diff` guard, proven by a passing verified run on change 1319844.

Pros:

* No cross-repository fragment collection. The sources are still fragments that `redocly` joins, but the join runs against one repository at one version instead of scanning whatever extensions happen to be checked out. The whole API is designed and reviewed in one place, and the shared components never become a cross-repository interface; they stay internal to the spec repository.
* The specification gets its own semver history, independent of any extension's release train. Version skew between consumers is detectable by comparing pins, which also gives the runtime endpoint (T433593) a safety property: all vendored artifacts descend from one version, so component conflicts cannot happen when the pins agree. Option 2 can replicate this safety by recording the Wikibase spec state each fragment was built against and comparing it in CI, at the cost of the convention and CI work that the registry pin makes automatic; the base document's `info.version` alone cannot carry it, since it tracks production-readiness milestones rather than spec states.
* Consumer repositories shed the `redocly` toolchain and the spec build entirely; their `npm test` keeps only the `spec:diff` guard.
* The consumer workflow is proven end to end in real Wikimedia CI, not just locally.

Cons:

* A second repository and a second review system: GitLab merge requests next to Gerrit changes, one more repository to maintain and keep in mind.
* A change that touches both the spec and its implementation splits into two reviews in two systems that have to land in a coordinated order.
* The contract lives away from the code implementing it. An extension cannot contribute its spec without a change in the shared spec repository, which is a different extensibility model from extension-owned fragments; whether the Wikibase REST API surface should be treated as an extensible interface at all is itself under discussion and not settled by this record.
* Local development gets an extra moving part: spec work happens in a second checkout with its own node toolchain, and iterating on a spec change against a running wiki means pulling the rebuilt artifact into the consumer on every cycle. Mounting the spec repository into the dev environment reduces this to running the local pull, but it is setup that options 1 and 2 do not need.

Change workflows:

* Spec change only: a merge request in the spec repository, then a release, then a Gerrit change in each affected consumer bumping the pin and committing the `spec:pull` result. Two systems, in order. Consumers that are not affected simply stay on their pinned version; `spec:diff` holds each consumer to its own pin, not to the latest release, so nothing forces a lockstep update. Production is untouched by the registry side of this: it ships only the committed vendored file, and the npm machinery exists for developers and CI alone.
* Route change only: an ordinary Gerrit change in the owning repo; the spec repository is not involved.
* Both: the spec merge request has to land and release first, then one consumer change carries the code, the pin bump and the refreshed artifact together. The code and its description cannot land atomically in one review; the pin is what keeps the two halves consistent.

Runtime endpoint and docs publishing: same mechanism as option 2. Each installed extension registers its vendored artifact through a hook, and Wikibase's handler merges, caches and serves the result. The difference is where the artifacts come from: they all descend from one published spec version, so as long as the consumers pin the same version, their shared definitions are identical by construction. The only thing that can go wrong is a version mismatch between pins, and that is easy to check. The vendored artifact is also the file the route serves, so the committed copy each consumer carries earns its keep twice. This shape is assessed but not built. For docs publishing, the spec repository already publishes the full document as an artifact, so the docs job could download it instead of building anything; whether doc.wikimedia.org can publish from a GitLab repository's CI is an open question for releng.

## Decision

Option 2 is chosen. Contributing extensions own their spec build and commit a self-contained dereferenced fragment, and Wikibase's handler joins the registered fragments into its own document at runtime (T433593).

The deciding weights were atomicity and self-service: a change to an extension's routes and their description lands as one change in one review in the owning repository, and an extension contributes its spec without a change in any shared repository.

Option 1 was rejected for its coupling and its runtime dead end: the aggregated build depends on which extensions happen to be checked out, fragments are bound to Wikibase's on-disk layout by file-path `$ref`s, and no workable path to the runtime endpoint was found, while the runtime join is central to the chosen design. Option 3 was rejected on cost rather than capability: its consistency guarantees are the strongest of the three, enforced by tooling where option 2 relies on convention, but they are bought with a second repository and review system, spec and code changes that cannot land atomically, and an extensibility model in which an extension cannot contribute its spec without a change in the shared repository. The runtime registration-and-merge mechanism is identical in options 2 and 3, and extension-owned tooling can later be packaged up into a shared repository far more easily than a shared repository can be unwound, so moving the sources into a versioned spec repository remains open if convention proves insufficient (see consequences).

The decision is conditional on closing the staleness gap in CI: the extension spec build runs in the api-testing job, and the check also runs with the extension checked out on Wikibase's gate, so a Wikibase change that stales an extension's embedded copies fails pre-merge instead of surfacing later in the other repository.

## Consequences

* WikibaseLexeme (T433337) proceeds per option 2: it commits its dereferenced fragment and owns its spec build and combined artifact.
* The spec build and staleness check are wired into the api-testing jobs on both the extension's and Wikibase's gates. This CI work is a precondition of the decision, not a follow-up.
* The runtime endpoint (T433593) follows the registration-and-merge design shared by options 2 and 3: each installed extension registers its committed dereferenced fragment through a hook, and Wikibase's handler joins and serves the result.
* Option 3 is to be revisited if convention-based consistency fails in practice: if Wikibase shared-definition changes fan out into disruptive refresh changes across extensions, or if version skew between committed fragments reaches a served or published document.
