# Changelog

## Unreleased — Calendar feature branch

- Reusable Place venues with address, manual coordinates and map links; existing LocalBusiness entities are available as event venues.
- Standalone and calendar events choose an existing or custom location, with conditional online/hybrid fields and preserved hidden data. Explicit selections avoid mixing venue facts with old custom/calendar addresses.


## 1.2.1 — 2026-10-05

- Preserve literal fragments in editable website IDs through Contao input handling. Decode already-stored HTML entities when editing and emitting the website node and its references.

Upgrade from 1.2.0: update the package and rebuild the Contao application cache; clear cached frontend pages. No database migration is needed for this fix. Previously encoded website IDs are corrected on output without resaving; saving the root stores the decoded ID.

## 1.2.0 — 2026-10-05

- Optional administrator-only AI helper with a dedicated OpenAI key, source evidence, reviewable proposals, feedback refinement and resumable analysis batches.
- Guided setup: import existing hand-written schema, establish the organization, configure website/news-archive defaults, then discover or improve content.
- Imports preserve established identities, group translations and retain unmapped JSON-LD. Imported and AI-created entities/homes start unpublished. Original HTML schema must be disabled manually after review.
- Multilingual suggestions follow Contao page translations. Discovery reserves existing identities, including drafts, and scans only eligible active public content.
- Person suggestions from public news bylines, author assignments, and reviewed removal of links to deleted managed entities during improvement runs.
- Dependency-aware application queues create required entities/homes first, recover mappings to existing translations, and explain blocked suggestions. Contao versions and stale-value checks protect edits.
- SoftwareApplication entities with localized requirements/features and manual offers; localized organization profile links and commercial-register labels; editable shared website identities.
- The manual editor and relationship map remain usable without an API key. Calendar adapters, smart images/social metadata, pricing-source hooks and the larger entity expansion remain separate feature work.

Upgrade from 1.1.x or the AI development branch: require `^1.2`, run the Contao database update, install bundle assets and rebuild the application cache. Keep the existing database, entity identities and `.env.local` key. No destructive data reset is required. PHP ^8.3 and Contao ^5.7 requirements are unchanged. AI output remains a proposal requiring editorial review; it does not guarantee factual completeness or search rich-result eligibility.

## 1.1.1 — 2026-10-05

- Shared company awards with a migration preserving existing localized values.
- Worldwide service coverage and named commercial-register identifiers, separate from tax IDs.


## 1.1.0 — 2026-10-04

- Interactive backend relationship map connecting managed entities, websites, pages and News articles, with locally bundled Cytoscape.js/fCoSE.
- Language filtering, grouping by type, content-sized cards, relationship labels, search and diagnostics for unconnected entities and articles without service subjects.
- Excludes noindex pages and bare require-item containers while retaining real article detail pages.
- News Main subjects (`about`) and Mentioned entities (`mentions`) selectors enrich JSON-LD and the relationship map.
- Linked knowledge topics (`knowsAbout`) for people and organizations complement localized expertise text.
- Restored the native primary child-items action for translations.
- Illustrated graph documentation and English/German backend labels.

Upgrade from 1.0.0: run the Contao database update to add `tl_news.schemaAbout`, `tl_news.schemaMentions` (when News is installed), and `tl_schema_entity.knowledgeTopics`. Install bundle assets and rebuild the cache. Existing identities, content and selections are preserved; no data removal is required. PHP ^8.3 and Contao ^5.7 requirements are unchanged.

## 1.0.0 — 2026-10-03

First semantic-versioned release. Requires Contao ^5.7 and PHP ^8.3; package versions no longer mirror Contao versions.

- Organization, LocalBusiness, Person, Service, manual Product and standalone Event entities with stable identities and localized homes.
- Company/contact facts, employee counts, expertise, awards and shared contact points.
- LocalBusiness addresses, region/PO box/fax, coordinates, map links, weekly opening hours and price range.
- Company-to-office and subsidiary links, network memberships, external organization references and reusable company/office/service catalogues.
- Public person contacts, workplaces and localized professional qualifications.
- Event venue addresses and offline/online/mixed attendance.
- Compact supporting organizations, with an explicit NAP-preserving location overview mode.
- Optional News article enrichment and archive-controlled JobPosting with defaults, overrides and expiry-aware output.
- Controlled adoption of established IDs, both at creation and via an explicit dry-run migration command.
- Website/page enrichment, manual offers, English/German editors, saved graph previews and illustrated documentation.

### Version-number correction

The earlier **5.7.0** GitHub release and tag were withdrawn at the maintainer’s request; commits remain in history. Existing consumers should change the package constraint from ^5.7 to ^1.0, run the additive database update and rebuild the cache. See [migration details](docs/versioning.md).

### Deliberately deferred

Smart image/social metadata, source-record adapters and product/pricing hooks remain on their separate unchanged feature branches. Products/offers remain manual. No AccountingService subtype, shop variants/inventory, Calendar adapter or FAQ adapter is included. Existing Contao images and project social metadata remain in place.

### Earlier pilot data

The pilot’s 24 source-linked offers were previously copied to manual fields without changing their identities. Retired experimental database columns were retained for reversibility. Other early pilot installations must preserve any needed source-derived values and their existing social metadata provider before removing experimental code. Do not blindly accept database DROP suggestions.
