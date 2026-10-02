# Development handover

## Pilot verified 2026-10-02

- Dev: https://vhugtech.abnahme-server.de; Contao 5.7.13, PHP 8.4, terminal42/contao-changelanguage.
- Remote path: httpdocs/private-bundles/vonheldenundgestalten/contao-schema-manager.
- Composer path repository and symlink; explicit dev-main version. No GitHub checkout on the server.
- Root Composer files backed up under httpdocs/.schema-manager-backups before registration.
- Credentials and deployment helpers are ignored and not shipped with the package.
- SSH is jailed. CLI uses a process-only DATABASE_URL localhost-to-127.0.0.1 substitution; website DB configuration is unchanged.

## Real content configured

19 shared entities and 36 localized homes:

- VHUG Technologies OÜ, using the legal name/address/VAT ID from the imprint.
- Markus Milkereit and Vanja Jović Radovanović, matching published article authors.
- Four service areas: development, maintenance/support, hosting, SEO/GEO.
- Six hosting packages, three maintenance/support packages and three SEO/GEO packages.
- Both blog archives configured as BlogPosting; all eight posts retain source-owned content.
- DE/EN now share one active WebSite identity and publisher. The old EN ID is retained in storage if that root is separated later.
- Contact and imprint pages reference the company.

The company uses the localized homepage as its home. Markus uses the respective imprint, where he is identified as representative. Vanja has no invented profile page or employment claim: the shared author identity is emitted without a home until an appropriate public page is available.

Pricing names/descriptions/amounts follow existing content rows. General service descriptions were initialized from each page's visible hero text. Original page/news content was not edited.

Seed settings and created IDs are recorded in ignored remote .deploy/vhug-seed-state.json, with a pre-seed backup. No production installation was changed.

## Checks

- PHP lint and Composer validation.
- 9 mapper assertions.
- 10 price assertions (exact/minimum, billing units, unknown amounts).
- 8 real Contao graph/localization/publication checks, fixture transaction rolled back.
- 10 real news replacement/suppression/route/reference checks, fixture transaction rolled back.
- Authenticated backend: module, entity save/immutable ID, archive/news/user/root-page forms, pricing selectors and saved JSON-LD preview.
- Authenticated live crawl: 20 DE/EN pages; all eight article readers; 24 localized Offer representations of 12 shared services; unique IDs and local references checked.
- Contao's separate Page JSON-LD context is internal metadata, not an extra Schema.org WebPage; it remains untouched.

A live-reader test found and fixed loss of the required article route parameter. Regression tests also check that unrelated NewsArticle nodes and arbitrary source properties survive replacement.

## Database scope

Only the extension's entity tables and explicitly listed new columns were installed. Existing tl_hosting_order precision changes and Contao's PrepareForOutputEncodingMigration were left unapplied. No blanket database migration was run.

## Boundaries and next project

This is a working development pilot, not a tagged release. The current Event fields are only a starting point; inspect and port the Diakonie schema element before replacing it. Jobs and FAQ adapters need their real source projects and editorial rules. Product support should describe actual products rather than reclassifying hosting services for rich-result eligibility.

Broader compatibility (other Contao/PHP versions, sites without News, custom canonical URL rules and complex access restrictions) needs a package CI matrix before release. Cache dependencies cover entity/translation/page tables, news archives/items/authors, selected source elements/articles and localized home pages.


## Page and image iteration (2026-10-02)

Added page-purpose selectors, resolved title/description, canonical URL alignment, existing breadcrumb linkage, independent website-home resolution and alternate site name. EN explicitly shares the DE website identity.

Existing DE/EN OG artwork was copied into files/schema-manager and registered through Contao Dbafs, then selected as root fallbacks. No new artwork was fabricated. How We Work pages are AboutPage, contact pages ContactPage, and blog indexes CollectionPage.

The VHUG News hook and page template now yield to centralized social metadata when schemaManageSocial is enabled. These two exact files were deployed and committed separately in vhugtech-bundle (0384ae1); unrelated dirty files were preserved.

Some news entries have custom external canonical URLs for their original publications. The configured canonicals remain untouched; OG URL, WebPage identity and article mainEntityOfPage now agree.

Validation adds 8 pure image-selection checks and 14 real Contao metadata/image/lifecycle checks. Backend selectors, a 20-page crawl, four additional about/contact routes and live social-image requests were checked. Modern lifecycle preparation is exercised in the Contao integration test; the pilot's actual layout remains legacy. Full browser/social-platform previews and real project-specific heroes should be checked on the next client installation.
