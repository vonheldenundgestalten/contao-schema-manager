# Experimental product and pricing contributions

This branch preserves the VHUG pilot adapter and the native Contao Offer proof on top of the manual 5.7.0 core. There is no smart-image/social implementation here. The existing source adapter still applies to Service rows; generalizing it to reusable Product/Service contributions is future work, following the plan below. Database updates are required for source bindings on a fresh installation.

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

