# Proposed architecture and editorial workflow

[Planning index](../entity-expansion-plan.md) · Status: proposals, not approved implementation

## 1. Keep four different responsibilities

| Layer | Owns | Does not own |
| --- | --- | --- |
| Contao source | Headline, body, dates, author, publication, imported member/product data and routing | A second conflicting semantic identity |
| Managed entity | Permanent ID, shared facts, semantic type/profile, approved relationships | Duplicate article bodies or imported personnel databases |
| Localized presentation | Translated labels/descriptions, source-derived text policy, home target and site placement | New identities simply because the language or page changes |
| Supporting records | Recognition results, certification instances, contacts, measured facts, relation roles/evidence | Automatically exported made-up Schema.org types |

Most ordinary sites continue to use exactly the familiar Organization, Person, Service and News workflow. Additional families are enabled when needed; disabling a family hides new creation options, never existing records or output without an explicit action.

## 2. Profiles and shared definitions

Recommend a curated definition registry with reusable field groups and explicit output mappers. A profile supplies its label, Schema.org type(s), capabilities, global/localized fields, allowed relations, validation, import mapping, AI permissions and graph presentation. A project can register a profile/adapter in code. Do not generate thousands of raw schema fields or let ordinary editors invent arbitrary properties.

Examples: House model = ProductModel + measurements; Flooring system = Product + constituent materials + certifications; Public showroom = Place + visiting details; Medical department = MedicalOrganization + specialty + organizational relations. These labels help editors without creating new public vocabulary.

Store the actual semantic relationships explicitly. Split the meaning of the current organization field into appropriate relations when necessary: provider, publisher, manufacturer, seller, employer, parent, organizer are not interchangeable. Preserve the current mapping for old records unless editors opt into a reviewed change.

Prefer existing normalized Contao fields and small supporting tables where repeatability justifies them, rather than an untyped JSON/EAV database for everything. Do not decide exact tables or API signatures until the workflows below are approved. The registry coordinates validation; it does not replace the mapper or source adapter with magic.

## 3. Source binding and homes are foundational

A source binding records adapter + record identity + mapped managed entity. A home target resolves a public URL from a page **or a source record and its reader**. A record ID is not a page ID. An alias change must not mint a new semantic identity.

Example: `tl_member 123 → Person → profile URL through the site's member directory adapter`; `tl_news 456 → award report Article`; `tl_calendar_events 789 → Event`. Do not bind a Person home to the bare require-item reader page or hand-concatenate aliases globally. Adapters resolve URLs through the appropriate Contao/project route and expose active/public eligibility.

Static pages remain supported. A stable section anchor can be an optional placement for team members on a shared team page; it must exist in public HTML and cannot be an invented fragment masquerading as a real home. A useful entity may have no individual profile page; show this honestly rather than forcing a fake page.

Minimum adapter responsibilities proposed: stable source key, record label/type, eligible public state, public field allowlist, home resolution, language links, cache dependencies and read permissions. Start with native News/Calendar and opted-in member directories. Project-specific product/system sources remain project adapters; this does not reopen automatic pricing work.

### Source authority

For each mapped field show whether it comes from the source, a manual semantic field, or preserved import. Source-derived values are read-only in the schema view with an edit-original link. Semantic overrides, if enabled at all, must be explicit and reversible. They must not silently rewrite the imported member record on the next CRM synchronization.

AI proposes semantic additions and missing bindings. It must not overwrite source-owned dates/names or infer employment, certification, medical treatment, ownership or unpublished personal information from a loose mention. Existing reviewed entities remain unchanged unless a concrete improvement is selected.

## 4. Public people without publishing the member database

Use explicit record selection or an administrator-selected public-directory configuration. A group may narrow eligibility but is not proof of consent/publication by itself. Only public output fields are mapped: name, public biography/job title, approved public contact, portrait and relationships. No username, login email, private address, birth date or form submissions.

Keep membership/login state separate from public directory publication. A contact without login access can be public; an enabled customer account can be private. Removal from the directory stops source-backed public emission and invalidates dependent output. Existing historical bylines may still reference a minimal public author when justified; that requires an explicit retention policy, not accidental draft leakage.

The same Person can have both tl_user and tl_member bindings after explicit identity confirmation. Duplicate names do not justify automatic merges. Responsibility for a service/category is not authorship of every page mentioning it.

## 5. Localization, identity and multi-site use

Global by default: IDs, legal company name, registration values, source keys, numeric facts/units, physical coordinates, recipient/issuer links, dates, model codes and organizational structure. Localized: descriptions, service/product titles, public role labels, category labels, register labels, official-profile links and explanation text. Preserve the existing global company award facts; translate their explanation/formatting, not the winner or edition.

Contacts can vary by territory rather than language; model a scoped contact point instead of changing the company's identity. Sales offers can vary by market/currency; do not automatically translate their price. Different language alone must not create a new physical site.

Recommended routing model: an owning site and default home per language, plus explicit placements in approved site/root contexts. If the same-language copy genuinely differs between sites, allow an explicit presentation override rather than choosing the first translation row. Keep translated content and page placement conceptually separate, even if migration initially preserves their existing storage.

Cross-domain references are legitimate. Diakonie, femininum and STARS may share entities in one Contao installation without sharing publisher, legal owner or homepage. AI scans must use an explicit domain/root scope; never expand to every linked partner site automatically. Existing imported language-specific IDs require a migration decision—do not collapse them into a newly generated ID.

## 6. Reusable location workflow

An event offers three choices: **existing place**, **one-off address**, **online**; mixed attendance combines a physical place with a public virtual URL. Picking a place fills its current approved address/contact/coordinates without copying those values into every event. Allow a session-specific room or entrance without altering the place globally. Expose where the values come from.

Location type and output roles differ: an office LocalBusiness can itself be the selected Place; it does not need a duplicate generic Place record. A building may have a separate business occupant when that distinction matters. A House can be an event venue without becoming a LocalBusiness. Do not infer 24/7 opening from “by appointment”. Coordinates remain manually entered, per the user's instruction; no AI geocoding.

Define what happens when an address changes after an event. Recommend current shared location for future occurrences, with an explicit historical snapshot only where preserving the old venue address is required. No automatic rewriting of past evidence.

## 7. Recognition, topics and content

A recognition result is an editorial object: recipient(s), scheme name, edition/year, category/result, evidence and linked reporting. The same article may report multiple results. Certifications are separate subject-scoped instances, not awards or reusable logos with implied universal validity. See the vocabulary file before defining their output.

Keep cases and reports in News. Archive type controls schema; optional case fields can add a client, related services, products and technologies. Existing about/mentions already handle much of this. No fake `client` property or automatic client Organization for every quoted name. Anonymous cases stay anonymous.

Reuse existing category sources where available. Categories organize products/services and can be topics that experts know about; they are not automatically services. A category has a public CollectionPage and an ItemList of visible entries. Only create a reusable term entity when it is actually referenced across items. Hierarchy can stay internal and in page breadcrumbs; Schema.org does not supply a general `broader` relation for these terms.

## 8. Emission and graph behavior

Continue using Contao's JsonLdManager. Build one identity-aware graph, not one script per editor form. For every relation establish its direction, allowed source/target types, compact representation and visibility rules. Include the correct @type/name/url with supporting references when useful; id-only cross-page references do not ensure a consumer will fetch the remote definition.

Use role-specific compact nodes: an event venue needs usable location facts on the event page; an author needs identity/name/profile; a publisher normally needs a compact company. Full local-home output remains available. Avoid recursive emission of the whole organization or category tree on every page. Define cycle handling, bounded traversal, query budgets, deterministic ordering and cache invalidation across shared records.

The backend graph must distinguish **published JSON-LD relationships** from **editorial-only relationships**, such as an award result's internal links or a sales responsibility with no exact standard mapping. Offer a legend/filter and meaningful labels, not falsely labelled Schema.org arrows. Internal records need not all become public graph nodes.

## 9. Migration and optional AI

Retain current IDs and free-text awards, existing manual offers, archive settings and imported JSON. A new native field must not unexpectedly override a preserved import value. A migration/import review should show field ownership and conflicts, including single vs multiple @types and arrays. Recognize externally maintained schema by identity and reserve it to prevent duplicate AI creation.

This does **not** revive the removed comparison feature or automatically disable old HTML. Agorum needs a scoped ownership decision: leave a block maintained externally or explicitly import it and retire the old emitter manually. Unsupported blocks such as existing FAQ contributions must survive untouched.

AI must use the same enabled profiles/relationships as manual editing and import. Draft proposals include why an entity is useful, evidence, intended source/home and necessary prerequisites. Reuse active shared venues/people first; do not create every customer, partner or certification body from incidental mentions. Multilingual suggestions cannot invent untranslated facts or silently alter existing values.
