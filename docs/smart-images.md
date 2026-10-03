# Experimental smart images and social metadata

This branch preserves the pilot image implementation on top of the reduced 5.7.0 core. It contains no pricing-source adapter. The restored resolver/social bridge is a starting point, not the completed generic design. Database updates for the additional controls are required when testing this branch.

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
