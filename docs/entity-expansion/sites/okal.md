# OKAL: models, built houses, recognition and advisers

[Planning index](../../entity-expansion-plan.md) · [Feedback OK01–OK06](../feedback/okal.md)

## Observed evidence

| Source | Observation relevant to the design |
| --- | --- |
| [Black Label 95](https://www.okal.de/haeuser/black-label/black-label-95/) | Catalogue design; area, floors, rooms, bathrooms and floor plans. The public page labels net floor area under DIN 277; it is not automatically living area. Detailed prices/options are gated. No ProductModel node in the sampled raw JSON-LD. |
| [Musterhaus Arnsberg](https://www.okal.de/haeuser/architektenhaeuser/musterhaus-arnsberg/) | A specific visitable building with street address, house facts, tour and named adviser. Visits are by appointment. No House node in sampled JSON-LD. |
| [Erbach sales office](https://www.okal.de/verkaufsbuero/verkaufsburo-erbach/) | Public business address, visiting hours and adviser; fits a LocalBusiness branch better than a bare location. Direct HTTP inspection succeeded after the web index initially failed. |
| [Bemusterungszentrum](https://www.okal.de/bauen/bemusterungszentrum/) | A place for experiencing/selecting house materials and equipment. Classify the actual business operation separately from the building if needed; do not infer retail sales or a tourist attraction. |
| [Steffen Bauer](https://www.okal.de/berater-finden/hausberater/steffen-bauer/) | Named adviser profile and public contact/capabilities. `tl_member` origin and planned individual forms are user-confirmed, not inferred from HTML. |
| [Heckmann story](https://www.okal.de/leben/okal-stories/besser-als-erhofft/) | An editorial customer story with video and a link to Design 22. It distinguishes living area from catalogue facts. No need to create a public household entity merely because the customer is named. |
| [2024 house award report](https://www.okal.de/aktuelles/okal-gewinnt-den-hausbau-design-award-2024/) | One article reports Hampton winning Premiumhäuser and ZweiRaum 17 taking third place in Newcomer. One article therefore needs multiple result links. |
| [2023 house award report](https://www.okal.de/aktuelles/okal-gewinnt-den-hausbau-design-award-2023/) | Bungalow 5 is the recipient in the bungalow category; the manufacturer reporting it is not an interchangeable recipient. |
| [PLUS X 2024 report](https://www.okal.de/aktuelles/okal-mit-plus-x-award-2024-ausgezeichnet/) | Organization-level recognition; the article is dated in 2023 while the award edition is 2024. Publication date must not supply award year automatically. |

The Black Label example is a valid model-design example; this review did not establish a specific award/year for that model. Do not manufacture one for a fixture. The forthcoming awards overview and event-location workflow are user-provided requirements.

## Proposed general model

- **House model:** ProductModel, with manufacturer/brand, model identifier and typed technical facts. Use additionalProperty for model-level room/area facts where the native property domain is a physical accommodation. Do not assign a street address to every copy of the design.
- **Built house:** House or SingleFamilyResidence when supported by the facts. Give it its own identity, address and physical facts. A model can be displayed at this location; current Schema.org `displayLocation` is a pending property and needs an explicit support policy. A House is not automatically a Product instance; use `model` only on a justified Product-typed subject. Keep the internal model/instance relation if there is no agreed public mapping.
- **Sales office:** reuse LocalBusiness with parentOrganization. **Showroom:** Place by default for the venue; LocalBusiness if the record represents a business branch. Avoid requiring a duplicate Place for an existing LocalBusiness.
- **Adviser:** Person linked to the approved public member profile. Employment/affiliation must reflect their actual status. Contact form is a contact route, not another Person, automatic author or reservation action.
- **Story:** archive-controlled Article about the represented house model and service where relevant. Link an available recording as VideoObject only when supported. Keep authorship separate from a sales contact and from the customer quoted.

## Award editing proposal

Offer an optional **Recognition** panel in the News archive/editor, plus a reusable overview in Schema Manager. Editors can create or link several result records while writing the article. Each result has scheme name, edition, category, distinction, exact recipient, evidence and optional issuer. A related report can be the main editorial home; no separate award landing page is required.

The result writes accurate `award` text on supported recipients. The News article keeps its normal type and uses `about` for recipients. Recipients may point back through `subjectOf`. The awards overview is CollectionPage + ItemList of reports/results' public content, not a fabricated Award list. A finalist or third-place result must never be simplified to “winner”. A real physical House alone does not have Schema.org award in its declared domain; link the report and resolve the recipient's identity properly rather than adding a fake type to silence validation.

## Coverage and hard parts

**Ready:** company, sales-office basics, manual people/services, story/news/job archive selection and article subjects (C01–C03). **Add:** house profiles, measurements and Product awards (C05/C06/C09). **Design:** actual member detail homes, imported field ownership, multiple awards per article, model-to-instance relation, visit-only hours and shared event venues (C04/C11/C13/C17).

The future event editor should select Arnsberg/Erbach/the showroom from existing places, or accept a one-off fair venue. An event's date/time does not alter the venue's general opening hours. Do not copy gated prices or infer zero-price offers.
