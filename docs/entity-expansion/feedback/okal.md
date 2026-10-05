# OKAL: feedback for round 2

[Findings](../sites/okal.md) · [Shared decisions](00-shared-decisions.md) · [Planning index](../../entity-expansion-plan.md)

Add answers and example URLs below. Database/source details cannot be established from public HTML. An unanswered item remains open; these examples refine shared capabilities rather than commissioning a bespoke integration.

## OK01 — Models and individual buildings

**Status:** Open · **Dependencies:** C05/C06/C13; D04

**Feedback needed:** Which Contao tables or extensions own catalogue models and show houses? Please give one confirmed model ↔ built-house pair, including whether the physical house is for sale. Which area values are living area versus DIN 277 net area?

**Working recommendation:** ProductModel and House remain distinct; authoritative measurements stay on their source.

**Response / examples:**

> Pending.

**Decision / date:** Pending.

## OK02 — Awards and reporting

**Status:** Open · **Dependencies:** C09; D03

**Feedback needed:** Please provide the planned awards overview and representative results, including the exact Black Label 95 award if applicable. Do you need multiple recipients, different placements, award images, jury/organizer and evidence links?

**Working recommendation:** Allow several result records per article. Distinguish award year, article date, winner and placement; do not guess the Black Label award.

**Response / examples:**

> Pending.

**Decision / date:** Pending.

## OK03 — Public adviser selection

**Status:** Open · **Dependencies:** C11/C12; D04

**Feedback needed:** How does the import identify publicly listed advisers and their publication state? Which contact fields are public, and what happens when an adviser leaves or loses login access?

**Working recommendation:** Use explicit directory eligibility; preserve imports as authority. Contact forms need no extra public personal data.

**Response / examples:**

> Pending.

**Decision / date:** Pending.

## OK04 — Locations and visitor information

**Status:** Open · **Dependencies:** C04/C13; D02

**Feedback needed:** Which source owns office/show-house/showroom addresses, appointment-only rules and opening hours? Can several businesses occupy the same site? Do all have detail pages?

**Working recommendation:** Use a Place-capable record once; a LocalBusiness can itself serve as the location. Do not infer 24/7 opening.

**Response / examples:**

> Pending.

**Decision / date:** Pending.

## OK05 — Event venue reuse

**Status:** Open · **Dependencies:** C17/C18; D07

**Feedback needed:** Give an event at an existing house, an office event and an external fair. Are rooms/entrances important, and should historical addresses stay fixed?

**Working recommendation:** Existing venue picker plus one-off and online alternatives; organizer is separate from venue.

**Response / examples:**

> Pending.

**Decision / date:** Pending.

## OK06 — News archives and stories

**Status:** Open · **Dependencies:** C03; D01

**Feedback needed:** Which archives are jobs, ordinary news, awards and customer stories? What structured client/model/service links already exist, and which customer identities may be public?

**Working recommendation:** Keep archive-level schema types; stories and award reports remain Articles, without manufacturing extra Services or household Persons.

**Response / examples:**

> Pending.

**Decision / date:** Pending.
