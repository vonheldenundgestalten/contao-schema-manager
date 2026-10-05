# Vocabulary and consumer decisions

[Planning index](../entity-expansion-plan.md) · Proposed mappings, not implemented features

Checked on 5 October 2026 against the official Schema.org vocabulary (the site displayed V30.1, 16 September 2026). Some useful properties are pending. Pin and recheck the chosen vocabulary and Contao/library support before implementation; a valid vocabulary term does not automatically have a convenient library class or Google feature.

## Types: choose the thing, not the page template

| Editorial profile | Proposed public type | Boundary |
| --- | --- | --- |
| House design | [ProductModel](https://schema.org/ProductModel) | A specification offered repeatedly; not a physical address |
| Built show house | [House](https://schema.org/House), optionally SingleFamilyResidence | A real accommodation/place; add Product only when that same physical house is actually being offered as a product |
| Sales office | [LocalBusiness](https://schema.org/LocalBusiness) | A local business establishment, not every building |
| Showroom or event venue | [Place](https://schema.org/Place) | More specific type only when accurate; a showroom need not be a Store |
| Raw material / flooring system | [Product](https://schema.org/Product) | Composition and certification distinguish the system, not a fictional System type |
| True product variants | [ProductGroup](https://schema.org/ProductGroup) | Not a generic category or set of constituent materials |
| Department / clinical centre | [MedicalOrganization](https://schema.org/MedicalOrganization) | Hospital or MedicalClinic only when the establishment actually fits |
| Hospital / outpatient clinic | [Hospital](https://schema.org/Hospital), [MedicalClinic](https://schema.org/MedicalClinic) | Legal operator, clinical department and site need not be the same entity |
| Clinician / adviser | [Person](https://schema.org/Person) | Baseline personal identity; office/business types are not substitutes for a person |
| Case study / award report | [Article](https://schema.org/Article) | Case study is an editorial profile, not a Schema.org CaseStudy class |
| Available webinar recording | [VideoObject](https://schema.org/VideoObject) | Distinct from the scheduled Event and the recap Article |
| Structured training | [Course](https://schema.org/Course), [CourseInstance](https://schema.org/CourseInstance) | Only a real course and its delivery; not every webinar |
| Medical information page | [MedicalWebPage](https://schema.org/MedicalWebPage) | Not every page on a healthcare domain |

Earlier requirements remain in scope discussion: [Book](https://schema.org/Book), [Periodical](https://schema.org/Periodical), [PublicationIssue](https://schema.org/PublicationIssue), [Vehicle](https://schema.org/Vehicle) and [Car](https://schema.org/Car). Publishing must distinguish a work, edition, issue and article from its commercial offer. Vehicles must distinguish a model from a stock unit; VIN, mileage and condition describe the unit. Examples are still needed before agreeing their fields. Multi-typing is acceptable only for one real thing with both meanings, not to force unrelated properties onto it.

## Relationship contract

| Meaning | Mapping / condition | Avoid |
| --- | --- | --- |
| Subsidiary or department | Organization.[parentOrganization](https://schema.org/parentOrganization), subOrganization, or [department](https://schema.org/department), as appropriate | Treating partners, group members and offices as interchangeable |
| Physical containment | Place.[containedInPlace](https://schema.org/containedInPlace) | Using geography to imply legal ownership |
| Employment / expertise / workplace | Person.[worksFor](https://schema.org/worksFor), [knowsAbout](https://schema.org/knowsAbout), [workLocation](https://schema.org/workLocation) | Equating expertise with responsibility for sales or delivery |
| Service delivery | Service.[provider](https://schema.org/provider) → Organization or Person | Using serviceOperator: its domain is GovernmentService |
| Contact channel | Organization/Person.[contactPoint](https://schema.org/contactPoint) → ContactPoint | Putting a Person directly into contactPoint or adding it arbitrarily to Product |
| Article subject | Article.[about](https://schema.org/about), mentions; entity.[subjectOf](https://schema.org/subjectOf) → Article | Inventing client on an Article; customer belongs to Order/Invoice |
| Product constituents | Product.[material](https://schema.org/material) → Product, Text or URL | Generic hasPart product composition: hasPart is for CreativeWork |
| Actual quantified bundle | Offer.[includesObject](https://schema.org/includesObject) → TypeAndQuantityNode | Converting material consumption per square metre into a fixed retail bundle |
| Product category | [category](https://schema.org/category) → text or appropriate concept | Calling every application area a Service |
| Reusable topic | DefinedTerm or CategoryCode; retain hierarchy in source taxonomy/breadcrumbs | Inventing a Schema.org broader property |
| Event venue | Event.[location](https://schema.org/location) → Place/VirtualLocation | Reusing an organizer's footer address as the venue |
| Recording and occurrence | Event.[recordedIn](https://schema.org/recordedIn) → CreativeWork; CreativeWork.[recordedAt](https://schema.org/recordedAt) → Event | Turning an on-demand video into an upcoming event |

[displayLocation](https://schema.org/displayLocation) could connect a house model/product to a display site, but it is pending in the checked vocabulary. Treat it as a compatibility decision, not a guaranteed launch dependency. [model](https://schema.org/model) belongs on Product, not a plain House. Do not invent a direct model-of-house property if the physical instance is not appropriately a Product too. The internal model/location relation can remain useful without an exact public edge.

[ProductCollection](https://schema.org/ProductCollection) is also pending and should not become the default solution for every system or category.

## Measurements need meaning as well as units

Use native properties where their domains fit. [floorSize](https://schema.org/floorSize) belongs to Accommodation/FloorPlan, not ProductModel. For model specifications use [additionalProperty](https://schema.org/additionalProperty) / appropriately supported measurements with an explicit label and unit. Net floor area under DIN 277 is not automatically living area. Thickness ranges need min/max and millimetres; do not turn a range into an exact scalar. Shared numeric facts remain global; labels can be localized.

## Awards: an editorial record with conservative public output

Schema.org has no general Award entity class in the checked vocabulary. [award](https://schema.org/award) is **Text**, available on Organization, Person, Product, Service and CreativeWork. A plain House does not inherit that domain.

Recommend a recognition-result record containing scheme, edition/year, category, distinction, exact recipient(s), evidence URL and related reporting. Emit a readable award string on eligible recipients. Link reporting using Article.about and recipient.subjectOf when that accurately describes the article. Do not put the award on the reporting Article as if the Article won it. Do not fabricate an Award node or add an Event unless there really is a ceremony/event worth describing separately.

One article can report multiple results; publication date and award edition are independent. Existing free-text company awards remain valid and must survive migration. A historical result is not a certification with an expiry date. Distinguish genuine prizes from partner tiers and accreditation.

## Certificates: preserve the actual scope and evidence

[hasCertification](https://schema.org/hasCertification) supports Organization, Person, Place, Product and Service. A [Certification](https://schema.org/Certification) can carry issuedBy, certificationIdentification, dates/expiry and its subject. Use actual issuer and evidence; a standards logo alone does not establish a certificate number, certification body or validity period.

A claim that a system meets DIN EN 14877 can be recorded as an evidenced standards claim even when certificate metadata is unavailable. Do not invent an issuer called DIN from a badge. A system certificate must not propagate to constituent materials; a company management-system certificate is not a product certificate. Reusable schemes and individual issued certificates are different records/concepts.

## Medical vocabulary requires a separate compatibility check

[IndividualPhysician](https://schema.org/IndividualPhysician) now exists, but inherits Physician/MedicalBusiness/MedicalOrganization rather than Person. [PhysiciansOffice](https://schema.org/PhysiciansOffice) is another distinct option. Start with Person for personal profiles; agree how newer physician modelling coexists before introducing extra identities or multi-types. [practicesAt](https://schema.org/practicesAt) is pending in the checked vocabulary.

[availableService](https://schema.org/availableService) expects MedicalProcedure, MedicalTest or MedicalTherapy, not generic Service. A department's service catalogue can still use ordinary Service through suitable general modelling; do not force every offering into a medical procedure. Specialty, credentials, emergency availability and treatment claims require explicit maintained facts. Shared branding cannot establish medical or legal responsibility.

## Google support is a separate layer

Schema.org validity, Google feature eligibility and actual display are three different checks. Rich Results Test does not enumerate every valid graph type. No additional property guarantees a rich result or ranking improvement.

- [Product snippets](https://developers.google.com/search/docs/appearance/structured-data/product-snippet) require the relevant offer/review/rating information. Keep genuine non-priced catalogue products valid without fabricating free offers or ratings.
- [Software apps](https://developers.google.com/search/docs/appearance/structured-data/software-app) have stricter rich-result requirements, including price and a review or aggregate rating. Do not synthesize reviews to qualify.
- [Event guidance](https://developers.google.com/search/docs/appearance/structured-data/event) currently excludes virtual-only experiences from Google's event experience. Online Event schema remains meaningful; a webinar is not made physically located to obtain eligibility.
- [Video](https://developers.google.com/search/docs/appearance/structured-data/video), [Article](https://developers.google.com/search/docs/appearance/structured-data/article), [LocalBusiness](https://developers.google.com/search/docs/appearance/structured-data/local-business) and [JobPosting](https://developers.google.com/search/docs/appearance/structured-data/job-posting) each need their own required fields and visible-content rules.
- Google's [documentation updates](https://developers.google.com/search/updates) report FAQ rich results ended on **7 May 2026**, with the documentation subsequently removed. Preserve useful existing FAQ markup, but do not promise the former healthcare/government FAQ search feature.
- Apply Google's [general structured-data policies](https://developers.google.com/search/docs/appearance/structured-data/sd-policies). Hidden private data and claims not supported by the page are not enrichment.

Before development freezes, test representative output through Contao's JsonLdManager and its supported library versions, including multiple @types, imported properties and newer vocabulary. Internal-only relationships should be clearly labelled in the manager graph; adding a custom @context to make unsupported relationships appear standardized is not a solution.
