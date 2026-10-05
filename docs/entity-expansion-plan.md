# Coordinated entity expansion

Status: planning only, parked until the real client examples are available.
Date: 2026-10-05
Branch: codex/feature-entity-expansion (based on main).

## Delivery decision

Develop and release one coordinated expansion covering the agreed entity families, their relationships, editorial forms, localization, graph display and AI support. Do not split the work into successive small type releases. Internal implementation steps and tests can be incremental, but the user-facing delivery is one coherent update. The previous suggestion to prioritize separate smaller additions is superseded.

This branch currently contains documentation only. No schema changes, migrations, runtime changes or deployment are part of this planning task. No version is assigned yet. Existing SoftwareApplication support is already in main. Reconcile the current AI and calendar branches when implementation begins; do not assume those features have been released. Smart image and product-source hook work remains separately parked.

## Gather examples before fixing the model

The user will supply representative public pages and explain their underlying Contao sources. Review the examples together across all families before agreeing the final supported types and properties. In particular, Diakonie is substantially more complex than the earlier hospital/clinic sketch: that sketch is not an approved model or a complete scope.

For each example record: page URL, actual subject, existing JSON-LD and IDs, source record/module, related entities, shared facts, translated facts, publication rules, expected output and editorial maintenance workflow. Separate a product model from a physical instance, an organization from its locations, and an offering from an article describing it. Capture examples with no public price, multiple languages and shared entities across pages.

## Candidate families (not a final type commitment)

| Family | Examples and modelling questions |
| --- | --- |
| Houses and locations / OKAL | Orderable house models versus specific built houses; show houses and showrooms; organization/location hierarchy, address, coordinates, opening hours, displayed models, floor area, rooms and other specifications. Evaluate Product/ProductModel, House/SingleFamilyResidence, Place and LocalBusiness with the actual examples. A design is not automatically a physical Place. |
| Flooring systems / VIACOR | Named flooring systems as Product, installation/consulting as Service; applications such as hospitals and running tracks, materials, thickness, colours and documented technical properties. Reuse additionalProperty/PropertyValue only where no appropriate native property exists. Distinguish a system, its components, application categories and installed reference projects. |
| Healthcare / Diakonie | Discover the real organizational, location, department, personnel, care and other content structures first. MedicalOrganization, Hospital, MedicalClinic and MedicalProcedure are candidates, not a sufficient or mandatory set. Verify the meaning and allowed relationships of every chosen type against the source pages. Do not force all content into Service or infer medical claims. |
| Publishing | Book, Periodical, PublicationIssue and links to existing Article/NewsArticle handling. Authors, publisher, ISBN/ISSN, editions, formats, issue numbers, publication dates, language and offers. Distinguish a publication series, issue, article and separately purchasable edition/subscription. |
| Vehicles | Vehicle/Car and further subtypes only when examples justify them; model versus individual stock vehicle, manufacturer, technical specifications, condition, mileage, VIN and offers. |
| Services | Retain Service as the common model for actual service offerings. Improve reusable relations and field groups only where examples demonstrate a gap; do not invent a specialist type for every service category. |
| Awards and recognition / especially OKAL | Reusable editorial records, exact recipients, award year/category/result, awarding body, evidence and award reporting. Keep distinctions between an award, a certification, a rating and an article. See below. |

## Awards: editorial records and schema output are different decisions

Goal: make it easy to write useful articles about awards clients won and connect those articles to the right company, house model, product, service or person. An award for a house model must not silently become an award for every house or for its manufacturer.

Candidate editorial record fields (validate with OKAL examples):

- Official award name, edition/year, category and distinction (winner, finalist, etc.).
- Explicit recipient(s) and awarding organization, with existing entity references where appropriate.
- Award date, official evidence URL and optional supporting media.
- Linked Contao news articles and a canonical editorial home if there is one.
- Translated explanation/reporting text; shared identity, recipient, date and official facts. Preserve existing globally stored company awards. Do not silently move them back into translated fields.

Schema.org's `award` property takes Text, not an award object or an @id reference. It applies to Organization, Person, Product, Service and CreativeWork. Therefore a richer internal award record can generate a concise, accurate textual award value on its supported recipient, without pretending that all internal metadata has a direct schema equivalent. Do not introduce a made-up Schema.org Award type or emit arbitrary fields into a generic node.

An article about winning an award can use the archive-selected Article/NewsArticle type and `about` to reference the actual recipient(s). Evaluate `subjectOf` on the recipient where appropriate. Preserve archive-level type selection; do not introduce per-news type overrides. Use `mentions` for genuinely secondary subjects. A ceremony is an Event only when a real event is represented, not merely because an award exists.

Certification is a separate candidate for actual authoritative certifications, with issuer, identifier and validity data. An ordinary prize must not be relabelled Certification merely to obtain richer fields. Likewise, jury scores are not automatically customer aggregate ratings. Decide the best output for each example before introducing schema relations for it.

Existing free-text awards must remain valid and survive any migration. Decide whether richer award records supplement them or replace selected lines through an explicit editorial conversion. Avoid duplicate output and do not create awarding organizations solely from unverified mentions.

## Common design work

- A central, extensible type definition mechanism should describe shared/localized fields, allowed relations, output mapping, validation and AI permissions. Audit current hardcoded type lists before designing it. Adding a type must consistently update forms, graph, import, analysis and output.
- Reuse field groups for identity, locations, offers, technical quantities, publications, vehicles and healthcare where semantics match. Do not assume every entity is a Product or every organization relation means the same thing.
- Consider per-site enabled families/types to keep selectors short. Determine how existing records remain editable if a family is disabled. This is a proposed UX decision, not implemented functionality.
- Keep stable IDs and localized homes. Define global versus translated fields explicitly; preserve imported IDs and existing supported properties.
- Provide constrained relation pickers with meaningful labels and exact Schema.org property mappings. Check property domains/ranges and multiple-type modelling where necessary.
- Manual maintenance must remain fully usable without AI. The optional AI helper should propose only supported facts/relations with evidence, respect existing data, and understand the expanded types in initial import, new-entry and improvement runs.
- Display new entities and meaningful relationships in the existing graph, including isolated entities, localized labels and existing page/source visibility filters.

## One implementation and acceptance round

1. Review the complete example set and agree an entity/property/relationship matrix, including gaps that remain out of scope.
2. Design the shared extension points and editorial forms against that matrix; decide award storage/output and healthcare structure before coding them.
3. Implement the agreed families together, including localization, import/AI rules, graph integration and additive migrations.
4. Validate on representative development fixtures drawn from the supplied examples. No unrequested live content changes.
5. Review the entire expansion with the user, document it, then merge/release as one coordinated update when authorized.

Acceptance must cover correct recipients and relationships, draft/active records, translations, stable imported IDs, no duplicate entities, safe changes to existing data, and regression coverage for current types. Validate emitted JSON-LD and distinguish Schema.org validity from any separately verified search-engine feature eligibility. Include editorial walkthroughs and screenshots for the final documentation. Do not promise rich results from adding types.

## Reference starting points

Recheck these when implementation begins; they describe vocabulary, not a complete client-specific model.

- [Schema.org award: text values and supported subjects](https://schema.org/award)
- [Schema.org Certification](https://schema.org/Certification)
- [Product](https://schema.org/Product), [ProductModel](https://schema.org/ProductModel), [House](https://schema.org/House), [Place](https://schema.org/Place)
- [MedicalOrganization](https://schema.org/MedicalOrganization), [MedicalProcedure](https://schema.org/MedicalProcedure)
- [Book](https://schema.org/Book), [Periodical](https://schema.org/Periodical), [PublicationIssue](https://schema.org/PublicationIssue)
- [Vehicle](https://schema.org/Vehicle), [Car](https://schema.org/Car)
