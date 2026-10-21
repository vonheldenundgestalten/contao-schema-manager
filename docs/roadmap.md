# Development after 1.0.0

The release on `main` focuses on manually maintained schema and Contao news enrichment.

- [Smart images and social metadata](https://github.com/vonheldenundgestalten/contao-schema-manager/tree/codex/feature-smart-images): preserves the image resolver, source providers, page controls and social presenter for further generalization. Not part of 1.0.0.
- [Product and pricing contributions](https://github.com/vonheldenundgestalten/contao-schema-manager/tree/codex/feature-product-hooks): preserves the pilot pricing-source adapter and native Contao Offer contribution proof for conversion into a generic integration. Not part of 1.0.0.

These are experimental branches, not supported alternatives to the tagged release. Both start from the reduced core and add only their respective work. Advanced event ticketing/recurrence features and FAQ integrations remain future work. Main now adds organization/contact completeness, reusable service catalogues and News-based JobPosting. Source-record bindings and specialized products remain parked pending examples from at least five real projects; no implementation is included in this update.

The feature branches retain their historical baseline and have not been updated as part of 1.0.0. They will need reconciliation with main when work resumes.

## Relationship map — released in 1.1.0

The relationship map is now part of main and the 1.1.0 release. It connects business entities, websites, pages and News records, with language filters, grouping and relationship diagnostics. News subjects/mentions and linked knowledge topics also enrich frontend output. See [scope and use](relationship-map.md). The separate image and product-hook branches remain unchanged.

## AI helper — released in 1.2.0

The optional AI helper, schema import and multilingual review workflow are now included in main. The AI branch is closed after merge. Editorial review remains required; ordinary editing needs no API key.

Calendar-record integration and reusable Place venues are released in **1.3.0**; the event branch is merged and closed. The larger entity expansion is documented on `codex/feature-entity-expansion` pending real project examples. Smart images/social metadata and product/pricing hooks remain on their existing separate branches; none is included in 1.2.0.


## Calendar and reusable venues — released in 1.3.0

Optional Calendar enrichment, calendar defaults, event relationships, AI source support and reusable Place/LocalBusiness venues are included. Physical, online and hybrid schema controls preserve existing custom fields. Calendar uninstall was tested with retained tables; standalone Event/Place editing remains available. The larger entity expansion stays in planning and is not included.

## Extension API planning update - 20 October 2026

The [expansion plan](entity-expansion-plan.md) now specifies a proposed [v1 adapter, type and relationship API](entity-expansion/extension-api.md). All OKAL-specific integration belongs in okal-bundle, requiring the eventual compatible release. [OKAL implementation notes](entity-expansion/okal-bundle-handover.md) are deferred until the API-enabled extension is installed and verified on OKAL dev. No runtime implementation, release or deployment accompanies this planning update.

## Generic extension API implemented on dev — 20 October 2026

The [v1 developer API](extension-api/README.md) now supports independent source/type/relationship providers, stable draft bindings, typed supplementary editing, guarded graph emission, preview, lifecycle invalidation, explicit payload migration and opt-in AI improvements. The generic example is synthetic equipment. Broad industry types and all OKAL bundle work remain deferred; install the API-enabled build on OKAL dev before integration there. The generic API prerelease is 1.4.0-beta.1.
