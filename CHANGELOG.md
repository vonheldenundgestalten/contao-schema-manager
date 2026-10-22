# Changelog

## 1.4.2 — 2026-10-22

- Preserve literal URI fragment/query characters in manually entered entity IDs. Contao’s default text input encoding could otherwise persist `#` as `&#35;` despite displaying the expected value in the editor.
- Add regression coverage through the actual Contao widget input path and verify raw stored values after backend saves. Existing IDs are not rewritten automatically; resave an affected custom ID after updating. No database schema updates.

## 1.4.1 — 2026-10-22

- Fix custom entity IDs on first save by validating against the current form origin before Contao persists buffered fields.
- Keep manual entity IDs editable after creation, enforce HTTPS/origin and uniqueness checks, reject clearing an established ID, and explain reference/derived-ID implications in English and German field help.
- Add installed-Contao regression coverage for new explicit IDs, duplicate rejection, later ID edits, blank-ID generation and fixed-origin protection. No database changes.

Upgrade: update the package and rebuild the application cache. Existing IDs remain unchanged unless an editor explicitly changes them. Validation: 15 installed regression checks and real backend saves covering first-save custom IDs, later edits, duplicate rejection and replacement of generated IDs.

## 1.4.0 — 2026-10-22

- Add opt-in automatic source ownership: original records persist public identities, while derived reference rows are hidden from entity editing and manual discovery/binding screens. Source graph navigation opens the original record.
- Reject supplementary writes to automatic sources and ignore stored supplementary facts at runtime; exclude these sources from AI edit suggestions. Existing non-automatic adapters retain their editing workflow.
- Add narrowly scoped approval of pending relationships without globally enabling pending vocabulary.
- Correct Schema.org range validation for a node carrying multiple compatible types, such as House and Product sharing one identity.

- Release the versioned extension API v1 for contributed source adapters, entity types and relationships, including typed fields, supplementary editing, previews, explicit migrations and source-change cache invalidation.
- Include managed-Person authorship from beta.2 and site-language graph resolution from beta.3. Existing entity IDs and native news/event identities are retained.

Upgrade from 1.3: apply the additive extension tables/fields through Contao's database update and rebuild the cache. Upgrade from beta.3: update the package and rebuild the cache; the generic changes add no database fields. Project integrations enabling automatic sources must run their own registered source-ID migration and reconcile their reference index. Existing non-automatic adapters keep their workflow. Pending vocabulary remains disabled unless explicitly approved.

Validation: PHP 8.3/8.4 CI, generic extension integration and author/language checks, and OKAL dev verification covering 449 automatic sources, unchanged public IDs, 251 public source pages, 72 public news articles and 31 show-home relationship graphs.

## 1.4.0-beta.3 — 2026-10-22

- Resolve root-scoped source entities and their relationships in the source root’s configured language when building the backend graph, independently of the editor’s UI language.
- Keep graph labels in the editor’s language, preserve source authorization and leave frontend translation availability unchanged.
- Add installed-application regression coverage for different editor/source languages and denied source access.

Upgrade from beta.2: update the package and rebuild the application cache. No database changes. This release includes the previously tested generic graph fix; project-specific source adapters remain in their own bundles.

## 1.4.0-beta.2 — 2026-10-21

- Require a deliberately mapped, published and available Person for automatic backend-user authorship. Preserve explicit per-news Person overrides and mapped-user inheritance.
- Remove unmanaged name-only author output from native pages and news, including default archives. Preserve unrelated schema properties and graph identities.
- Omit unmapped backend users from the relationship map and AI author context; stop automatic Person creation/name matching from account names.
- Add regression coverage for mapped/unmapped users, disabled logins, draft and wrong-type mappings, native page output and graph relationships.

Upgrade from beta.1: update the package and rebuild the cache. No database changes. Existing user-to-Person mappings remain valid; unmapped editorial accounts no longer appear as public authors. Project-specific source adapters remain in their own bundles.

## 1.4.0-beta.1 — 2026-10-21

Prerelease of the generic extension API v1 for integration testing; the final 1.4.0 release is pending.

- Add versioned source, type, relationship, binding, supplementary editor, invalidation and payload-migration contracts with compile-time metadata validation.
- Add source-owned read-only facts, explicit draft bindings, typed global/localized supplementary values, relationship selection and safe backend preview.
- Integrate contributed entities and relationships with the existing JSON-LD and backend graph; omit unavailable/private sources and targets, preserve identities and bound traversal.
- Add source-change cache invalidation, scheduled visibility deadlines, explicit dry-run/apply payload migrations and opt-in existing-entity AI proposals.
- Include synthetic provider examples and developer documentation; all project adapters remain in their own bundles.

Upgrade: apply the two new extension tables, supplementary columns and expanded entityType field through Contao database updates; rebuild the application cache. No automatic publication or destructive migration. Install the explicit beta version; project adapters and OKAL integration remain deferred.

## 1.3.3 — 2026-10-11

- Fix: error pages (`error_401`, `error_403`, `error_404`, ...) no longer turn into HTTP 500. They are not routable, so `ContentUrlGenerator::generate()` threw `RouteNotFoundException` in the JSON-LD listeners. These pages are now skipped, and every page/home URL generation is guarded against routing exceptions, omitting URL-derived properties instead of emitting invalid JSON-LD.

Upgrade from 1.3.2: update the package and rebuild the application cache. No database changes.

## 1.3.2 — 2026-10-08

- SoftwareApplication entries can select required software (e.g. Contao CMS). Published dependencies are linked through `softwareRequirements` alongside localized requirements text and shown in the relationship map. Apply the Contao database update for the new shared selection field.

Upgrade from 1.3.1: update the package, apply the additive `tl_schema_entity.requiredSoftware` field through the Contao database update, and rebuild the application cache. Existing localized requirements text and entity identities are preserved.

Validation: dev backend multi-selection/save/reload, saved JSON-LD preview, frontend JSON-LD, draft exclusion, relationship-map links and rollback-based integration tests.

## 1.3.1 — 2026-10-08

- Entities are now fully output only on their localized home page. Everywhere else (author, publisher, provider, about/mentions, relations) they are lean reference nodes with `@id`, `@type`, `name` and `url` (companies also `logo`) and no relations, so referenced entities are no longer expanded recursively into the graph. Applies to all entity types, not only companies; event venues and offices on a location-overview page keep their contact facts. The backend JSON-LD preview shows the entity's home output with lean references.

## 1.3.0 — 2026-10-05

- Optional Contao Calendar integration enriches core Event nodes with calendar defaults, organiser, attendance/status, speakers and related topics. Core dates, content and recurrence remain authoritative.
- Calendar events appear in the relationship graph and optional AI setup/content workflows.
- Reusable Place venues with address, manual coordinates and map links; existing LocalBusiness entities are available as event venues.
- Standalone and calendar events choose an existing or custom location, with conditional online/hybrid fields and preserved hidden data. Explicit selections avoid mixing venue facts with old custom/calendar addresses.
- Calendar remains optional even when its database tables are retained after uninstalling. Fresh scans/setup skip it; stale calendar proposals cannot alter leftover records.

Upgrade from 1.2.x: update the package, apply the new extension fields through the normal Contao database update, install assets and rebuild the cache. Calendar itself is optional and must match your Contao version. Removing Calendar does not require deleting its tables. Existing manual event addresses and identities remain supported.

Validation: real Calendar uninstall/reinstall on dev with scripts disabled and no database update; unchanged table definitions and calendar records; read-only frontend/backend and optional-bundle integration checks.

## 1.2.1 — 2026-10-05

- Preserve literal fragments in editable website IDs through Contao input handling. Decode already-stored HTML entities when editing and emitting the website node and its references.

Upgrade from 1.2.0: update the package and rebuild the Contao application cache; clear cached frontend pages. No database migration is needed for this fix. Previously encoded website IDs are corrected on output without resaving; saving the root stores the decoded ID.

## 1.2.0 — 2026-10-05

- Optional administrator-only AI helper with a dedicated OpenAI key, source evidence, reviewable proposals, feedback refinement and resumable analysis batches.
- Guided setup: import existing hand-written schema, establish the organization, configure website/news-archive defaults, then discover or improve content.
- Imports preserve established identities, group translations and retain unmapped JSON-LD. Imported and AI-created entities/homes start unpublished. Original HTML schema must be disabled manually after review.
- Multilingual suggestions follow Contao page translations. Discovery reserves existing identities, including drafts, and scans only eligible active public content.
- Person suggestions from public news bylines, author assignments, and reviewed removal of links to deleted managed entities during improvement runs.
- Dependency-aware application queues create required entities/homes first, recover mappings to existing translations, and explain blocked suggestions. Contao versions and stale-value checks protect edits.
- SoftwareApplication entities with localized requirements/features and manual offers; localized organization profile links and commercial-register labels; editable shared website identities.
- The manual editor and relationship map remain usable without an API key. Calendar adapters, smart images/social metadata, pricing-source hooks and the larger entity expansion remain separate feature work.

Upgrade from 1.1.x or the AI development branch: require `^1.2`, run the Contao database update, install bundle assets and rebuild the application cache. Keep the existing database, entity identities and `.env.local` key. No destructive data reset is required. PHP ^8.3 and Contao ^5.7 requirements are unchanged. AI output remains a proposal requiring editorial review; it does not guarantee factual completeness or search rich-result eligibility.

## 1.1.1 — 2026-10-05

- Shared company awards with a migration preserving existing localized values.
- Worldwide service coverage and named commercial-register identifiers, separate from tax IDs.


## 1.1.0 — 2026-10-04

- Interactive backend relationship map connecting managed entities, websites, pages and News articles, with locally bundled Cytoscape.js/fCoSE.
- Language filtering, grouping by type, content-sized cards, relationship labels, search and diagnostics for unconnected entities and articles without service subjects.
- Excludes noindex pages and bare require-item containers while retaining real article detail pages.
- News Main subjects (`about`) and Mentioned entities (`mentions`) selectors enrich JSON-LD and the relationship map.
- Linked knowledge topics (`knowsAbout`) for people and organizations complement localized expertise text.
- Restored the native primary child-items action for translations.
- Illustrated graph documentation and English/German backend labels.

Upgrade from 1.0.0: run the Contao database update to add `tl_news.schemaAbout`, `tl_news.schemaMentions` (when News is installed), and `tl_schema_entity.knowledgeTopics`. Install bundle assets and rebuild the cache. Existing identities, content and selections are preserved; no data removal is required. PHP ^8.3 and Contao ^5.7 requirements are unchanged.

## 1.0.0 — 2026-10-03

First semantic-versioned release. Requires Contao ^5.7 and PHP ^8.3; package versions no longer mirror Contao versions.

- Organization, LocalBusiness, Person, Service, manual Product and standalone Event entities with stable identities and localized homes.
- Company/contact facts, employee counts, expertise, awards and shared contact points.
- LocalBusiness addresses, region/PO box/fax, coordinates, map links, weekly opening hours and price range.
- Company-to-office and subsidiary links, network memberships, external organization references and reusable company/office/service catalogues.
- Public person contacts, workplaces and localized professional qualifications.
- Event venue addresses and offline/online/mixed attendance.
- Compact supporting organizations, with an explicit NAP-preserving location overview mode.
- Optional News article enrichment and archive-controlled JobPosting with defaults, overrides and expiry-aware output.
- Controlled adoption of established IDs, both at creation and via an explicit dry-run migration command.
- Website/page enrichment, manual offers, English/German editors, saved graph previews and illustrated documentation.

### Version-number correction

The earlier **5.7.0** GitHub release and tag were withdrawn at the maintainer’s request; commits remain in history. Existing consumers should change the package constraint from ^5.7 to ^1.0, run the additive database update and rebuild the cache. See [migration details](docs/versioning.md).

### Deliberately deferred

Smart image/social metadata, source-record adapters and product/pricing hooks remain on their separate unchanged feature branches. Products/offers remain manual. No AccountingService subtype, shop variants/inventory, Calendar adapter or FAQ adapter is included. Existing Contao images and project social metadata remain in place.

### Earlier pilot data

The pilot’s 24 source-linked offers were previously copied to manual fields without changing their identities. Retired experimental database columns were retained for reversibility. Other early pilot installations must preserve any needed source-derived values and their existing social metadata provider before removing experimental code. Do not blindly accept database DROP suggestions.
