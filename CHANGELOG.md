# Changelog

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
