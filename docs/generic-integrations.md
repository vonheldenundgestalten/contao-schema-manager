# Generic content contributions and image/social metadata

**Status: refined design, not an implemented replacement for the pilot adapters.** Native Offer contributions have been verified on Contao 5.7 through both Twig and legacy PHP template APIs. The organization output refinement is already implemented separately.

## Pricing: use Contao's graph as the contribution path

The content element/controller owns the price because it owns the visible offer. Schema Manager owns the reusable entity, its identity and localized home. There should be no knowledge of VHUG's `pricing` field, serialized rows or display-text price parser in the generic package.

Contao already provides `add_schema_org()` in Twig and `$this->addSchemaOrg()` in legacy templates. Its implementation creates a node and calls the graph's `set()` with the supplied `identifier`. It does not merge arbitrary properties into a pre-existing entity just because `@id` matches. Emit an Offer with its own identity and `itemOffered` pointing at the existing Service instead. [Contao documentation](https://docs.contao.org/5.x/dev/reference/twig/functions/add_schema_org/).

### A small, standard contribution

The following uses Contao's existing function. The controller supplies the variables from its record and the selected managed entity; these are not proposed new Twig functions:

```twig
{% do add_schema_org({
    '@type': 'Offer',
    '@id': offerId,
    'identifier': offerId,
    'itemOffered': {'@id': entityId},
    'price': price,
    'priceCurrency': currency,
    'url': offerPageUrl
}) %}
```

`identifier` selects the Contao graph slot, while `@id` is the public identity. Supply both consistently. A price card must not emit another partial Service under the managed Service's slot: that risks replacement instead of enrichment. Controllers should build the data; templates only hand it to Contao.

The proof in `tests/contao-offers.php` verifies that two distinct native offers coexist beside one Service, retain `itemOffered`, and update the same offer when re-emitted with its identifier. It does not claim that the future manager-side discovery or backend binding is implemented.

### Editor workflow and ownership

On a participating element/row, offer a **Describes entity** picker and a publish-contribution choice. For a single-price element this can be an element-level selection; a multi-price element needs a selection on each row. A generic extension cannot inject a row control into every third-party widget: the owning bundle supplies that small integration.

The public extension API should resolve the selected record to a published entity reference and collect its cache dependencies. The picker stores a record relationship, not a manually copied URL. Persist an Offer identity separately from the row position; reordering rows must not change identity or attach a price to a different service.

Keep the source's visible title/text authoritative for the Offer. Linking a price must not silently replace the Service's own name or description. If a project wants that text binding, make it an explicit adapter setting.

For an ordinary Contao installation without a compatible pricing element, provide a generic Offer record/content integration with structured amount, currency, billing period and linked entity. It should be usable as the source for both visible price rendering and schema, not force two independent prices to be kept in sync. This is the fallback path; it is not necessary when existing content can emit its own Offer.

### Manager-side linking, not unrestricted merging

At final graph assembly, collect Offer contributions whose `itemOffered.@id` belongs to a published managed entity. Emit that entity if required, and add the Offer reference to its `offers` collection. Merge by stable Offer identity without overwriting existing manual/third-party offers. Multiple offers per service are supported. Keep unrelated external offers untouched.

An Offer-to-Service reference is already meaningful JSON-LD without the reverse link; the reverse link makes navigation and validation easier. Only sources actually contributing to the current response should supply prices. A cached or off-page price must not be carried from a previous render. Merely referencing a Service elsewhere should not automatically reproduce all prices from its home page.

A scheduled, hidden, protected, removed or unpublished source must not leave a stale contribution. Providers must participate in Contao's cache dependencies; fragment-cache hits must preserve/replay metadata contributions just as cold renders do. Treat this as an acceptance test, not an assumption that a template call always executes.

Amounts should come from structured source data where possible. Price-specification types handle recurrence, minimum prices and tax information when actually known. Keep parsing of formatted phrases such as “from 10 €/month” inside the VHUG adapter, not in the generic manager.

The backend should explain **source → linked entity → supplied offer**, link back to the source editor, and report broken bindings. A saved-entity preview cannot promise to show every render-time contribution: distinguish it from a preview of a real page response.

### Migration

Move VHUG row decoding, price parsing and source controls into `vhugtech-bundle`. Migrate existing bindings, preserving entity/Offer IDs and localized assignments. Introduce persistent row keys where required. Turn off the old path only after fixture and live comparisons prove there are no duplicate/missing offers. Retain existing source fields through the migration rather than deleting them first.

## Images: shared sources, separate purposes

A photograph representing a page and a branded image used when sharing it are related but not interchangeable. A company logo or generic social fallback should not automatically become an article's content image. Build one metadata subsystem with two outputs:

- **Representative image:** a meaningful image of the page's subject, linked through `primaryImageOfPage`. Existing Article.image or other entity images keep their own semantics.
- **Social card:** an image and metadata designed for a link preview, optionally using that representative source, but allowing dedicated artwork and different renditions.

Ship this as a supported module of the extension initially, with an independent resolver API and optional tag output. That avoids maintaining two resolution implementations and does not force Schema Manager to own social tags on sites with an existing solution. It could become a separate reusable bundle once the API has been proven across projects.

### Editorial interface

Keep the common case simple. A page/reader record gets a **Preview and sharing** panel:

- Representative image: **Automatic / Select image / None**.
- Social card: **Automatic / Select image / No image**; social-tag ownership is a separate root-level setting.
- Optional social title/description overrides; otherwise use the resolved page/reader metadata.
- Live result preview with source, crop, fallback reason and a link to edit the originating record.

Preserve the existing page picker as an automatic fallback during migration; do not silently reinterpret it as an override. The advanced panel can expose an explicit fallback versus override choice. Provide reader-record controls so a shared news-reader page does not impose one card on every article.

At each language root, configure social defaults and rendition presets. A section may explicitly opt into a default for descendants, but an arbitrary parent's hero must not silently become its children's image. Cross-language fallback is opt-in, especially for artwork containing text.

### Deterministic source selection

| Context | Proposed automatic behavior |
| --- | --- |
| News detail | Actual current news main image; it wins over generic reader-layout images |
| Regular page with a marked representative image | Use the explicitly marked rendered image |
| Page with several unrelated images | Use an assigned fallback or report that a choice is needed; do not guess from the first ImageObject |
| Page with explicit page/terminal42 image | Use the configured assignment according to fallback/override mode |
| No subject image, but root social artwork exists | Use artwork for the social card; leave primaryImageOfPage absent unless explicitly suitable |
| Page with a custom social card | Keep the representative image; use custom artwork for social only |
| Multiple equally eligible explicit candidates | Resolve deterministically and display the ambiguity in the editor |
| Missing or unsuitable file | Explain rejection, then try the next permitted source |

Sources should report file/figure, role, owner, language, metadata, priority and cache dependencies. Built-in providers cover Contao pages, current news records, and explicitly marked standard content images. A small registered provider handles custom heroes; an optional provider handles terminal42 pageimage using its supported behavior. The resolver must not read project-specific database fields directly.

Existing JSON-LD image relations can be evidence when they explicitly identify the current subject's image. A bare ImageObject only says an image exists; it does not establish that the image represents the page.

### Rendering and social tags

Use Contao's image Studio and configurable image sizes/presets, preserving focal/important areas and metadata. Do not bake platform dimensions into several listeners. Generate crawler-compatible public file URLs with actual MIME type, dimensions and localized alt text. Format support and file-size rules belong in the rendition policy; an AVIF-only image source, for example, may need a broadly compatible social derivative.

Offer fit/crop controls. Do not silently crop important text or upscale tiny artwork into an apparently suitable card. Keep a deterministic fallback and an editor-visible explanation if the selected file cannot meet a rendition's requirements. A simple wide social preset is a default, not a claim that every platform displays the same crop.

A single social presenter owns Open Graph and X/Twitter metadata when enabled, including title, description, canonical URL, appropriate page/article type, site name, locale and image data. Open Graph supports image dimensions, MIME type and alternative text; emit the properties with the correct image. [Open Graph protocol](https://ogp.me/).

Root-level ownership modes should be **managed here** or **external**. External mode still exposes resolved metadata for another integration; it does not add duplicate tags. Managed mode requires a one-time removal/disablement of competing template output. Do not parse and rewrite arbitrary finished HTML to conceal conflicts. Report duplicate tags in diagnostics.

Content discovery must finish before the head is serialized. Contao's modern layout defers its head; JSON-LD assembly occurs separately. Keep one tested integration boundary for each supported legacy/modern rendering path. The current response-context wrapper is a pilot compatibility layer, not an API that every provider should copy. Resolving only at JsonLdEvent is too late to guarantee social-head output. Prove the replacement lifecycle with core templates and the pilot's custom layout before removing that bridge.

### Cache and maintenance

Cache by the actual document/reader record and language, source revision, rendition configuration and relevant website origin—not just the shared reader-page ID. Tag entity, file, source content, page, root/section settings and image-size dependencies. Image replacement, focal-area changes, publication changes and social overrides must invalidate the appropriate output. Use stable versioned derivative URLs; platform-side social caches can still require re-scraping and cannot be invalidated by Contao alone.

Run the same resolver for the backend's page-preview request and the frontend, with public-view visibility rules. Do not maintain a separate approximation for editor previews. Keep source providers, selection policy, image generation and tag presentation individually testable.

## Acceptance before replacing the pilot implementation

1. Ordinary Contao installation with no VHUG code; existing VHUG installation after migration.
2. Legacy and Twig layouts; cold response, cached page and cached fragment paths.
3. Two news articles sharing one reader page, in DE and EN, without image/price leakage.
4. Page with one marked hero, many images, no images, explicit fallback, override and disabled representative image.
5. Separate social artwork, inherited section/root defaults and localized text artwork.
6. Replaced/missing images, portrait/text-heavy/small sources, focal-area changes and regenerated variants.
7. Existing external social-tag owner versus managed output; exactly one intended set of tags.
8. Multiple native Offer contributors, reordering/deleting rows, publication/access scheduling, and unchanged entity/offer identities after migration.

The delivery order is: generic Offer reference/contribution API and migration; unified image metadata model and lifecycle tests; providers and editor diagnostics; social presenter and migration; then removal of pilot-specific code. Each step must preserve useful current behavior rather than introducing a second competing path.
