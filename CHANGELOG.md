# Changelog

## 5.7.0 — 2026-10-03

First scoped release for Contao 5.7 and PHP 8.3+.

- Shared Organization, LocalBusiness, Person, Product, Service and basic Event entities.
- Permanent identities, localized home pages/content, publication controls and saved JSON-LD previews.
- Manually entered Product identifiers/brand and Product/Service offers, including starting prices and billing units.
- Compact organization references outside localized organization homes.
- Website publisher/identity, page purposes, canonical metadata and core breadcrumbs.
- Optional News enrichment with archive-level types, shared public authors and explicit revision dates.
- English/German backend labels and illustrated setup/editorial documentation.

### Deliberately deferred

Smart image selection/social-tag generation and content-driven pricing are developed on separate feature branches. Core ImageObject and article-image output remains untouched. This release has no dependency on VHUG elements or terminal42 extensions.

### Transition from the unpublished pilot

The pilot's 24 linked offers were backed up and copied to manual localized values without changing entity/offer identities. The site's original social-tag handling was re-enabled. Existing experimental database columns were retained for reversibility; they are not used by the release. Do not blindly apply database DROP suggestions when moving an existing pilot installation to this release.

For other pre-release installations, copy any source-derived values into the manual fields and ensure your existing social metadata provider is enabled before removing the experimental code. Back up first. New installations need no pilot migration.
