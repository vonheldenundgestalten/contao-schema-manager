# Versions and migration

Package versions follow [Semantic Versioning 2.0.0](https://semver.org/), independently of the supported Contao version:

- **Patch**: backwards-compatible fixes.
- **Minor**: backwards-compatible features and additive database fields.
- **Major**: incompatible changes to supported configuration, documented integration APIs, entity identity/output contracts or required migration behavior.

The supported PHP/Contao ranges are declared in composer.json. The 1.0.0 baseline requires PHP ^8.3 and Contao ^5.7. Tags are immutable once released; the withdrawal of the earlier 5.7.0 pilot tag is a one-time version-number correction. Future releases will not reuse, move or renumber published tags.

## Existing 5.7.0 installations

The earlier release/tag was withdrawn at the maintainer’s request. Its commit history remains intact. It does not mean this extension now targets Contao 1.0.

From the application root, change the **package** constraint:

```sh
composer require vonheldenundgestalten/contao-schema-manager:^1.0 --with-all-dependencies
```

Review Composer’s proposed dependency changes, then run the normal Contao database update and rebuild the application cache. The new schema fields are additive; existing entity IDs, translations, manual offers and News configuration are retained. Do not accept unrelated DROP suggestions or remove retired experimental data blindly. Back up first. Local path installations on dev-main can continue using their existing path setup.

## Preserve established JSON-LD IDs

For a new entity, optionally enter its existing HTTPS ID in **Permanent entity ID** before the first save. It must use the saved identity origin and be unique. Leave it blank to generate an ID. Once persisted, the ordinary editor cannot change it.

For an already-created entity, use the explicit migration command from the Contao application root. It checks both the expected current ID and uniqueness. The default is a dry run:

```sh
php vendor/bin/contao-console schema-manager:adopt-id 42 'https://example.org/#organization' --expected-current-id='https://example.org/#entity-old'
```

Review the displayed mapping, then repeat with `--apply`. This changes the entity ID and invalidates entity cache tags. Relationships stored as record selections follow the change. Derived offer/catalogue/contact IDs also change with their parent prefix; the command does not rewrite hard-coded JSON, external references or third-party caches. Inventory those references before applying it. The normal editor remains immutable after adoption.

## Release checklist

1. Update CHANGELOG and documentation with a semantic package version; retain the correct Contao compatibility range.
2. Run Composer validation, PHP lint, mapper tests and the configured Contao integration suites.
3. Verify a core-only installation, backend editing and frontend output on the pilot.
4. Push main and wait for PHP 8.3/8.4 CI to pass for that exact commit.
5. Create an annotated version tag and a GitHub release pointing to that commit. Verify a fresh Composer consumer can resolve the tagged version.
