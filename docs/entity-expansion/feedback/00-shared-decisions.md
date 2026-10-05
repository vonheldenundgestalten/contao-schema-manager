# Shared planning decisions

[Planning index](../../entity-expansion-plan.md) · Round 1, 5 October 2026

This is the central feedback log. Fill responses here or refer to the decision ID. Blank means **open**, not approval. Start with D02, D03, D04 and D06; those shape the data model. The other decisions can follow in later rounds. No implementation starts from these unanswered proposals.

## D01 — Curated profiles and one coordinated expansion

**Status:** Open

**Recommendation:** Use a central definition mechanism with conditional field groups and explicit mappers. Keep familiar editing minimal; enable additional families by project. Deliver the accepted expansion together.

**Alternative / tradeoff:** A universal ontology editor exposes more types but makes validation, AI and editorial use much harder. Client-specific forms duplicate the same capabilities.

**Feedback needed:** Do you agree with profiles such as House model, Product system and Medical department, backed by shared fields? Which families must be in the coordinated release?

**Response:**

> Pending.

**Decision / date:** Pending.

## D02 — Separate organizations, places and responsibilities

**Status:** Open

**Recommendation:** Represent legal/organizational structure separately from physical sites; reuse a LocalBusiness as a place where it truly serves both roles. Use explicit provider/employer/manufacturer/organizer relationships.

**Alternative / tradeoff:** A single organization selector is simpler initially but becomes ambiguous. Creating separate business and place nodes everywhere adds unnecessary duplicates.

**Feedback needed:** Confirm the distinctions using the Diakonie and Woodmark diagrams requested in their feedback files. Which responsibilities need structured editing beyond an ordinary contact point?

**Response:**

> Pending.

**Decision / date:** Pending.

## D03 — Awards as recipient-specific result records

**Status:** Open

**Recommendation:** Keep existing award text. Add optional repeatable results with scheme, year, distinction, exact recipient, evidence and reporting links. One article may cover several results. Public output remains conservative award text and article relations.

**Alternative / tradeoff:** News-only fields cannot comfortably represent multiple results and an awards overview; a general fake Award type would misrepresent the vocabulary.

**Feedback needed:** Should results be editable beside the reporting News item as well as centrally? How should the awards overview be populated: existing content logic or a future optional module?

**Response:**

> Pending.

**Decision / date:** Pending.

## D04 — Source-owned data and selective public people

**Status:** Open

**Recommendation:** Bind entities to typed source records and resolve their real detail URLs. Use explicit public-directory eligibility and field allowlists for tl_member. Derived source fields link back to the original editor.

**Alternative / tradeoff:** Copying source data into schema records is initially simpler but drifts after imports. Automatic inclusion of all members would expose unrelated/private accounts.

**Feedback needed:** Confirm which source systems and routing patterns must be supported first. Should any source-derived fields allow explicit local overrides, and who maintains those after an import?

**Response:**

> Pending.

**Decision / date:** Pending.

## D05 — Topics and roles without misleading schema

**Status:** Open

**Recommendation:** Reuse source categories. Add reusable topic entities only when valuable across records. Store useful sales responsibilities internally when no precise public property fits; mark such graph edges accordingly.

**Alternative / tradeoff:** Forcing every category into Service and every sales role into knowsAbout makes output less truthful. Omitting internal links entirely loses editorial value.

**Feedback needed:** Is an explicitly labelled editorial-only relationship acceptable? Which categories need a shared identity rather than a label and CollectionPage?

**Response:**

> Pending.

**Decision / date:** Pending.

## D06 — Identity, language and multiple sites

**Status:** Open

**Recommendation:** One shared identity when it is genuinely the same thing, with explicit owning site, language homes and approved placements. Distinguish market contacts/offers from translation. Preserve existing imported IDs pending a reviewed migration.

**Alternative / tradeoff:** First translation row per language cannot handle same-language domains. Creating a new entity per page/language fragments the graph.

**Feedback needed:** Provide one concrete cross-domain reuse example and its preferred home. Which facts differ by site/market, rather than by language?

**Response:**

> Pending.

**Decision / date:** Pending.

## D07 — Events, venues and media scope

**Status:** Open

**Recommendation:** Integrate the parked calendar work through reusable places and source adapters. Retain standalone Event. Decide VideoObject and Course/CourseInstance scope explicitly; use manual coordinates.

**Alternative / tradeoff:** Copying venue addresses everywhere is easy but drifts. Making every recording an Event invents occurrence information. Training may warrant a separate editorial workflow.

**Feedback needed:** Should recordings be part of this expansion? Are actual course catalogues required now? For historical events, should changed venue facts be preserved through an explicit snapshot?

**Response:**

> Pending.

**Decision / date:** Pending.

## D08 — Existing output, import ownership and AI

**Status:** Open

**Recommendation:** Reserve known IDs, preserve additional properties and assign an owner to each emitter. Keep manual retirement of old HTML; do not revive the removed comparison feature. AI suggests evidenced improvements through the same definitions.

**Alternative / tradeoff:** Automatic replacement can silently discard unsupported facts or duplicate existing nodes. Preserving opaque JSON forever without ownership makes native edits confusing.

**Feedback needed:** For each legacy block, choose externally maintained or explicitly imported. Who reviews conflicting facts when a new native field overlaps preserved JSON?

**Response:**

> Pending.

**Decision / date:** Pending.

## D09 — Earlier requirements and scope completeness

**Status:** Open

**Recommendation:** Keep books/periodicals and vehicles on the same expansion decision list. Gather representative real pages before fixing editions/issues/model/stock behavior. No separate surprise mini-release.

**Alternative / tradeoff:** Committing all conceivable fields now is speculative; forgetting the earlier requirements would undermine the generalized design.

**Feedback needed:** Please add representative book, magazine/newspaper and vehicle pages when available. Which are required for the first coordinated expansion, and which may be explicitly deferred?

**Response:**

> Pending.

**Decision / date:** Pending.
