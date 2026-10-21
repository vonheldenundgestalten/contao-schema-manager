# Capability and gap matrix

> Round 1 research/proposal. [Round 2](round-2.md) takes precedence for catalogue breadth, source-field authority, event attachments and the decision to defer import redesign. Still planning; no implementation approved.

[Planning index](../entity-expansion-plan.md) · [Shared decisions](feedback/00-shared-decisions.md)

Status is based on **1.2.0 source**, not the richer dev event checkout. **Ready** means a usable manual path exists; **Add** means a bounded addition with understood semantics; **Design** means an ownership, routing or vocabulary decision is needed; **Branch** means separate unreleased work. These are complexity categories, not time estimates.

| ID | Capability / projects | Current position | Proposed handling | Class / feedback |
| --- | --- | --- | --- | --- |
| C01 | Company/group hierarchy: all, especially VIACOR/Woodmark/Diakonie | Organization parent, subOrganization, memberships, external organization URL exist | Reuse; distinguish subsidiary, department, partner and physical site | Ready + Add department; D02 |
| C02 | Services: VHUG/Woodmark/Agorum/medical services | Service, provider organization, localized text, manual offers, subservice catalogue | Reuse for actual services; don't create one for every case or topic | Ready; D01 |
| C03 | Articles, blog, cases, jobs: OKAL/Woodmark/Agorum/VHUG | Archive-controlled Article/NewsArticle/BlogPosting/JobPosting; author/about/mentions | Keep archive defaults. Case studies use Article; optional Report only if justified. No per-news type override | Ready; D01 |
| C04 | Shared locations: OKAL/Diakonie/events | LocalBusiness exists; Event addresses are inline; no managed generic Place | Place/House profiles; reuse LocalBusiness where it really is a branch; scoped compact venue output | Design; D02/D04 |
| C05 | House catalogue and physical show house: OKAL | Only generic Product | ProductModel for design; House/SingleFamilyResidence for built house; conditional multiple types only for the same physical product | Add + Design instance/model link; OK01 |
| C06 | Technical attributes: OKAL/VIACOR/vehicles | SKU/MPN/brand only; no generic quantity rows | Typed measurements and additionalProperty; native properties where valid; units, ranges and shared facts | Add; VI01/D01 |
| C07 | Materials/system composition: VIACOR, future bundles | No composition relation | Product.material references for constituent materials; separate bundle Offer.includesObject when exact quantities are real | Design; VI02 |
| C08 | Hierarchical applications/topics: VIACOR/Agorum/Woodmark | Limited service catalogue; page type picker | Reuse source taxonomy; CollectionPage + ItemList; optional DefinedTerm/CategoryCode for reusable concepts | Design; D05 |
| C09 | Awards/results: OKAL/Woodmark/VHUG | Organization/Person free-text award; no result records or Product awards editor | Shared recognition record with exact recipient + report links; emit award text and Article.about/subjectOf where appropriate | Design; D03/OK02 |
| C10 | Certificates and standard claims: VIACOR/Diakonie/Woodmark | Person credentials are text mapped to EducationalOccupationalCredential | Certification with issuer/evidence/validity; standards claim separate; do not turn every badge into Certification | Design; VI03/DI03 |
| C11 | Public-member people: OKAL, perhaps other teams | Person manual; tl_user.schemaPerson + AI byline suggestions; no member adapter | Explicit public-directory binding and field allowlist; source visibility independent from login entitlement | Design; D04/OK03 |
| C12 | Responsibilities: VIACOR/OKAL/Diakonie/VHUG | Person worksFor, workLocation restricted to LocalBusiness, knowsAbout | Distinguish expertise, employment, membership and contact responsibility; role details may remain internal | Design; D02/D05 |
| C13 | Source-record and section homes: all | Translation page picker; EntityGraph rejects requireItem homes and resolves by language | Typed home target: page, source detail, optional stable section; keep content body on its source | Design; D04/D06 |
| C14 | Multiple domains / same-language homes: Diakonie/Woodmark/VHUG | Shared entity IDs and per-language homes; current selection takes first published translation per language | Explicit owning site + approved placements and reuse scopes; separate translation from routing | Design; D06 |
| C15 | Medical organizations: Diakonie | Only Organization/LocalBusiness | MedicalOrganization, Hospital, MedicalClinic profiles; department/centre distinction; Person baseline for clinicians | Design; DI01/DI02 |
| C16 | Medical content and services: Diakonie | Service and generic page types | MedicalWebPage when appropriate; scoped MedicalProcedure/Test/Therapy candidates with editorial evidence | Design; DI04 |
| C17 | Standalone event + reusable venue: STARS/OKAL | Standalone Event manual, online/physical address | Retain standalone; add shared venue selector with inline option; no fabricated coordinates | Add depends C04; D07 |
| C18 | Calendar events: all event sites | Separate calendar branch has source graph, defaults, identities and manual coordinates | Reconcile that branch through shared location/source definitions; do not duplicate its mapper | Branch + Design; D07 |
| C19 | Webinar recordings and learning: Agorum/Woodmark/Diakonie | Neither VideoObject nor Course editor | Event for scheduled occurrence; VideoObject for available recording; Course/instance only real course | Scope decision; AG02/AG03 |
| C20 | Legacy graph/import: Agorum/VIACOR/Woodmark/VHUG | Supported-type import, retained properties and identity override exist; unknown families not fully editable | Registry-driven supported import; reserved external IDs; field provenance; no silent overwrite or reintroduction of compare UI | Design; D08 |
| C21 | Books/magazines/newspapers: earlier requirement | No managed publication family | Book/Periodical/PublicationIssue; editorial article vs sold edition/offer separated | Design; D09, needs examples |
| C22 | Cars/vehicles: earlier requirement | Generic Product only | Vehicle/Car profile; distinct model vs stock unit; VIN/mileage/condition scoped to instance | Design; D09, needs examples |
| C23 | Graph, AI and schema type consistency: all | Type lists and relation allowlists repeated across DCA, mapper, graph, AI/import | Central definitions plus explicit adapters/mappers; distinguish internal-only graph relations | Design prerequisite; D01 |
| C24 | Automatic pricing and social images | Separate parked feature branches | Keep out; manual offers and explicit image references continue | Deferred by earlier instruction |

## Source-code evidence and constraints

- [Entity DCA](../../contao/dca/tl_schema_entity.php): seven current managed types; one organization selector is reused for several meanings.
- [EntityMapper](../../src/Schema/EntityMapper.php): explicit type checks; Product organization supplies offer seller, **not manufacturer**. Adding manufacturer is a separate supported relation, not merely using the existing field.
- [EntityGraph](../../src/Schema/EntityGraph.php): references and compact organization output; homes query by entity/language, requireItem exclusion, LocalBusiness-only work locations; subservice catalogues currently include Services only.
- [Translation DCA](../../contao/dca/tl_schema_translation.php): page-based home and localized data together. Source detail homes need more than a new label.
- [NewsGraph](../../src/Schema/NewsGraph.php): source-owned articles and author/subject enrichment already exist; JobPosting has its own path.
- [FieldPolicy](../../src/Ai/FieldPolicy.php), [SchemaImport](../../src/Ai/SchemaImport.php), [ContentRelationshipMap](../../src/Schema/ContentRelationshipMap.php): hardcoded types/relationships that must remain consistent during expansion.
- [ImportedSchema](../../src/Schema/ImportedSchema.php): native editor values win recursively; native lists replace imported lists. New fields require explicit precedence/migration rules so imported values are neither lost nor unexpectedly resurrected.

These observations concern architecture, not a request to refactor during planning. Public sites do not reveal their database tables reliably; only the user-confirmed tl_member/News mappings are treated as known.
