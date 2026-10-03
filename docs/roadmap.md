# Development after 5.7.0

The release on `main` focuses on manually maintained schema and Contao news enrichment.

- [Smart images and social metadata](https://github.com/vonheldenundgestalten/contao-schema-manager/tree/codex/feature-smart-images): preserves the image resolver, source providers, page controls and social presenter for further generalization. Not part of 5.7.0.
- [Product and pricing contributions](https://github.com/vonheldenundgestalten/contao-schema-manager/tree/codex/feature-product-hooks): preserves the pilot pricing-source adapter and native Contao Offer contribution proof for conversion into a generic integration. Not part of 5.7.0.

These are experimental branches, not supported alternatives to the tagged release. Both start from the reduced core and add only their respective work. Full Event support and FAQ integrations remain future work. Main now adds organization/contact completeness, reusable service catalogues and News-based JobPosting. Source-record bindings and specialized products remain parked pending examples from at least five real projects; no implementation is included in this update.
