# Acceptance scenarios for the future expansion

> Round 1 research/proposal. [Round 2](round-2.md) takes precedence for catalogue breadth, source-field authority, event attachments and the decision to defer import redesign. Still planning; no implementation approved.

[Planning index](../entity-expansion-plan.md) · These are planning criteria, not implemented or executed tests.

Approve the semantics and source ownership first. Then turn the accepted scenarios into focused integration tests and editorial walkthroughs.

| ID | Scenario | Expected result | References |
| --- | --- | --- | --- |
| A01 | House model and an actual show house | Different identities; model measurements retain their exact meaning; physical address belongs to the place | C05/C06; OK01 |
| A02 | One award article, two houses, different placements | Two result records, correct recipient/output for each; article covers both; no award copied to every house | C09; OK02 |
| A03 | Award edition differs from article publication year | Both dates preserved; company award does not become a product award | C09; OK02 |
| A04 | Flooring system with four constituents and three standards badges | System and materials remain distinct; material links valid; no invented certificates or fixed quantities | C07/C10; VI02/VI03 |
| A05 | Parking → Ramps category whose home is a section | Source hierarchy preserved; real anchor supported; no fake Service or non-existent broader property | C08/C13; VI04 |
| A06 | Sales leader responsible for a category | Person/contact role retained without pretending they manufacture all products or author every page | C12; VI05 |
| A07 | Public member with disabled login; private member with enabled login | Only the explicitly public profile appears; login entitlement does not decide publication; private fields never emitted | C11; OK03 |
| A08 | One person linked to a backend author and member profile | Explicit confirmed identity reused, distinct source keys, no name-only merge; reader URL resolves to the person | C11/C13; D04 |
| A09 | Public source withdrawn or renamed | Dependent output/cache updates; stable ID survives URL change; historical byline behavior follows agreed policy | C11/C13; D04 |
| A10 | Hospital department, interdisciplinary centre, MVZ and independent partner | Correct different relationships and operators; no flattening into branches or accidental ownership claims | C01/C15; DI01 |
| A11 | Hospital, practice and conference on different domains | Approved shared identity reused; correct publisher and home per context; footer address does not become conference venue | C14/C17; DI05/DI06 |
| A12 | Event reuses show house, with a particular entrance | One place, correct event-specific detail; venue address visible in event output; coordinates remain manual | C04/C17/C18; OK05 |
| A13 | A shared venue later moves | Future occurrences use agreed current data; historical output obeys explicit snapshot policy | C17; D07 |
| A14 | Group has legal subsidiaries and several offices | Parent/subsidiary and physical location stay separate; user-announced future countries are not published as current facts | C01/C04; WO01 |
| A15 | Named case and anonymous case, each involving a service | Article remains content; meaningful about/mentions links; no invented customer property, customer identity or deliverable Service | C03; WO03/VH01 |
| A16 | Existing Agorum software block enters management | All approved existing IDs, applicationSubCategory, browserRequirements and offer facts survive; unsupported independent blocks remain intact | C20; AG01 |
| A17 | New native field overlaps preserved imported JSON | Explicit ownership/precedence; no silent overwrite, list loss or resurrection of cleared legacy values | C20; D08 |
| A18 | Live webinar, available recording, recap, presenter and author | Distinct meaningful records and roles; no fake upcoming date, organizer or rating; online Google eligibility described accurately | C19; AG02 |
| A19 | Same entity in DE/EN and twice in the same language across sites | Shared facts consistent; explicit home/placement chosen, no first-row routing; existing language-specific IDs handled by approved migration | C14; D06 |
| A20 | Cyclic parent/topic/service relations and many shared entities | Bounded deterministic output, no whole-site recursive graph, meaningful compact linked entities and cache dependencies | C23; D01 |
| A21 | Backend graph shows award/contact relationships | Editorial-only edges distinguishable from actual emitted Schema.org properties; publication/language filters truthful | C23; D05 |
| A22 | Optional bundle/source adapter absent, source deleted or user lacks access | No fatal error or leakage; actionable unresolved-binding state; no guess at another record with the same numeric ID | C13/C23; D04 |
| A23 | AI proposes additional entities and relationships | Uses enabled definitions, cites evidence and dependencies, reuses known identities, avoids incidental client/medical/certification claims | C20/C23; D08 |
| A24 | Existing 1.2.0 site upgrades without enabling new families | Existing IDs, awards, manual offers, archive defaults and schema preserved; no automatic publication, emitter deletion or data reclassification | All; D01/D08 |

## Planning gates

1. Resolve shared decisions and representative client mappings in the feedback files.
2. Agree editor walkthroughs: create/bind entity, choose location, record award, attach certificate, publish translation, withdraw source.
3. Produce reviewed sample JSON-LD for the ambiguous cases and test vocabulary/library compatibility.
4. Freeze the coordinated release scope, including books/vehicles/training decisions; only then authorize implementation.

No deployment, runtime test suite, API analysis or client data mutation is required to validate this documentation round.
