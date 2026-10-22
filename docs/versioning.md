# Versions and migration

Package versions follow [Semantic Versioning 2.0.0](https://semver.org/), independently of the supported Contao version:

- **Patch**: backwards-compatible fixes.
- **Minor**: backwards-compatible features and additive database fields.
- **Major**: incompatible changes to supported configuration, documented integration APIs, entity identity/output contracts or required migration behavior.

The supported PHP/Contao ranges are declared in composer.json. The 1.0.0 baseline requires PHP ^8.3 and Contao ^5.7. Tags are immutable once released; the withdrawal of the earlier 5.7.0 pilot tag is a one-time version-number correction. Future releases will not reuse, move or renumber published tags.

## 1.4 installations and beta upgrades

**1.4.0** is the stable release of extension API v1. It includes automatic source ownership, source-persisted public identities, multi-typed relationship validation, deliberate Person-based authorship and site-language backend source graphs.

```sh
composer require vonheldenundgestalten/contao-schema-manager:^1.4 --with-all-dependencies
```

From 1.3, apply the additive Contao database updates and rebuild the application cache. From beta.3, the generic changes require only an update and cache rebuild. An integration adopting automatic source ownership must also apply its own registered public-ID migration, preserving the IDs already established on that installation, and reconcile its source reference index. Project-specific adapters remain in their own bundles. No minimum-stability change is needed; published beta tags remain immutable.

## Existing 5.7.0 installations

The earlier release/tag was withdrawn at the maintainer’s request. Its commit history remains intact. It does not mean this extension now targets Contao 1.0.

From the application root, change the **package** constraint:

```sh
composer require vonheldenundgestalten/contao-schema-manager:^1.0 --with-all-dependencies
```

Review Composer’s proposed dependency changes, then run the normal Contao database update and rebuild the application cache. The new schema fields are additive; existing entity IDs, translations, manual offers and News configuration are retained. Do not accept unrelated DROP suggestions or remove retired experimental data blindly. Back up first. Local path installations on dev-main can continue using their existing path setup.

## Preserve established JSON-LD IDs

For a new entity, optionally enter its existing HTTPS ID in **Permanent entity ID** before the first save. It must match the identity origin entered in the same form and be unique. Leave it blank on first save to generate an ID. The ordinary editor also permits later explicit ID changes, retaining HTTPS/origin and uniqueness checks. An existing ID cannot be cleared.

For a scripted change to an already-created entity, use the explicit migration command from the Contao application root. It checks both the expected current ID and uniqueness. The default is a dry run:

```sh
php vendor/bin/contao-console schema-manager:adopt-id 42 'https://example.org/#organization' --expected-current-id='https://example.org/#entity-old'
```

Review the displayed mapping, then repeat with `--apply`. This changes the entity ID and invalidates entity cache tags. Relationships stored as record selections follow the change. Derived offer/catalogue/contact IDs also change with their parent prefix; the command does not rewrite hard-coded JSON, external references or third-party caches. Inventory those references before applying it. The normal editor uses the same HTTPS/origin and uniqueness rules; the identity origin stays fixed after first save.
