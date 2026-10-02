# Contao Schema Manager

**Describe your company, people and services once. Connect them to your Contao content in every language.**

Contao Schema Manager adds shared Schema.org entities to Contao's existing JSON-LD graph. Editors manage company facts centrally, assign each entity a home page per language, and connect it to pages and news without maintaining JSON in HTML content elements.

For example, a hosting business can describe its company once, connect its hosting and maintenance services to it, and use the same people as authors throughout its blog. Each entity keeps one permanent identity even when a page moves or its description is translated.

![Structured data overview with company, people, hosting and maintenance services](docs/images/entities.png)

*Real examples from the bilingual VHUG Technologies pilot. Screenshots show the English Contao backend; German labels are also included. The optional pricing adapter shown below belongs to that project.*

> **Development version:** use `dev-main` in a development environment first. Requires **PHP 8.3+** and **Contao 5.7+ within the 5.x series**; currently tested on Contao 5.7.13 with PHP 8.4. The News bundle is optional. This is not yet a complete editor for every Schema.org type.

## In this guide

- [Install](#install)
- [Understand entities, identities and translations](#understand-entities-identities-and-translations)
- [Initial setup](#initial-setup)
- [Manage services and solutions](#manage-services-and-solutions)
- [Manage pages and sharing images](#manage-pages-and-sharing-images)
- [Manage news and blog posts](#manage-news-and-blog-posts)
- [People and standalone events](#people-and-standalone-events)
- [Preview and validate](#preview-and-validate)
- [Everyday maintenance](#everyday-maintenance)
- [Current scope and limitations](#current-scope-and-limitations)
- [Developer notes](#developer-notes)

## Install

Run Composer commands from your **Contao application root**, not from this package's directory. Back up the database before applying schema changes.

### Option A: install directly from GitHub

Register the repository and require the development version:

```sh
composer config repositories.schema-manager vcs https://github.com/vonheldenundgestalten/contao-schema-manager.git
composer require vonheldenundgestalten/contao-schema-manager:dev-main
```

This explicitly uses the GitHub repository; it does not depend on a Packagist release. If the repository requires authentication, configure Composer's normal GitHub access separately.

### Option B: develop with a local package folder

This is the setup used by the pilot. Put a checkout or copy of this repository at:

```text
YOUR-CONTAO-APP/
  composer.json
  private-bundles/
    vonheldenundgestalten/
      contao-schema-manager/
        composer.json
        src/
        contao/
```

Add the following entry to the application's `composer.json` → `repositories`, preserving its other repository entries:

```json
{
  "type": "path",
  "url": "private-bundles/vonheldenundgestalten/contao-schema-manager",
  "options": {
    "symlink": true,
    "versions": {
      "vonheldenundgestalten/contao-schema-manager": "dev-main"
    }
  }
}
```

Then run:

```sh
composer require vonheldenundgestalten/contao-schema-manager:dev-main
```

Use either the path repository or the GitHub repository for this package. A path installation uses the local files; pushing to GitHub alone does not deploy changes to it.

### Complete the Contao setup

1. Rebuild the application cache, for example with `php vendor/bin/contao-console cache:clear --env=prod`.
2. Review and apply database updates through your normal Contao Manager workflow, or run `php vendor/bin/contao-console contao:migrate` interactively. Review unrelated proposed changes separately.
3. Sign into Contao as an administrator. Open **Content → Structured data**.
4. Grant appropriate module/field access to other backend users through the normal Contao permissions if needed.

The bundle registers through Contao Manager automatically. It adds `tl_schema_entity`, `tl_schema_translation`, and schema-related fields on existing Contao tables. No frontend module or JSON-LD content element needs to be placed on each page.

When updating a local package, synchronize its files and rebuild the Contao cache. When updating a Composer-managed version, update the package with Composer. Review database changes whenever an update adds fields.

## Understand entities, identities and translations

An **entity** is a real thing: a company, person, service or event. A **page** describes that thing. They are related, but their identifiers serve different purposes.

| Item | Example | Purpose |
| --- | --- | --- |
| Identity origin | `https://www.example.com` | Permanent public domain used to generate identifiers. No page path. |
| Permanent entity ID | `https://www.example.com/#entity-<generated-id>` | Identifies the same entity across languages and page moves. Generated on first save. |
| English representative page | `/en/hosting.html` | Home for the English service description. |
| German representative page | `/de/hosting.html` | Home for the German service description. |

The generated suffix is random; it does not depend on the database record ID or schema type. **Identity origin is fixed after the first save.** Use the public production domain even when editing on a staging server. Do not enter the staging domain or the service page URL.

The manager uses Contao to resolve representative-page URLs. Staging previews can therefore contain staging page URLs while their permanent entity IDs retain the production origin. Check the actual output again after deployment.

### What is shared, and what is translated?

| Shared entity facts | Localized child record |
| --- | --- |
| Permanent identity and type | Representative page and derived language |
| Organization/person name and legal name | Description |
| Address, telephone, email and legal identifiers | Service/event display name |
| Related organization and official profile links | Person's job title |
| Logo or portrait; event dates and venue | Main-subject assignment and publication |

Company and person names are shared; you do not re-enter the legal company name for each language. A Service or Event can have a localized name, falling back to its shared name when empty.

Each entity can have **one localized home per language**. Several entities can use the same page—for example, several hosting packages described on one hosting page. News reader pages that require an item cannot be selected as these homes; news has its own integration.

## Initial setup

### 1. Create the company

Under **Content → Structured data**, choose **New**:

1. Select **Organization**, or **LocalBusiness** when that type genuinely describes the business.
2. Enter its shared **Name** and public **Identity origin**.
3. Add the legal name, public contact/address details and other applicable shared facts. Optional fields can remain empty.
4. Select an existing public logo and add official profile URLs, one per line.
5. Tick **Published** and save. Contao generates the permanent entity ID.

![Company identity settings with a shared name, production origin and generated permanent ID](docs/images/company.png)

*The origin is the company website's domain, not the page assigned to a translation.*

### 2. Give it a home in each language

Open the entity's **child elements** using the list/tree icon beside it, or **Save and edit child elements**. Create a child record:

1. Select the regular homepage or company/about page for the first language as **Representative page**.
2. Save to derive the language from that page's website root.
3. Add a description matching the visible page content.
4. Select **Main subject of this page** when the page is primarily about this company.
5. Publish and save the child record.

Repeat for the other languages. You are translating the same company, not creating separate companies.

Both the parent entity and its localized child must be published for the localized description to appear. The home page must also be publicly available. If an entity is published but has no available home in the current language, a reference can still include its shared facts; it does not borrow another language's description. Unpublished entities are omitted entirely.

### 3. Connect the website publisher

In **Site structure**, edit the primary language's **website root**. Under **Structured data**:

- Choose the company as **Website publisher**.
- Set the public **Website name** and, if appropriate, a genuine **Alternative website name**.
- Save to generate the website's permanent identity.

For another language of the same website, select the primary root in **Share website identity with**. This connects both languages to one WebSite identity. Leave that setting empty for a genuinely separate website. Keep the roots' publisher settings consistent; a shared website uses its primary root's publisher.

Usually leave **Website homepage override** empty. The website homepage is independent of the company's representative page.

![Language-root settings: publisher, website name and sharing the website identity with the German root](docs/images/website.png)

### 4. Set a fallback sharing image

On each language root, choose a **Default sharing image** under **Social sharing**. This is useful for pages without a suitable image of their own. Add an image description or use the file's localized metadata.

Enable **Manage social sharing metadata** if this extension should output Open Graph/Twitter cards. Have your integrator disable duplicate tags from other extensions or templates first. The VHUG pilot theme has a separate bridge for this; other themes need their own check.

The representative image also feeds Schema.org's `primaryImageOfPage`; enabling social tags is a separate choice.

## Manage services and solutions

Use a **Service** entity for a service you actually provide: website development, hosting, maintenance or a consulting solution. The extension currently has no separate Product editor.

1. Create a Service with its shared name and identity origin.
2. Select the company as **Related organization**. The output connects it as the service's `provider`.
3. Publish the entity and add a child record for each language.
4. Choose the page describing the service and enter its localized name and description.
5. Mark it as the page's main subject when appropriate, then publish and save.

A service can be the main subject of its detail page and also be relevant to other pages. On those other pages, select it under **Structured data → Related entities**. This adds a relationship using the existing identity; it does not create another service or move its home.

**Main subject** connects a page with what it primarily describes (`mainEntity`). **Related entities** connects subjects relevant to that page (`about`). Select only entities supported by the visible content.

### Optional: derive a priced service from existing content

The current adapter supports the VHUG **pricing** content element. This is an optional project integration, not a generic Contao price editor.

On a localized Service record:

1. Select a **Pricing element ID** from that service's home page.
2. Select the **Pricing row key**, such as “CMS Hosting”.
3. Save and open the preview.

The selected row supplies the localized name, description and price. This takes precedence over the manually entered localized name/description. Editors continue changing those values in the original pricing content element.

![CMS Hosting localized home with the existing pricing element and row selected](docs/images/service-home.png)

*The localized text fields are empty here because this example takes its text and price from the selected CMS Hosting row.*

Exact amounts become `price`; “from”/“ab” amounts become `minPrice`. Recognized monthly/yearly prices retain the billing unit. Unknown or quote-based amounts are not turned into invented numeric prices. Offers reference the Service and its seller.

After removing or reorganizing pricing rows, check the linked services. Row keys are references, not automatic identity tracking. An unavailable linked source suppresses that service's output rather than leaving an obsolete offer published.

## Manage pages and sharing images

Continue editing page titles, descriptions and content in their normal Contao screens. The extension reuses resolved page metadata, canonical URLs, language and core breadcrumbs.

### Choose the page's purpose

Edit a regular page in **Site structure → Structured data**:

| Page purpose | Typical use |
| --- | --- |
| `WebPage` | Default; suitable when no more specific type applies |
| `AboutPage` | About the organization or its work |
| `ContactPage` | Contact information |
| `CollectionPage` | An overview or collection, such as a blog index |
| `ProfilePage` | A page primarily describing a person or organization |
| `ItemPage` | A page primarily describing one item |

This changes the existing page node's type while preserving its other properties. The Service, Person or Article described on that page remains a separate linked entity. Selecting `ItemPage` does not create a Product.

### Pick a representative image

Under **Representative image**, use:

- **Automatic:** news reader image → marked content image → page picker → explicitly assigned terminal42 page image → language-root fallback.
- **Prefer page image:** the page picker/explicit terminal42 page image comes before the automatic sources.
- **No representative image:** omit the page/social image. An article's own image data remains intact.

![Contact page settings showing related company, ContactPage purpose and representative-image controls](docs/images/page.png)

For a page with a hero or main image, edit that content element and enable **This image represents the page**. The adapter uses standard Contao `singleSRC`/`addImage` fields, only when the element is rendered and enabled. The first qualifying marked image wins. Custom hero structures need an adapter; the manager does not guess from every image, slider or logo strip.

For pages with no suitable content image, use the page picker or the root fallback. File metadata supplies localized alternative text unless overridden. Existing ImageObject metadata is reused and connected to `primaryImageOfPage`.

JPG, PNG and WebP are supported. The root's **Social image format** either fits the complete image or crops to 1200 × 630 using Contao's important image area. Missing/unusable files fall through to the next candidate. The optional terminal42 integration uses explicitly assigned images; it does not emulate that extension's module-specific parent-image inheritance.

## Manage news and blog posts

News stays in **News**. Do not create a separate entity manually for every blog post.

### Configure the archive once

Edit the news archive and choose **Structured data → Article schema**:

| Setting | Behavior |
| --- | --- |
| `core` | Keep Contao's article output |
| `BlogPosting` | Enrich/retype articles as blog posts |
| `Article` | Use a general article type |
| `NewsArticle` | Enrich articles as news reporting |
| `suppress` | Hide this archive's core article nodes |

Choose its **Publisher** and save. The publisher is needed to generate permanent article IDs; saving the archive also fills missing IDs for its existing posts.

![News archive configured for BlogPosting with the company as publisher](docs/images/news-archive.png)

Enrichment replaces the matching core news node, retaining source content and unrelated graph nodes. Suppression only affects that archive's article output; it does not disable all structured data on the page.

### Connect authors once

Create a published **Person** entity for each public author. Then edit the corresponding backend user and choose **Structured data → Public author entity**. Backend account details and public author identity remain separate.

A person's localized home can be a profile/team page that actually describes them. Do not invent a profile URL: a person without a localized home can still provide a shared author identity.

### Edit posts as usual

The existing headline, publication date, article content and main image remain the source. Extra fields appear only for archives configured as `BlogPosting`, `Article` or `NewsArticle`:

- **Author entity:** optional override; otherwise the backend author's linked Person is used.
- **Content last revised:** set this for a real content revision, not an administrative save.
- **Permanent article ID:** generated automatically and retained when the URL/type changes.

![News editor with its regular title and author plus optional schema author and revision fields](docs/images/news.png)

Where terminal42 changelanguage supplies a `languageMain` relationship, translated news links to the original through `translationOfWork`. Shared company/person identities stay the same across languages; translated news records have their own article identities.

**Jobs stored in News are not supported as JobPosting yet.** Do not select NewsArticle just to make a job appear as a supported rich-result type; retain the project's existing job-schema solution until an adapter is available.

## People and standalone events

For a Person, put the name, portrait and actual related organization on the shared entity. Put the biography/description, job title and representative page on the localized children. The related organization becomes `worksFor`, so only set it when that relationship is accurate.

A **standalone Event** can use the entire homepage as its localized home. You do not need to create a Contao Calendar event. This is useful when a complete landing page represents one conference.

The current Event editor is basic: dates, venue name, status, organizer and localized text. Dates accept `YYYY-MM-DD` or `YYYY-MM-DDTHH:MM:SS+HH:MM`. It does not yet replace a complete custom event element with ticket offers, attendance modes, detailed venue addresses and other event-specific data.

## Preview and validate

Save a localized record, then expand **Published JSON-LD preview**. **Saved output** displays the full saved JSON with automatic height and line wrapping.

The preview contains that entity and its referenced organization. It is **not the complete page graph** and does not display unsaved edits. Unpublished entities are omitted.

<details>
<summary>Example: full saved output for CMS Hosting</summary>

![Expanded saved JSON output for CMS Hosting and its provider organization](docs/images/saved-output.png)

</details>

Check the rendered frontend too:

1. Visit the entity's home and another page referencing it, in each configured language.
2. Inspect the `application/ld+json` block using the `https://schema.org` context. Contao can also output a separate internal Page context; that is not a duplicate Schema.org page.
3. Confirm identities, names, localized home URLs, relationships and publication state.
4. Use the [Schema.org validator](https://validator.schema.org/) for vocabulary/graph validation and [Google Rich Results Test](https://search.google.com/test/rich-results) for Google's supported search features. A valid Service graph need not produce a dedicated Google rich-result preview.

A password-protected development URL cannot be fetched by public validators. Use their code input to test copied JSON/HTML, then validate the public deployment separately. Structured data must reflect the visible content; valid markup does not guarantee a particular search appearance. See [Google's structured-data guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies).

### Full organization or a compact reference?

**Current behavior:** when an organization is referenced as publisher, provider or another relationship, the manager includes its available full shared/localized data once in that page's graph. Relations point to its permanent `@id`. The company is edited centrally, but its rendered details currently repeat across pages.

A future compact-output policy could publish full details on the organization's localized home and a small identifying node elsewhere. That is a recommendation, **not an existing toggle or implemented behavior**. See [Organization output: guidance and proposed policy](docs/organization-output.md) for examples and the distinction between an ID reference and automatic data retrieval.

## Everyday maintenance

| When something changes | Where to edit |
| --- | --- |
| Company name, address or phone | Shared Organization/LocalBusiness record, once |
| Service wording in one language | Its localized child; for linked pricing, the source content row |
| A service moves to another page | Its representative-page selection; retain its permanent ID |
| Another page discusses a service/person | That page's Related entities |
| News headline, body or main image | The existing news record/content |
| A post's public author differs | The post's Author entity override |
| A post receives a substantive update | Its content and Content last revised field |
| A new language is added | Root setup and localized child records |
| Sharing image is unsuitable | Mark a suitable content image or choose a page override |
| A service is withdrawn | Unpublish the entity and update the visible content |

Preserve generated IDs when migrating or importing records. Removing and recreating an entity gives it a new identity; changing its home page does not.

### Troubleshooting

- **No localized output:** check parent publication, child publication, page publication/access and the page's derived language.
- **News fields are missing:** enable a supported enrichment type on the archive and save it first.
- **News keeps the old output:** confirm archive publisher and generated article IDs; save the archive to fill missing IDs.
- **Preview seems stale:** save first, then reopen the preview. Linked pricing content is the source when configured.
- **Duplicate schema or social tags:** check old HTML elements, theme templates and other extensions. The manager does not automatically remove handcrafted scripts or FAQ microdata.
- **Fields/module missing after an update:** review database updates, rebuild the application cache and check backend permissions.

## Current scope and limitations

Available now: Organization, LocalBusiness, Person, Service, basic standalone Event; localized homes; page purposes; website publisher/identity; shared people as news authors; archive-controlled article enrichment; representative/social images; saved previews; optional VHUG pricing and terminal42 page-image integrations.

Still pending: a full Event workflow, JobPosting replacement and fields, FAQ-checkbox adapters, Product-specific fields, generalized content adapters, language-home suggestions, a visual relationship/usage overview, and compact supporting-entity output. Existing HTML schemas need a deliberate migration per project.

## Developer notes

The bundle integrates with Contao's JsonLdManager/JsonLdEvent. It extends the existing graph rather than adding a separate handcrafted JSON-LD script for each entity. Standard news fields remain authoritative, and the image resolver serves both page schema and social metadata.

Run pure checks from the package directory:

```sh
php tests/mapper.php
php tests/prices.php
php tests/images.php
```

Run integration checks from a configured Contao application root, adjusting the package path for a `vendor/` installation:

```sh
php private-bundles/vonheldenundgestalten/contao-schema-manager/tests/dca.php
php private-bundles/vonheldenundgestalten/contao-schema-manager/tests/integration.php
php private-bundles/vonheldenundgestalten/contao-schema-manager/tests/news.php
php private-bundles/vonheldenundgestalten/contao-schema-manager/tests/page-metadata.php
```

The integration checks target the pilot fixtures: published DE/EN pages, a configured news archive/publisher/author and relevant image settings. Fixture database writes roll back. `SCHEMA_TEST_ORIGIN` sets the routing origin; `SCHEMA_TEST_DB_TCP=1` is an optional process-only workaround for jailed SSH without a local MySQL socket. Do not treat these as a universal fresh-install test suite.

See [development notes](docs/development.md) for the pilot's setup and verification history. Screenshot files in [docs/images](docs/images) were captured from the actual backend on 2026-10-02; credentials, browser sessions and capture helpers are not included.
