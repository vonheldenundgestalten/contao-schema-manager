# Round 2: broad coverage with a small, stable integration model

[Planning index](../entity-expansion-plan.md) · [Original user feedback](feedback/00-shared-decisions.md) · [Next feedback](feedback/01-round-2.md)

**Status: planning in progress. Nothing here authorizes development.** This revises the narrow catalogue proposed in round 1. The user's answers establish direction, not a completed specification. Their original wording remains in the shared-decisions file.

## What changed after your answers

| Decision | Direction received | Consequence for the plan |
| --- | --- | --- |
| D01 + D09 | Much broader coverage; roughly 100 complex-offering examples; combine with developer extensibility | [100-use-case catalogue](use-cases-100.md); stop treating the initial clients as the boundary of the type catalogue |
| D02 | Accept proper company relationships; LocalBusiness for offices as a starting point | Keep organizational and physical relationships distinct; exact medical/group diagrams remain open |
| D03 | Separate manageable awards and certificates, linked to reporting and recipients | First-class backend records, with different public mappings; see below |
| D04 | Source fields authoritative; no duplicate editing; custom adapters may be appropriate | Source adapter and entity definition are independent developer extension points |
| D05 | Explanation was unclear | Concrete VIACOR example below; no agreement inferred |
| D06 | Keep shared facts centrally; localized fields and language-specific URL | Preserve this model; same-language cross-site placement remains a separate unresolved edge |
| D07 | Events with optional recordings; no standalone recording management or courses | Narrow media scope; an attached recording can still emit VideoObject |
| D08 | Existing import sufficient; defer complex ownership-conflict work | No new comparison UI or generalized migration system in this expansion |

## Broad catalogue, predictable editor

The [100 examples](use-cases-100.md) are coverage probes, not a commitment to 100 arbitrary types. There are three distinct promises a release could make:

1. **Recognize a vocabulary type** and preserve its identity.
2. **Provide a useful editor** with supported fields, relationships and output.
3. **Connect an existing source** with its public state, language and detail URL.

A long type dropdown only achieves the first. The goal should be broad useful coverage through the second, with a stable route to the third. A searchable catalogue can use familiar names such as Industrial equipment, House model or Managed hosting, explaining the underlying type. These are labels/presets, not new vocabulary types. The exact labels and number of real types remain a review task.

Proposed reusable capabilities: identity; localized presentation; organizational roles; public contacts; places; measured/specification facts; product/manufacturer/material relationships; services/provider/coverage; software facts; manually maintained offers; event occurrence; recognition and certification; content subjects. Each definition chooses applicable capabilities and only valid public properties. A customer need not understand an ontology or configure mappings.

Do not make every specialist label a permanent stored profile. Prefer a stable semantic definition plus optional editor preset; changing an editor label must not silently retype the entity or change its identity. Type changes with populated incompatible fields need an explicit reviewed rule.

## Two independent developer extension points

These are responsibilities, not approved class names, interfaces or code.

| Extension point | Responsibility | Example |
| --- | --- | --- |
| Entity definition | Type(s), useful fields, global/localized split, allowed relations, validation and output mapping | A specialist building profile using ProductModel plus measured facts |
| Source adapter | Stable record identity, public eligibility, language resolution, public URL, source-owned facts, relations and invalidation | OKAL tl_houses; VIACOR products/systems; a filtered member directory |

The core supplies shared identity, storage of **additional** facts, editor integration, publishing rules, graph composition and extension registration. An adapter supplies business-specific knowledge. Clients without custom sources can still create broad supported entities manually. Native News and Calendar integrations use the same conceptual contract; member selection can have a reusable adapter with project-defined eligibility/routing, rather than a bespoke people subsystem.

Do not put OKAL or VIACOR table names or custom publication rules into the core. Conversely, do not make every developer implement translations, IDs, JSON-LD assembly and permission rules from scratch. That common work belongs in the extension.

### Source authority must be unambiguous

For each entity definition and bound source, show **From source** fields as read-only values with an edit-original link. Allow editing only facts the source does not own. An owned but empty source field remains owned: fill it at the source, not in a second schema field. This avoids hidden overrides and later import conflicts. No per-field override feature is proposed.

Each semantic entity has one authoritative binding by default. Additional appearances can reference it. If a person is present in tl_user and tl_member, that does not authorize merging names or mixing competing contact fields. Select the authoritative public profile and use an explicit reference from the author record; any genuine need for multiple contributing authorities requires a later concrete design.

The adapter may derive public facts on read, with caching. The entity record stores identity and supplementary facts, not a second copy of every source value. Export needs a consistent source snapshot/version so publication or a changed record cannot produce a mixture of old and new facts. The exact cache strategy is not yet selected.

### Stable source and translation identity

An adapter needs a stable business/source key, not only a numeric row ID. For example, an upstream house identifier is preferable when imports recreate rows. Source key, page ID, entity ID and language-row ID are separate concepts.

The adapter explicitly groups translated records; names or matching slugs are not enough. It returns global facts separately from localized values and the actual localized detail URL. Contao/project routing resolves the URL; Schema Manager does not guess aliases. A missing translation must follow a defined fallback/publication policy, not silently create an EN page from DE data.

For tl_houses, one adapter can classify model records as ProductModel and show-house records as House from an explicit source discriminator. It must not infer that from the title. If one row actually represents two distinct things, the adapter must expose two explicit source subjects or we must resolve the source design first; one row is not automatic proof of one entity.

For VIACOR, adapters bind products, systems and translated categories using their real identifiers. The system points to constituent entities by stable source keys; those targets may not yet have a managed binding. Decide whether a reviewed batch creates the required bindings or leaves an actionable unresolved link. Do not silently publish an entire source database through dependencies.

### Lifecycle and extension removal

Public source eligibility is a gate independent from schema publication. A draft schema supplement cannot become public merely because the source is live; a hidden source cannot become public because a schema record is active. Existing source-generated Article/Event output remains separate from optional managed enrichment so a draft supplement does not remove the core article.

If an adapter disappears, keep the binding and ID for recovery, but do not export stale private/source-owned facts as if verified. Display a clear unavailable-source state. Deleting a source, recreating it with a reused numeric ID, changing its language, or changing its reader all need defined behavior before implementation.

## Awards: a real backend item without a fictional public type

Yes: the editor should be able to create an **Award result** independently, choose recipients and link one or more News articles. No extraction from prose is necessary. The record represents a particular result, not the whole award programme across every year.

Proposed minimum fields:

| Field | Example / rule |
| --- | --- |
| Award name | Official scheme name; default wording plus optional localized display label |
| Edition/year | 2024; independent from publication date |
| Category | Premiumhäuser; localized label if useful |
| Distinction | Winner / third place / finalist, accurately maintained |
| Recipient(s) | Any managed entity may be selected internally; same result can have joint recipients |
| Reporting | Existing news record(s) or page, resolved through source homes |
| Evidence | Official result URL; awarding organization optional when known |
| Optional presentation | Badge/image and short localized explanation; no required standalone page |

Different placements mean different result records. Multiple recipients in one record means a genuinely shared result, not all recipients in an article. Start without requiring a separate Award Programme record; repeated scheme names can become reusable later if evidence warrants it.

Public output has two parts: the eligible recipient gets a readable award string, and the reporting Article identifies its subjects. The backend keeps richer structured detail for filtering and overview content. For example, a ProductModel for Hampton could carry `award: "Hausbau Design Award 2024 — Premiumhäuser — Winner"` and a subjectOf reference to the existing report. The report can be about both Hampton and ZweiRaum 17; the separate third-place result stays attached to the correct model. Use the site's actual article type; do not force NewsArticle when the archive uses Article.

Schema.org's [award](https://schema.org/award) expects text, not an Award object, and supports only certain recipient types. The picker can accept any entity while the output mapper emits award only where valid. For a recipient outside that domain, retain the result in the backend and connect a real report using subjectOf when appropriate. Show this limitation clearly; do not pretend every internal arrow is public schema. No fake Award @type, Event or CreativeWork wrapper is needed.

There is no need to assign a public @id to a record that is not emitted as a public entity. It still has stable internal identity and references. If an awards overview lists reports, its public list items refer to those real reports, not invented award URLs.

Certification is different: [Certification](https://schema.org/Certification) is a public type and can have an ID. A separate certificate record can include the actual subject, issuer, certificate identifier, evidence and validity information when available. Standards claims without a documented certificate must not be converted into fabricated issued certificates. Awards and certificates can share editor components, but not their public meaning.

## D05 explained with VIACOR

Consider three statements:

| Statement | Meaning | Treatment |
| --- | --- | --- |
| ACTIVE court is a flooring system | A product offered to customers | Product entity |
| Parking → Ramps groups suitable systems | A category hierarchy | Reuse source categories and public collection pages; not another Service just because it has a page |
| Meike is the sales contact for this application area | A responsibility assigned to a person | Keep the contact assignment; do not automatically emit knowsAbout or provider |

Knowing about a topic, being the sales contact, and delivering a service are different facts. If the public biography actually says the person specializes in that subject, knowsAbout may be appropriate. The sales assignment alone does not prove it.

The unresolved choice is whether Schema Manager should store/show that existing sales assignment for editorial navigation even when it has no agreed exact public mapping. My recommendation: let the source adapter expose it, label it **editorial relationship**, and avoid creating a second editable assignment. A graph filter can show actual public schema only. We do not need a generic role editor just to reproduce existing client assignments.

## Languages and domains

Keep the user's model: global facts on the entity, language-dependent content and URL on the presentation. Every URL resolves one language version. The source adapter supplies the correct URL for that version.

The remaining question is narrower: what happens if the same entity appears on two German-language domains? That is two placements, not two translations. Prefer one DE text and a chosen DE home, with explicit references from the other site, unless a real requirement for different presentation emerges. Do not add a general site-specific content override system pre-emptively.

## Narrowed event and import scope

Events may have an optional attached recording, reused from an existing content/video field where available. It can emit a VideoObject connected through recordedIn/recordedAt, without a standalone recording manager or course support. Missing upload dates, thumbnails or rights are not invented. The Event retains its real occurrence dates; an available replay is not an upcoming event. Whether more than one recording is needed remains open.

Keep current import behavior as the baseline. New supported definitions need enough import/AI awareness to avoid duplicate identities, but a generalized conflict-resolution or emitter-ownership redesign is not a prerequisite for this release. Record unsupported preservation cases as limitations and avoid destructive conversion. The removed comparison feature stays removed.

## What is deliberately not decided yet

- The actual broad launch type list, which capabilities each exposes, and searchable editor organization.
- The stable source-adapter contract, permissions, publication/fallback policy and source-key migration.
- Single versus multiple event recordings and where media facts come from.
- How award/certificate content appears in frontend overviews; no automatic frontend module commitment.
- Whether specialist book/vehicle/medical capabilities have sufficient real examples.

Next round should walk through one OKAL source binding, one translated VIACOR system, one award article with two results, and one existing event with a recording. Agree their editor behavior and output before drafting APIs or implementation tasks.
