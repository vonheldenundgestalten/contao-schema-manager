# Release verification

Released features are maintained on main. Feature branches are used when explicitly requested; the image and product-hook work remains deferred; see [roadmap](roadmap.md).

Validation includes PHP lint, strict Composer validation, mapper/manual-offer tests, real Contao integration tests for localized entities/publication/compact organization output, news replacement/suppression, and page metadata. A clean core-only installation is checked independently of the pilot.

The pilot uses Contao 5.7.13/PHP 8.4 with DE/EN content. Live checks cover 20 pages, all eight blog readers and 24 localized manual offers. Backend checks cover child lists, localized editing, invalid-price rejection and saving a temporary unpublished Product; the fixture is removed afterwards.

Credentials, browser state and deployment/data-migration helpers are ignored and never distributed. Test fixture writes in the PHP integration suite roll back. Integration scripts run from a configured Contao application's root; use a development environment with the described fixtures.

## Organization, service and job update on main

New pure tests cover archive defaults, overrides, employment types, physical/hybrid/remote location semantics, deadlines, missing required data, shared contacts and localized service fields. Real Contao tests cover contact publication, localized service catalogues, deduplication, rejected cycles and dates, JobPosting replacing only its source article, reader URLs, list suppression and private-response cache expiry. The backend forms were saved through Playwright using temporary unpublished fixtures; all fixtures were removed. Screenshots of these example forms are in the README. The existing frontend pilot regression audit covers the unchanged company/service/blog output.

Business integration coverage includes office/group relationships, external references, scoped NAP output, company catalogues, professional credentials, established-ID migration and location field validation. The release procedure and compatibility policy are in [versioning](versioning.md).

## Generic API verification — 20 October 2026

The generic v1 API was deployed to the extension development installation, including additive binding/relationship tables, supplementary payload/version/revision columns and the entityType width increase. Production cache clear/warmup succeeded. Local Composer validation, PHP lint and regression checks pass. Installed Contao checks cover bindings/source ownership, typed payloads, relationships, lifecycle, opt-in AI and explicit migrations, plus existing entity/business/News/Calendar/access checks. Browser checks cover discovery, draft binding, measurement/relationship saves, preview, HTTP JSON-LD and graph output. Anonymous HTTP checks cover withdrawal/restoration, source changes, draft targets and missing sources. Synthetic test services/data are temporary and removed after verification. Calendar-uninstalled behavior is not exercised on this Calendar-enabled installation. No OKAL setup or production was changed.
