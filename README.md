# Contao Schema Manager

Shared Schema.org entities, localized homes and source-owned article metadata in Contao's existing JSON-LD graph. Requires Contao 5.7 and PHP 8.3+. Development version, tested with Contao 5.7.13.

## Available now

- Organization, LocalBusiness, Person, Service and basic standalone Event records.
- Immutable random IDs, independent of database IDs, schema types, page URLs and deployment hosts.
- Shared names/legal facts; localized service/event names, descriptions, roles and representative pages.
- Organization relationships, additional page references, website publisher and WebSite nodes.
- English/German backend labels and saved JSON-LD previews on localized records.
- Archive-level core/BlogPosting/Article/NewsArticle/suppress handling. News fields appear only for enabled archives.
- Shared author entities linked to backend users, with optional article-specific author and explicit revision date.
- Existing article fields, images and body retained; exact source-node replacement preserves unrelated graph nodes.
- Optional adapter for VHUG pricing elements: translated text and prices come directly from the existing element row.

## Install

Add a Composer path repository, preserving other application repositories:

```json
{
  "type": "path",
  "url": "private-bundles/vonheldenundgestalten/contao-schema-manager",
  "options": {
    "symlink": true,
    "versions": {"vonheldenundgestalten/contao-schema-manager": "dev-main"}
  }
}
```

Require `vonheldenundgestalten/contao-schema-manager:dev-main`, rebuild the application cache and review Contao's database migration. The package adds two entity tables and schema-prefixed fields to pages, users and the optional News bundle. Review unrelated migration suggestions separately.

## Editorial workflow

1. **Content → Structured data:** create an entity, choose its type and permanent public identity origin. Never enter the dev hostname as the identity origin.
2. Enter shared facts. Organization and legal names, person names, address and contact details are shared.
3. Open the entity's child records and select one representative page per language. Contao determines its language and URL. Reader pages requiring an item cannot be entity homes.
4. Add localized descriptions and service/event names or a person's role. Mark the page's main subject where appropriate, then publish the entity and its localized home.
5. On other relevant pages, select **Related entities**. Relationships use the same @id, and supporting nodes include the original localized home URL.
6. Set the publisher and website name on each language root. On news archives, choose the article schema and publisher. Map backend authors to public Person entities; the news editor allows an override.
7. For a priced Service, select an existing pricing element on its home page and the appropriate row. Save to refresh the JSON-LD preview.

A published entity with no published home in the current language contributes only shared facts when referenced. It never borrows another language's description or home. Unpublished entities are omitted entirely. IDs must be preserved when moving/importing records.

Dates of article modification are explicit: ordinary administrative saves do not claim that the article was revised. Translated news records use Contao changelanguage's languageMain relationship when available.

## Pricing adapter

The pilot adapter supports the existing VHUG `pricing` element's serialized rows. It validates the owning article/page and publication state. Source row keys are persistent references: when reorganizing or replacing rows, review the linked schema records. The core-only installation does not require the custom pricing field.

Exact amounts use price; “from”/“ab” amounts use minPrice. Monthly/yearly units use referenceQuantity. Unknown or quote-based pricing has no invented amount, currency assumption beyond recognized EUR input, tax status or availability. Each Offer points to its Service and seller.

## Validation

From the package directory:

```sh
php tests/mapper.php
php tests/prices.php
```

From a configured Contao application's root:

```sh
php private-bundles/vonheldenundgestalten/contao-schema-manager/tests/integration.php
php private-bundles/vonheldenundgestalten/contao-schema-manager/tests/news.php
```

Integration tests require published DE/EN pages; news tests additionally require a configured archive, a published organization and a mapped author. All fixture writes roll back. SCHEMA_TEST_ORIGIN sets the test routing origin. SCHEMA_TEST_DB_TCP=1 is an optional process-only workaround for jailed SSH environments lacking the local MySQL socket.

## Deliberately pending

Full Diakonie Event parity, JobPosting fields/replacement, FAQ-checkbox adapters, Product-specific fields, generalized third-party adapters, changelanguage home suggestions and a visual relationship/usage overview. Existing FAQ microdata and handcrafted scripts are not automatically removed. The basic Event type is not yet a replacement for Diakonie's complete element.

The live pilot and verification details are documented in [docs/development.md](docs/development.md).
