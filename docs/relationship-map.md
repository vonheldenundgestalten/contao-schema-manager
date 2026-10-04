# Entity relationship map (feature branch)

Branch: `codex/feature-relationship-graph`. No release tag. No database changes or changes to frontend schema output.

## Library choice

[Cytoscape.js](https://js.cytoscape.org/) provides graph analysis, directed edges, selectable nodes and disconnected components without a framework dependency. We use its [fCoSE layout plugin](https://github.com/iVis-at-Bilkent/cytoscape.js-fcose) for readable spacing. [vis-network](https://visjs.github.io/vis-network/docs/) was another suitable option; Cytoscape's graph traversal/connected-component API fits the missing-relationship workflow. [Sigma](https://www.sigmajs.org/docs/) targets large WebGL graphs and brings Graphology; that is unnecessary for an editorial entity map.

The exact releases, MIT licenses and SHA-256 hashes are in `public/vendor/*/README.md`. Assets are served by Contao locally, with no runtime CDN, telemetry, or transfer of graph data to a third party. Updating the libraries is a deliberate vendor update followed by browser verification; Composer consumers do not need Node or an npm build.

## What it shows

- Every saved managed Organization, LocalBusiness, Person, Service, Product and Event, including unpublished records.
- Parent organization, employer, provider, organizer, configured offer seller, memberships, workplaces, explicit/inferred office and subsidiary relationships, and service-catalogue links.
- Relationships point from the subject to the related entity. Reciprocal parent/location links are intentional; exact duplicates are collapsed.
- Full name and type, with street and postal locality for LocalBusiness. Entity identity and localized home assignments appear only when selected.
- Orange borders for entities with zero entity relationships, dashed borders for drafts and red placeholders for missing referenced records. Unconnected entities are not automatically errors. Separate connected groups are also retained.

This is an **editorial map of saved configuration**, not a crawler or a claim that every configured edge is currently published. For example `offers.seller` is the configured seller; an actual offer also requires a valid translated offer/home. Catalogue entries, drafts and language-specific data remain subject to the existing frontend output rules. Use the saved output preview and actual page JSON-LD to verify publication.

Page assignments are detail metadata rather than edges, so sharing a homepage cannot conceal a missing business connection. The map does not yet expand News-generated Article/JobPosting nodes, WebPage/WebSite nodes, inline Offer/ContactPoint/ImageObject/value objects or arbitrary JSON-LD added by other bundles. Those can be added as separate layers later without overwhelming the business-entity overview. It does not infer links from matching names or sameAs URLs.

## Backend use

Open **Content → Structured data → Entity relationships**. Drag nodes, zoom and pan; select one to label its relationships and see details. The entity selector and relationship buttons provide a keyboard alternative to the canvas. Search highlights matches without deleting other records. **Unconnected entities** highlights nodes with no entity edges. **Fit all** restores the full framing; **Reset view** clears selection and filters.

The feature uses the existing Structured data module permission. Page titles and page URLs are not queried; page access remains controlled by Contao page mounts. The view has no write API. Editing opens the existing entity editor and its normal permission checks.

## Verification

Run `composer test` for mapping checks (cycles, directions, inferred/explicit deduplication, disconnected records, missing targets, location labels, empty datasets). With Playwright and Chromium installed, run `python3 tests/browser-relationship-map.py` for offline rendering, search, selection, draft/missing/orphan styling, hostile label handling, mobile width and Turbo lifecycle checks.

Real backend testing on the pilot verifies the list action, local assets, authenticated rendering, entity selection and edit navigation. No entity relationships are changed by these checks.
