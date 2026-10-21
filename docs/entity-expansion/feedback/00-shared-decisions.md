# Shared planning decisions

[Planning index](../../entity-expansion-plan.md) · Round 1 responses received, 5 October 2026

**Your answers below are preserved verbatim.** The original Open/Pending placeholders are retained as part of that feedback. See [Round 2 synthesis](../round-2.md) for the interpretation and [next feedback](01-round-2.md) for outstanding questions. These answers do not authorize implementation.

This is the central feedback log. Fill responses here or refer to the decision ID. Blank means **open**, not approval. Start with D02, D03, D04 and D06; those shape the data model. The other decisions can follow in later rounds. No implementation starts from these unanswered proposals.

## D01 — Curated profiles and one coordinated expansion

**Status:** Open

**Recommendation:** Use a central definition mechanism with conditional field groups and explicit mappers. Keep familiar editing minimal; enable additional families by project. Deliver the accepted expansion together.

**Alternative / tradeoff:** A universal ontology editor exposes more types but makes validation, AI and editorial use much harder. Client-specific forms duplicate the same capabilities.

**Feedback needed:** Do you agree with profiles such as House model, Product system and Medical department, backed by shared fields? Which families must be in the coordinated release?

**Response:**
For a generic extension, I would much rather go broad and add let's say 100 additional entity types, so it can work in as many situations as possible. I also don't like the idea of a ontology editor, or rather: i like the idea a lot, but it seems like this puts too much work into the hands of the user. So instead of your pretty small selection: come up with maybe 100 use cases for things that people want to sell online - but critically: limit yourself to things not quickly and usually sold in an online store. Those will be handled by shopping software, not in the scope of a custom CMS solution. We usually handle complex and/or big items and services.


> Pending.

**Decision / date:** Pending.

## D02 — Separate organizations, places and responsibilities

**Status:** Open

**Recommendation:** Represent legal/organizational structure separately from physical sites; reuse a LocalBusiness as a place where it truly serves both roles. Use explicit provider/employer/manufacturer/organizer relationships.

**Alternative / tradeoff:** A single organization selector is simpler initially but becomes ambiguous. Creating separate business and place nodes everywhere adds unnecessary duplicates.

**Feedback needed:** Confirm the distinctions using the Diakonie and Woodmark diagrams requested in their feedback files. Which responsibilities need structured editing beyond an ordinary contact point?

**Response:**
I would also like to have an entity relationship properly set up, because Diakone and Woodmark both actually have multiple companies connected. Go with your recommendation. Companies, localBusiness for offices as a good point for now.


> Pending.

**Decision / date:** Pending.

## D03 — Awards as recipient-specific result records

**Status:** Open

**Recommendation:** Keep existing award text. Add optional repeatable results with scheme, year, distinction, exact recipient, evidence and reporting links. One article may cover several results. Public output remains conservative award text and article relations.

**Alternative / tradeoff:** News-only fields cannot comfortably represent multiple results and an awards overview; a general fake Award type would misrepresent the vocabulary.

**Feedback needed:** Should results be editable beside the reporting News item as well as centrally? How should the awards overview be populated: existing content logic or a future optional module?

**Response:**
I would really welcome the additional Award entity, and link it to the NewsArticle, it's too hard to pick out the specific award fields from the news content. Same for Certifications. But the award should have the fields you mentioned above (where recipient can be any other entity, in Okal's examples it's almost always HouseModels that win the award). I honestly don't know how to do this correctly, because the official award is just text, and never a lot, mostly the name and year of the award. You have a good idea how to structure it?


> Pending.

**Decision / date:** Pending.

## D04 — Source-owned data and selective public people

**Status:** Open

**Recommendation:** Bind entities to typed source records and resolve their real detail URLs. Use explicit public-directory eligibility and field allowlists for tl_member. Derived source fields link back to the original editor.

**Alternative / tradeoff:** Copying source data into schema records is initially simpler but drifts after imports. Automatic inclusion of all members would expose unrelated/private accounts.

**Feedback needed:** Confirm which source systems and routing patterns must be supported first. Should any source-derived fields allow explicit local overrides, and who maintains those after an import?

**Response:**
Don't overwrite, I would use the source fields as much as possible - our "additional" fields should only provide what the original element does not have. No double-editing. The mapping of course will be hard, especially on custom elements and data types like the OKAL tl_houses (which is home to HouseModels and Demo houses). Getting this mapping logic right is a major milestone, together with D01 for which entities to add. Maybe custom data types like for okal and viacor need a developer-facing API, and not even be handled in our extension? The same thing could then be used for Members being used as Persons, or at least the filtering if only certain members should be included. Again: smart, universally useful logic needed. Which is double-true when translation is also important (like at Viacor). The custom dev approach could also open the door for additional entity types, so the extension core can stay clean of those complexities.

> Pending.

**Decision / date:** Pending.

## D05 — Topics and roles without misleading schema

**Status:** Open

**Recommendation:** Reuse source categories. Add reusable topic entities only when valuable across records. Store useful sales responsibilities internally when no precise public property fits; mark such graph edges accordingly.

**Alternative / tradeoff:** Forcing every category into Service and every sales role into knowsAbout makes output less truthful. Omitting internal links entirely loses editorial value.

**Feedback needed:** Is an explicitly labelled editorial-only relationship acceptable? Which categories need a shared identity rather than a label and CollectionPage?

**Response:**
I don't get what we're talking about here, probably an example would help.


> Pending.

**Decision / date:** Pending.

## D06 — Identity, language and multiple sites

**Status:** Open

**Recommendation:** One shared identity when it is genuinely the same thing, with explicit owning site, language homes and approved placements. Distinguish market contacts/offers from translation. Preserve existing imported IDs pending a reviewed migration.

**Alternative / tradeoff:** First translation row per language cannot handle same-language domains. Creating a new entity per page/language fragments the graph.

**Feedback needed:** Provide one concrete cross-domain reuse example and its preferred home. Which facts differ by site/market, rather than by language?

**Response:**
We're already on a good path here, with central truths in the entity directly, and only using translation fields when the content can / should differ in language versions. Which in Contao will always be the URL connected, a URL can always only be one language content.

> Pending.

**Decision / date:** Pending.

## D07 — Events, venues and media scope

**Status:** Open

**Recommendation:** Integrate the parked calendar work through reusable places and source adapters. Retain standalone Event. Decide VideoObject and Course/CourseInstance scope explicitly; use manual coordinates.

**Alternative / tradeoff:** Copying venue addresses everywhere is easy but drifts. Making every recording an Event invents occurrence information. Training may warrant a separate editorial workflow.

**Feedback needed:** Should recordings be part of this expansion? Are actual course catalogues required now? For historical events, should changed venue facts be preserved through an explicit snapshot?

**Response:**
Skip standalone recordings, but include them when the recording is not the main content - just "a" content in an event. Which is what Agorum is doing. This is still events, we're not handling courses or similar right now.


> Pending.

**Decision / date:** Pending.

## D08 — Existing output, import ownership and AI

**Status:** Open

**Recommendation:** Reserve known IDs, preserve additional properties and assign an owner to each emitter. Keep manual retirement of old HTML; do not revive the removed comparison feature. AI suggests evidenced improvements through the same definitions.

**Alternative / tradeoff:** Automatic replacement can silently discard unsupported facts or duplicate existing nodes. Preserving opaque JSON forever without ownership makes native edits confusing.

**Feedback needed:** For each legacy block, choose externally maintained or explicitly imported. Who reviews conflicting facts when a new native field overlaps preserved JSON?

**Response:**
I like the recommendation, but I'm not sure how to handle your problem case. I would say it's an edge case for the core extension and not our problem right now. Keep the handling simple, what we have for importing is good enough.


> Pending.

**Decision / date:** Pending.

## D09 — Earlier requirements and scope completeness

**Status:** Open

**Recommendation:** Keep books/periodicals and vehicles on the same expansion decision list. Gather representative real pages before fixing editions/issues/model/stock behavior. No separate surprise mini-release.

**Alternative / tradeoff:** Committing all conceivable fields now is speculative; forgetting the earlier requirements would undermine the generalized design.

**Feedback needed:** Please add representative book, magazine/newspaper and vehicle pages when available. Which are required for the first coordinated expansion, and which may be explicitly deferred?

**Response:**
I'm with you, we will probably not do a good job adding things by guessing - please combine D01 and D04 into a compact, stable, flexible extension model.


> Pending.

**Decision / date:** Pending.
