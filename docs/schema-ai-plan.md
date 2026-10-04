# Schema AI helper — implementation plan

Status: planning only, 2026-10-04. Branch: `codex/feature-schema-ai`, based on 1.1.0. No implementation, API calls, credentials, migrations or deployment accompany this document. No release version is assigned.

## Recommendation

Build an optional, evidence-based assistant inside the existing Schema Manager. It proposes ordinary editable records and field changes; the existing schema mapping remains responsible for producing JSON-LD. AI never becomes necessary to render the website.

Offer two actions:

1. **Analyze and prefill**: discover entities and prepare unpublished drafts, either for a whole new site or just newly discovered subjects on an established site.
2. **Check schema for improvements**: propose missing facts, localized homes and relationships for existing records, with a checkbox review list showing evidence and before/after values.

Use the same proposal engine for both. Creating new records is not entirely independent: a discovered service may refer to an existing company, and duplicate people across languages must resolve to one identity. Creation avoids overwriting live data, but matching and relationship handling are still essential.

Use a review list, not a chat interface, for the first version. A later “Why this suggestion?” or “Refine this description” conversation may produce revised proposals; it must never bypass review or write directly to records.

## Editorial experience

### Analyze and prefill

Entry points: the Schema Manager overview and the new-entity editor. The overview offers **Whole selected site**, **New subjects only**, and **Selected pages**; an entity editor analyzes its selected home page and relevant supporting pages.

1. Choose a Contao root/site, languages and scope. Show eligible page counts, exclusions, scan limits and the estimated API cost before starting. Nothing runs automatically on ordinary page edits.
2. Inventory local content and the existing schema records. On first use, initialize the inventory even if existing entities were created manually.
3. Present candidate cards grouped as Company/offices, People, Services, Products and Events. Show name, proposed type, candidate homes per language, possible existing matches, source excerpts and unresolved fields.
4. Each candidate can be edited, excluded or matched to an existing entity. Matching an existing entity sends proposed additions to the improvement queue rather than creating a duplicate.
5. **Create selected drafts** creates ordinary unpublished entities and translations. Resolve selected interdependent candidates as a batch. Publication stays a separate normal editorial action.
6. The normal entity editor and saved-output preview remain the place to finish and publish. Subsequent website rendering makes no AI calls.

For an unsaved entity form, prefill the form/proposal buffer first, without creating a published record. Respect any values already entered by the editor; show conflicts instead of replacing them.

“Prefill all fields” means attempt all supported fields for which the sources provide evidence, not invent values to make every field nonempty. Missing prices, legal facts, qualifications or dates remain blank and visible as unresolved items.

### Check schema for improvements

Run for one entity, selected records, one site or new/changed content since the previous run. Group suggestions under the affected entity and language, then by **Missing facts**, **Connections**, **Localized homes**, **Conflicts** and **Possible duplicates**.

Each row shows:

| Column | Example |
| --- | --- |
| Entity and field | Markus Milkereit → Linked knowledge topics |
| Current → proposed | Existing topics → add SEO & GEO |
| Meaning | Knows about this subject; does not claim service provision |
| Evidence | A quotation from the person's profile, with page title, URL and language |
| Evidence quality | Explicit statement / editorial inference / conflicting sources |
| Controls | Accept checkbox, edit value, reject, inspect source |

Use expandable entity groups and **Select all eligible suggestions in this group** / **Select all eligible visible suggestions**. Display the selected count and exact filter scope. Nothing starts checked. Conflicts, identity/legal-name changes, ambiguous matches and overwrites are excluded from bulk selection; require individual review. A dependency is shown before selection, never silently selected or created. Rejected suggestions stay rejected for the same evidence/version and can be restored explicitly.

**Apply selected changes** shows a concise final summary. Changes to published entities can affect live JSON-LD immediately; say so in that summary. Store the approving editor and versions. Apply valid dependency groups atomically, with a clear result for each group; do not silently drop an unavailable target. Recheck permissions and current field values at apply time. If the editor changed a field after analysis, mark its proposal stale and request a fresh comparison.

Do not treat an empty field as permission to publish an inferred claim. Do not equate an author writing about a service with proof that they provide it. Prefer an evidence-backed `knowsAbout` proposal where appropriate.

### Why the checkbox list wins

| Review list | Chat |
| --- | --- |
| Makes every field and relationship change visible | Useful for explanations and ambiguous editorial context |
| Easy to compare, select, reject and audit many changes | Harder to verify that a long answer includes every proposed mutation |
| Predictable cost after analysis | Follow-up conversations can consume additional budget |
| Works naturally with Contao records and versioning | Requires additional conversation state and interaction design |

First version: list plus editable proposals and source excerpts. Later: optional contextual chat restricted to revising proposals. No chat-driven publishing or arbitrary database tools.

## Sources: close to Contao first

Build a permission-filtered inventory from the Contao page tree, content elements, News archives/items, existing managed entities and translations. Reuse the concepts in `ContentMapSource`, but do not assume graph display data is sufficient for extraction: it contains relationships and routing metadata, not the full content evidence.

Use public rendered HTML for the facts a visitor actually sees, with source adapters supplying reliable record IDs, language, routing and structured News fields. Prioritize home, legal/company, team/profile, service/product and contact pages, then news and secondary pages. Existing JSON-LD is a useful hint and matching source, not unquestionable truth; compare it with visible content and editor-owned records. Deduplicate repeated navigation/footer blocks while retaining relevant company/contact evidence from representative pages.

Do not use free-ranging model browsing. The server collects an allowlisted site corpus and sends bounded excerpts with source identifiers. Default exclusions: protected/unpublished content, account/backend pages, noindex pages, search results and bare require-item containers. Inventory real detail URLs through Contao routing so News content remains eligible. Excluding a noindex page from discovery does not mean it is private: an editor may explicitly include a public legal/contact page as evidence without creating a WebPage node for it.

Custom content elements may expose text via rendering or an optional evidence adapter. If the text cannot be extracted reliably, report incomplete coverage instead of pretending the site was fully scanned. Defer browser/JavaScript rendering, PDF ingestion, off-site research and arbitrary external URLs until real projects demonstrate the need.

For a basic-auth staging site, fetch only through an explicitly configured trusted origin and keep staging credentials on the server. Map staging URLs to the configured public site/home routes. Never derive permanent entity identity origins from the staging hostname.

## Detecting what is actually new

“New” is an unmatched entity candidate, not simply a page created after a timestamp. One new page may describe an existing service; an edited old page may introduce a new person.

Persist a source inventory keyed by site, source record and locale, plus canonical URL, normalized content hash and relevant extraction version. Track entity-to-source mappings and a candidate/suggestion decision ledger. Use stable source IDs across URL moves. On repeat runs, analyze new/changed source chunks plus the small related entity context; unchanged content can reuse prior extraction.

Match in this order: explicit source binding or trusted existing stable `@id`; known localized homes and canonical URLs; then structured attributes such as type, legal identity and public contact facts. Names are only a hint. A fuzzy match or two similarly named offices requires review. A translation is normally a new home/translation for the same entity, not a second person/company.

Existing schema output can create a feedback loop: never treat the assistant's own previously generated JSON-LD as fresh independent evidence. Keep visible-source hashes and existing-schema snapshots distinct. Remember manually removed/rejected links so subsequent scans do not keep adding them back. A source deletion creates a review notice, never automatic deletion of an entity or relationship.

In **New subjects only**, existing entity edits are queued separately and are not applied through creation. Cache keys include site, language, model, prompt/extraction version and normalized evidence. A changed model/prompt allows an explicit rescan; it does not turn all content into new entities.

## Mapping to the current extension

| Existing destination | Proposed assistance |
| --- | --- |
| `tl_schema_entity` | Shared identity facts, supported type, company/person/service relationships and `knowledgeTopics` |
| `tl_schema_translation` | Home page and language, description, service wording, expertise and other existing localized fields |
| `tl_schema_contact` | Explicit public organizational contact points |
| `tl_page` schema settings | Publisher, supported page type and linked entities where supported by visible content |
| `tl_news` | `schemaAbout`, `schemaMentions`, existing managed-author association |
| `tl_news_archive` | Respect the archive's existing Article/BlogPosting/NewsArticle/JobPosting choice; no per-entry type overrides |

Use the current supported types only. Preserve existing entity IDs and identity origins; identity generation remains deterministic application logic. The model may propose an existing ID match but must not mint final IDs, merge records or change identity fields. Adoption of an established external ID remains the explicit existing workflow.

The company legal name is a shared fact, not a translation. Translated descriptions/home pages remain separate from shared facts. Never infer a legal name from a logo or marketing title; flag conflicts between the imprint and existing company facts. Link translations through Contao's available language/page relationships, not guessed paths.

`knowsAbout`, provider and responsibility are different claims. This version can propose the knowledge links already supported in 1.1.0; it must not invent a Person-provider selector or new arbitrary properties. Products and offers remain manual records: AI may propose values explicitly stated on a selected page, but no source binding or automatic price synchronization is introduced. Smart images/social metadata and the parked product-hook work remain outside this feature.

## Architecture and proposed storage

Separate collection, inference, review and writing:

1. **SiteInventory / EvidenceCollector**: permission-checked source records, bounded public fetches and normalized excerpts.
2. **EntityMatcher**: deterministic matches, ambiguous candidate groups and stable source bindings.
3. **AnalysisProvider**: one initial API implementation behind a small interface; receives a bounded task and returns a constrained proposal document.
4. **ProposalValidator**: known field/type/relation allowlists, expected value shapes, length limits, source-ID checks and dependency validation. Structured output constrains shape, not factual correctness.
5. **Review module**: filtering, evidence, editable before/after values and explicit approval.
6. **ProposalApplier**: shared domain validation, authorization, versions, transactions and cache invalidation; no raw model SQL or unrestricted JSON-LD injection.

Proposed tables (design, not migrations): `tl_schema_ai_run`, `tl_schema_ai_source`, `tl_schema_ai_proposal`, and a source-binding/decision ledger. A proposal stores target type/record or temporary candidate key, allowed field/property, locale, old-value fingerprint, proposed value, evidence references, evidence quality, dependencies, decision, actor and application result. Runs store scope, provider/model, prompt version, reported token usage, estimated cost and status. Keep source excerpts separate with a retention policy; avoid retaining entire pages unnecessarily.

Apply through a dedicated writer using the existing validators/mappers, not direct AI-controlled writes. Calling a model save alone is not enough if it skips DCA callbacks: explicitly reuse/extract shared validation and test versioning, cache tags and save semantics. EntityGraph remains the only output path; the graph gains normal entities/edges after approval. A later preview may overlay proposed edges visually, but MVP must work without changing the visualization library.

Run scanning/inference in resumable background jobs compatible with the installed Contao/Symfony setup; verify the supported job mechanism before implementation. Do not hold a browser request open for a full-site scan or require a permanent worker without documenting hosting prerequisites. Cancellation stops future batches; already issued requests may still incur cost. Retrying a job must not duplicate approved records or applications.

## API setup and usage visibility

Keep AI optional and disabled until an administrator configures it. Recommend a dedicated provider project and service credential per client/site (separate production and staging if useful), not one agency-wide key. Show provider/model, credential status, connection test, allowed roots and read-only usage information in settings. Store the secret server-side through environment/Symfony secrets or an encrypted secret store with its encryption key outside the database; never send it to the browser, logs, exports or prompts. Do not require clients to give the key to the extension vendor.

Use OpenAI **GPT-6.1 Sol** (`gpt-6.1-sol`) as the fixed model, with no editor/admin model selector. Start with standard processing and medium reasoning; evaluate the extraction fixtures before implementation is considered ready. Keep the model identifier centralized in code so a future supported-model change is a normal extension update, not another client configuration task. Retain the small provider interface for testability, not a provider-selection UI. Record the actual model and pricing snapshot for each run. Use structured output and handle refusals, malformed/truncated responses and retries explicitly.

Do not implement financial budget enforcement: no monthly/run spending limits, budget reservations, budget ledger or spending-based admission control. Keep the dedicated client key, a rough pre-run estimate and reported token usage/estimated cost afterwards. Technical bounds still prevent runaway crawling or retry loops: selected site scope, bounded requests, timeouts, finite retries and cancellation. These are operational controls, not configurable monetary budgets. No paid connection test or rescan happens silently.

### Rough pilot cost estimate

As of 2026-10-04, the official GPT-6.1 Sol model page lists Standard pricing of **$2 per million input tokens and $10 per million output tokens** for requests at or below 272K input tokens. Higher-context requests and other processing options have different rates. Use small evidence batches; no Fast mode or paid model browsing is assumed.

For recreating the pilot's roughly twenty business entities, their German/English descriptions and home assignments, plus relationship proposals, assume **60K–150K total input tokens** and **20K–60K total billed output tokens, including reasoning**, across collection/extraction/review passes. This gives approximately **$0.32–$0.90** in base text-model charges. A practical rough allowance of **$1–$3 for the whole initial fill** leaves room for repeated context, corrections and retries; it is an estimate, not a hard upper bound.

This is a hypothetical purpose-built API workflow, not measured usage from the development conversation. It excludes building/debugging the extension, browser automation, hosting, tax and any optional regional surcharge. Incremental additions should usually cost less, depending on how much supporting context is reprocessed. Measure actual usage in the prototype before displaying a tighter estimate to clients.

## Evidence, permissions and data handling

The model reads untrusted website text. Instructions embedded in pages must be treated as quoted content, never as commands. It receives no key, cookies, backend access, database tools or ability to select arbitrary fetch targets. Validate suggested URLs/source IDs against the collected inventory. Escape all model/excerpt text in the backend.

Only authorized users may configure credentials, start runs, see source evidence or apply changes. Recheck record/field/site permissions at review and application, including when a privileged user's run is later viewed by another editor. Source fetches must enforce origin allowlists, redirect checks, private-address restrictions, time/size limits and no credential forwarding to unrelated hosts; explicitly configured staging access is a narrow server-side exception, not arbitrary internal fetching.

Show what public site content and schema context will be sent to which provider. Omit forms, submissions, account data and unrelated personal data. Verify provider retention/region settings for deployment requirements rather than promising a universal policy. Allow deleting run evidence/history under a documented retention policy while preserving the minimal application audit needed by the site owner.

Existing data is not silently replaced. No automatic publication, entity deletion/merging, identity changes or invented claims. A provider outage affects analysis only; normal editing and frontend rendering continue to work.

## Implementation stages and acceptance criteria

1. **Read-only prototype**: bounded collection from selected pages, deterministic inventory/matching, evidence-linked output and measured usage. Evaluate on varied real client sites, including multiple offices/languages and custom Contao elements.
2. **New entity drafts**: review cards, explicit candidate matching, localized homes, dependency-aware unpublished creation, permissions and versions.
3. **Improvement review**: checkbox groups, before/after comparisons, rejection memory, stale-change detection and transactional application of facts/relationships.
4. **Operational readiness**: background-job recovery, credential controls, usage reporting, retention, documentation and provider failure tests.
5. **Review refinement (implemented on the AI feature branch)**: contextual feedback with a plain-language reply and a separate revised review. Originals remain available. Proposed-edge graph preview remains future work. No general autonomous website-management agent.

Release gates:

- First scan discovers supported evidence-backed entities without inventing missing mandatory facts.
- An unchanged rerun creates zero duplicate drafts and resurfaces no identical rejected suggestions.
- Newly added/changed pages distinguish existing subjects from new ones across DE/EN; URL moves keep identities.
- Company legal names remain shared; localized fields and homes do not leak into the wrong language.
- Ambiguous matches, unsupported fields and source contradictions require review.
- Approval preserves unrelated values, rejects stale edits and cannot write inaccessible records.
- Transaction failure, repeated clicks and job retry create no partial dependency groups or duplicate writes.
- Real rendered JSON-LD, version history, cache invalidation and graph links match the approved changes.
- Tests cover page prompt injection, unsafe URLs, secret redaction, unpublished sources and permission loss after scanning.
- Retries are finite, cancellation stops future batches, and reported usage is recorded without claiming monetary enforcement.
- AI disabled or unavailable has no effect on normal Schema Manager behavior.

## Decisions to revisit before coding

Deployment region; package boundary (optional module in this repository first versus companion package if dependencies warrant it); run/source retention periods; technical batch sizes and retry defaults; supported background execution on client hosting. These are implementation choices, not reasons to block this plan. Start with explicit public-site scanning and the review list; validate assumptions against the real projects before broadening scope.

## References checked for this plan

- [GPT-6.1 Sol model and pricing](https://developers.openai.com/api/docs/models/gpt-6.1-sol): fixed model choice and the Standard token rates used in the rough pilot estimate.

- [OpenAI structured outputs](https://developers.openai.com/api/docs/guides/structured-outputs): constrained response shape; still requires application validation and evidence review.
- [OpenAI production practices](https://developers.openai.com/api/docs/guides/production-best-practices): server-side credential management and production usage planning.

Provider behavior, GPT-6.1 Sol availability and pricing must be rechecked at implementation time; do not silently substitute a different model. The workflow and storage decisions above are proposed extension design, not existing functionality.


## Pilot feedback and editorial quality (2026-10-04)

The first 16-source discovery run proposed too many entities from headings and brief mentions. Prefer the existing broad offering unless a separate service has clear editorial meaning. A case-study mention is not sufficient proof of a currently marketed product or service. Each creation should explain its benefit, supported relationships and remaining uncertainty; entity count is not a success metric.

Review groups start collapsed with a type/name summary. Select all suggestions selects every pending proposal, including replacements; applying still requires the explicit apply button. Dependent draft/home creation steps are selected with their fields. No analysis applies changes automatically.

The feedback conversation sends saved public sources, schema context, prior pending proposals and the editor's feedback to the fixed provider. It returns an explanation and a complete replacement proposal set in a separate analysis. Sources are not fetched again during refinement. The original review is preserved; apply only the preferred version. Unsaved text edits and selections are not part of feedback. Refinement is available after completion and before any proposals have been applied; otherwise start a fresh analysis. Provider errors pause the new review for manual retry. Usage belongs to each review separately. Feedback history is limited to the latest ten messages and normal context-size limits still apply.

Discovery can propose page/news links to its new entities, subject to the same field and evidence validation. Other changes to existing records remain the improvement workflow's responsibility.

Remaining modelling gaps are explained rather than filled with substitute relations. In particular, Product.organization currently maps to an offer seller, not software authorship or compatibility. SoftwareApplication/softwareRequirements is not yet exposed by the AI allowlist. A platform requirement can ultimately be text or a URL; a managed platform entity is optional. An external client may be the subject of a case study through about, but this is not an explicit customer relationship. Do not misuse parentOrganization, memberOf or customer to manufacture that relation.

Validation: mocked provider integration covers separate refinement runs, preserved originals, explanation/usage, and refusal to refine applied reviews. Browser checks intercept refinement POSTs and cover collapsed summaries, full selection, dependency selection and review navigation. No additional paid API request was made for these checks; real refinement quality still needs pilot testing.


## Automatic language entries (2026-10-04)

New runs include the selected root plus published roots with another language and the same configured domain by default. The editor can disable this for a single-language run. Cross-domain language sites are not automatically grouped. A multilingual run deliberately scans all included sources, even if the changed-content checkbox is set, so it retains the evidence needed to match entities and translations.

Page counterparts come from Contao's `languageMain` links where available. Discovery batches keep page families together. After source discovery, a resumable localization pass fills missing page assignments and localized fields for new candidates and existing entities with linked homes. It preserves one shared entity/identity, never translates legal identity fields and never overwrites existing translations. Draft identity metadata is included only to avoid duplicate candidates, not as source evidence. Localized proposals may cite the exact original-language source while translating its supported content into the destination page language.

Each locale is labelled in the review. Creation and publication remain explicit editorial actions; translated records start unpublished. Missing/ambiguous page links or insufficient evidence produce review notices rather than invented homes or content. Old saved analyses are unchanged; prepare a new analysis to use the multilingual scope.

Validation includes two linked language roots, one shared entity with two unpublished homes, protected shared names, preserved existing translations, missing-counterpart notices, and a complete mocked localization batch that saves proposals without applying them. No paid provider call is part of these tests.
