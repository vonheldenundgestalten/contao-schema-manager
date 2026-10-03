# Release verification

The 5.7.0 release is developed on main. Feature branches are used only for the explicitly deferred image and product-hook work; see [roadmap](roadmap.md).

Validation includes PHP lint, strict Composer validation, mapper/manual-offer tests, real Contao integration tests for localized entities/publication/compact organization output, news replacement/suppression, and page metadata. A clean core-only installation is checked independently of the pilot.

The pilot uses Contao 5.7.13/PHP 8.4 with DE/EN content. Live checks cover 20 pages, all eight blog readers and 24 localized manual offers. Backend checks cover child lists, localized editing, invalid-price rejection and saving a temporary unpublished Product; the fixture is removed afterwards.

Credentials, browser state and deployment/data-migration helpers are ignored and never distributed. Test fixture writes in the PHP integration suite roll back. Integration scripts run from a configured Contao application's root; use a development environment with the described fixtures.
