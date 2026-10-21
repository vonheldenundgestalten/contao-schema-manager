# Diakonie: organizations, care structures and shared identities

[Planning index](../../entity-expansion-plan.md) · [Feedback DI01–DI06](../feedback/diakonie.md)

## What the public structure establishes

This is not a proposal to turn every navigation item into a clinic. The [current overview](https://www.diakonie-klinikum.de/ueber-uns.html) describes a hospital with eight departments, specialist centres and four MVZ. Those public counts differ from the approximate working description, which reinforces the need for flexible relationships rather than fixed numbers or menu-derived entities.

The [hospital imprint](https://www.diakonie-klinikum.de/impressum.html) identifies a hospital operating gGmbH. The [femininum imprint](https://femininum.dks-mvz.de/impressum.html) identifies **DKS Medizinisches Versorgungszentrum gGmbH** as its operator, with a different registration number. Preserve the distinction; shared Contao, branding and contact infrastructure do not merge legal identities. The public pages alone do not prove the complete ownership chain between those operators.

| Evidence | Structural implication |
| --- | --- |
| [Gynaecology department](https://www.diakonie-klinikum.de/leistungsspektrum/kliniken-im-ueberblick/gynaekologie/uebersicht-und-kontakt.html) | Works with urology, pelvic-floor and breast centres; department relationships are not a strict tree. Its explicit absence of gynaecological emergency provision must not be overridden by hospital-level assumptions. |
| [Oncology centre](https://www.diakonie-klinikum.de/leistungsspektrum/medizinische-zentren/onkologisches-zentrum/uebersicht-kontakt-onkologisches-zentrum.html) | Interdisciplinary centre with leadership, coordinator, secretariat, organ centres and stated DKG certification. Different people have different roles. |
| [Breast centre](https://www.diakonie-klinikum.de/leistungsspektrum/medizinische-zentren/onkologisches-zentrum/brustkrebszentrum/uebersicht-kontakt.html) | Describes collaboration across specialties and with femininum; shares people/contact context with the ambulatory practice without making the centre identical to that practice. |
| [femininum](https://femininum.dks-mvz.de/) | Outpatient practice with its own address, hours, team and appointment destination on a separate domain. |
| [Physiotherapy](https://www.diakonie-klinikum.de/leistungsspektrum/weitere-angebote/physiotherapie/uebersicht-und-kontakt.html) | Care provision and organizational team need separate consideration from information pages about a treatment. |
| [Cooperations](https://www.diakonie-klinikum.de/leistungsspektrum/weitere-angebote/kooperationen.html) | Includes independent practices/businesses and other hospitals. A listed cooperation partner is not automatically a subsidiary or hospital department. |
| [STARS](https://www.stars-conference.com/) | Public landing page currently advertises 5–6 March 2027 at LOOK21, plus named scientific organizers affiliated with departments. The hospital's footer address is not the conference venue. |

The sampled pages primarily contained core WebPage/ImageObject metadata and Contao's separate context, not a complete medical graph. STARS did not expose an Event node in this particular raw response; this does not disprove the earlier project-specific content element or its use elsewhere. No private backend data was inspected.

## Proposed model, subject to confirmation

1. Represent the operating legal organizations with their own stable IDs. A hospital entity can carry legal facts if it truly represents the same operating identity; create a separate operator only where the distinction is real. Do not mandate double records for every clinic.
2. Use **Hospital** for the hospital establishment and **MedicalOrganization** for genuine clinical departments or interdisciplinary centres. A department is an Organization relation (`department`), not another full Hospital solely because the German label is “Klinik”.
3. Use **MedicalClinic** for the ambulatory clinic/practice where that definition fits. The practice can have a confirmed parentOrganization/operator relation and a hospitalAffiliation only through a type/property combination that supports it; do not attach that property indiscriminately to all medical entities.
4. Model buildings, entrances and rooms as Place only when location reuse needs them. `containedInPlace` handles physical containment; it must not be used for management responsibility.
5. Clinicians remain **Person** for employment, bylines and public profiles. Schema.org now also defines IndividualPhysician and PhysiciansOffice, but their hierarchy and pending `practicesAt` support require a deliberate compatibility decision. Do not simply replace Person with Physician and assume it is a Person subtype.
6. Use **MedicalWebPage** for actual medical information pages, with reviewed authorship/review dates only when real. A department's contact page need not be MedicalWebPage. Procedure/condition information is not the hospital itself.

## Care services and evidence boundary

General care offerings can remain Service with provider where truthful. Specific MedicalProcedure/MedicalTest/MedicalTherapy entities need clinician-reviewed scope; the specialized availableService relation expects those medical types, not generic Service. Do not invent treatment indications, outcomes, emergency provision, medical credentials or procedural details from navigation labels.

A centre's accreditation is a certification of its defined scope, not an award for every doctor or the whole group. Map people once across their employment, centre roles, authorship and conference roles. A global jobTitle alone cannot express all simultaneous roles; start with explicit role context in the editor and only export supported relations.

STARS stays a standalone Event with a homepage home; Calendar use is optional. Organizer, scientific lead, speaker and sponsor are distinct roles. Reuse a public LOOK21 Place if present, or a one-off venue, retaining the correct address rather than inheriting the operator's footer.

## Hard requirements this adds

C01/C04/C10–C18: many-to-many affiliations, reusable places, multilingual/multi-domain placement, source detail homes, exact certificate subjects, clinical review and role distinctions. The current same-language-first home resolution and AI same-domain default are insufficient for all of this. Confirm Diakonie's actual content tables/relationships before designing an adapter; no plan assumes News or tl_member for the medical directory.
