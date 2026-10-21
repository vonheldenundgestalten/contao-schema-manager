# Round 2 feedback: unresolved design choices

[Proposal](../round-2.md) · [100 use cases](../use-cases-100.md) · [Original responses](00-shared-decisions.md)

**Still planning.** The original answers establish direction only. Answer these over subsequent rounds; no need to resolve everything at once. The highest-value next items are R2-01 and R2-02.

## R2-01 — Broad coverage without 100 separate forms

**Status:** Open.

The catalogue covers 100 complex-offering examples. Many share the same valid Schema.org type. Proposed editor: searchable familiar labels with relevant capabilities, plus developer-registerable definitions. Source adapters remain independent.

**Feedback:** Does this achieve the breadth you want, or do you specifically want a much larger picker of distinct Schema.org classes? Mark missing sectors/use cases by U-number or add examples. The exact launch type list is still outstanding.

**Response:**

> Pending.

## R2-02 — Mapping one real source

**Status:** Open.

Source-owned fields are read-only in Schema Manager even when empty. Additional fields only fill capabilities the source does not own. No duplicate editable values. One authoritative source per entity by default.

**Feedback/examples:** For OKAL tl_houses and one translated VIACOR source, document table/record identity, model-versus-instance discriminator, translation linkage, public eligibility, reader route and which fields are owned. A field list or representative sanitized record is sufficient for the next planning round; no access credentials are needed in these files.

**Response:**

> Pending.

## R2-03 — Award result editor

**Status:** Open.

A backend Award result has name, year, category, distinction, recipients, evidence and report links. Different results from one article are separate records. It emits recipient award text where valid, rather than a fictional Award node. Certificates use their real public type.

**Feedback:** Does the Hampton/ZweiRaum example explain the separation? Should editors create the result centrally, from the News editor, or both? Should the overview use an existing project renderer initially?

**Response:**

> Pending.

## R2-04 — Existing contact assignments in the graph

**Status:** Open; replaces the abstract wording of D05.

A VIACOR category assignment means sales responsibility, not automatically expertise or service provider. Recommend reading the existing source assignment and showing it as an editorial-only edge, without a second editor.

**Feedback:** Is that useful, or should the graph show only exported schema relationships? No new generic role editor is proposed.

**Response:**

> Pending.

## R2-05 — Shared entity on two same-language sites

**Status:** Open; the global/localized split is retained.

**Feedback:** Is one German text and one chosen German home, referenced from the other domain, sufficient for actual Diakonie use? Only provide a counterexample if different same-language presentations really matter; we should not invent complexity.

**Response:**

> Pending.

## R2-06 — Event recording attachment

**Status:** Direction accepted: no standalone recordings or courses. Attachment details open.

**Feedback:** One optional recording or several? Which existing field/content element owns video URL, thumbnail and upload date on Agorum? A concrete event/replay page will determine the mapping.

**Response:**

> Pending.

## R2-07 — Publication and source changes

**Status:** Open.

**Feedback:** Confirm the proposed source-eligibility gate and one-authoritative-source rule, particularly when a public member leaves or an import recreates rows. Should unresolved dependencies remain pending rather than creating/publishing related records automatically?

**Response:**

> Pending.

## Scope retained without further redesign

Company relationships and office LocalBusiness remain the direction from D02. Current import remains sufficient per D08; generalized conflict handling is deferred. Books, periodicals, specialized vehicles and medical depth need real examples before detailed fields are promised. These are recorded boundaries, not a request to reopen everything now.
