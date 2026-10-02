# Organization output: guidance and proposed policy

This note distinguishes current behavior from a possible refinement. It does not describe a configuration option that already exists.

## What the extension does today

EntityGraph emits the available full representation of a referenced entity, once per page graph. An organization's shared facts and current-language description/home can therefore appear on many pages. Service.provider, WebPage.publisher, article.publisher and other relationships use its permanent `@id`.

This preserves identity and avoids duplicate organization nodes within one graph. It does not yet limit the full company description to its home.

## What the sources say

Google recommends putting Organization markup on the homepage or another page describing the organization, such as an about page; repeating it on every page is unnecessary. This is guidance about placement, not a prohibition on a company node serving as a service provider or article publisher. [Google Organization documentation](https://developers.google.com/search/docs/appearance/structured-data/organization).

JSON-LD permits a node reference containing only `@id`. Matching IDs identify the same node; they are not an instruction to fetch another page and import all its properties. Do not assume a page-level consumer will retrieve missing company details automatically. [W3C JSON-LD 1.1, Node Identifiers](https://www.w3.org/TR/json-ld11/#node-identifiers).

## Recommended refinement

For this manager, a sensible default would be:

1. **Localized company home:** emit the full relevant organization description, legal/contact facts, logo and verified profile links.
2. **Other pages referring to it:** include a compact identifying organization node and link relationships to it by `@id`.
3. **Feature-specific requirements:** retain any additional organization properties needed by the consuming feature on that page. A fixed four-property stub is not a universal guarantee of rich-result eligibility.

The compact node should normally include `@type`, `@id`, `name` and the representative `url` where available. A logo can be retained where useful, particularly in a publishing context. The actual type should remain consistent—for example, do not silently replace an existing LocalBusiness type with a less specific one.

This is an engineering recommendation based on the guidance above, not a separate Google requirement. It reduces repeated detail while leaving enough local context to identify the referenced organization. There is no claim that a shorter graph improves rankings.

## Example: service page

This illustrative graph uses shortened example IDs for readability. It shows the proposed compact representation, not an exact current export:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://www.example.com/#entity-company",
      "name": "Example GmbH",
      "url": "https://www.example.com/en/"
    },
    {
      "@type": "Service",
      "@id": "https://www.example.com/#entity-hosting",
      "name": "Managed hosting",
      "url": "https://www.example.com/en/hosting.html",
      "provider": {
        "@id": "https://www.example.com/#entity-company"
      }
    }
  ]
}
```

The Organization is defined once in this document; all relevant relationships can refer to it. A bare `provider: {"@id": "…"}` is also valid JSON-LD, but it offers less context to a consumer looking only at this page.

## Languages and stable identity

Use the same organization ID and shared name/legal facts in every language. Its `url` can point to the home in the current language. The full description belongs on each available localized home; “one home” means one home per language, not one privileged language for the whole website.

Do not change the ID to the current service-page URL. An entity's permanent identity and the URL of a page describing it remain separate.

If no published home exists in the current language, omit the unavailable localized URL rather than inventing one. If a future implementation supports deliberate cross-language fallback, it should be explicit.

## Implementation considerations

A future change should distinguish entities directly described by the current page from supporting entities such as the provider or publisher. A direct/full representation must win if an entity is first encountered as a compact reference and later as the page's subject. Deduplicate by `@id`, preserve type and relationships, respect publication rules, and test both language homes and news readers.

Keep the saved entity preview useful as a full record preview, and label any future page-specific output preview separately. This output refinement should not add another set of company fields for editors to maintain.
