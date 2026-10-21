# OKAL bundle: deferred integration notes

[API proposal](extension-api.md) · [OKAL research](sites/okal.md)

20 October 2026. Planning only. All implementation belongs in okal-bundle.

## Required gate

Wait until the API-enabled Schema Manager is installed and verified on OKAL dev.
Before that, do not implement adapters/providers, change OKAL tables/dependencies,
run migrations, install packages or deploy OKAL changes. Record the installed
version/commit and confirm API v1, registries, binding/preview/graph and diagnostics
before beginning integration. No guessed minimum Composer version.

## Later work in okal-bundle

1. Inspect actual namespace/package constraints; require the released compatible
   vonheldenundgestalten/contao-schema-manager version and register providers via tags.
2. Inspect tl_houses/import lifecycle: immutable upstream IDs, explicit model/building
   discriminator, translation groups, source field authority and publication rules.
   A row may expose distinct subjects; never classify from title alone.
3. Register house-model/physical-house definitions where core lacks them. Reuse generic
   builders and validation. No OKAL source code or fixtures in Schema Manager.
4. Confirm measurement meanings/units: DIN 277 versus living area, rooms, bathrooms,
   storeys, roof pitch and knee-wall height. No duplicate editable source facts.
5. Confirm real model/show-home pairs and whether physical houses are offered for sale.
   Keep designs and buildings distinct. No public offer from gated/absent prices.
6. Inspect public adviser selection, approved contact fields, employment/affiliation,
   workplaces and withdrawal policy. Login eligibility is not public-directory eligibility.
7. Resolve real localized reader URLs and authorized edit-original links; preserve
   stable identities through imports and alias changes.
8. Register meaningful relationships; unresolved targets remain diagnostics. Avoid
   inferred binding/publication, fake predicates and name-based identity merging.
9. Wire committed source/import/routing changes and scheduled visibility to invalidation.
   Verify withdrawal removes dependent shared cached output.
10. Map recognition/certification records only from confirmed evidence: exact recipient,
    category, placement, edition, issuer, validity and reporting News links. Allow
    several results per report. Do not propagate certifications to all models.
11. Reuse existing Calendar venues/News subjects; inventory legacy emitters and agree
    their ownership/retirement before enabling overlapping output.
12. Verify backend editor/preview/graph and public JSON-LD on OKAL dev with real models,
    show homes, public/private/withdrawn advisers and translated routes. Check import
    refresh, URL changes, missing targets, bundle lifecycle and pending vocabulary.

## Evidence still needed after installation

Actual source schema/import identifiers; confirmed model/building pairs; measurement
bases; adviser publication/contact policy; routing/languages; awards/certificate
subjects; existing JSON-LD emitters. Obtain this during the later integration stage.
These are handover notes, not a request for the user to supply details now or
authorization to access/change OKAL dev before the installation gate.
