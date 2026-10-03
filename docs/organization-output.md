# Organization output

## Implemented behavior

Organization and LocalBusiness records emit their complete available data on their published representative page for the current language. Elsewhere, they emit `@type`, `@id`, `name`, localized home `url` when available, and `logo` when configured. Identity and type are retained; unpublished organizations are omitted as before. No other-language home is invented when the current language has none. Explicit external organization references can instead use their configured external website URL.

This selection happens while the manager builds its own entity nodes, before later graph listeners. It does not sweep the final graph or strip unrelated extensions' organization nodes. Other integrations can still enrich the result afterwards. A complete supporting-company preview remains available in the backend: the saved entity preview is not a full frontend-page preview.

The change applies to Organization and LocalBusiness only. Services, people and events retain their existing representation.

An explicit **Location overview with contact details** page setting retains address, telephone, email and parentOrganization on supporting LocalBusiness nodes when the page visibly lists those offices. Full organization relationships (locations, subsidiaries, networks and service catalogues) are followed only on the localized home or in a backend preview, avoiding unrelated catalogue trees on every page.

## What the sources say

Google recommends putting Organization markup on the homepage or another page describing the organization, such as an about page; repeating it on every page is unnecessary. This is guidance about placement, not a prohibition on a company node serving as a service provider or article publisher. [Google Organization documentation](https://developers.google.com/search/docs/appearance/structured-data/organization).

JSON-LD permits a node reference containing only `@id`. Matching IDs identify the same node; they are not an instruction to fetch another page and import all its properties. Do not assume a page-level consumer will retrieve missing company details automatically. [W3C JSON-LD 1.1, Node Identifiers](https://www.w3.org/TR/json-ld11/#node-identifiers).

## Output policy

The manager now follows this default:

1. **Localized company home:** emit the full relevant organization description, legal/contact facts, logo and verified profile links.
2. **Other pages referring to it:** include a compact identifying organization node and link relationships to it by `@id`.

For future feature-specific integrations, retain any additional organization properties needed by that consumer through the relevant integration. The compact representation is not a universal guarantee of rich-result eligibility.

The compact node should normally include `@type`, `@id`, `name` and the representative `url` where available. A logo can be retained where useful, particularly in a publishing context. The actual type should remain consistent—for example, do not silently replace an existing LocalBusiness type with a less specific one.

This is an engineering recommendation based on the guidance above, not a separate Google requirement. It reduces repeated detail while leaving enough local context to identify the referenced organization. There is no claim that a shorter graph improves rankings.

## Example: service page

This illustrative graph uses shortened example IDs for readability. It illustrates the implemented compact representation, with no logo configured:

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

If no published home exists in the current language, omit the unavailable localized URL rather than inventing one. A configured External organization website is an explicit reference for external organizations, not an automatic cross-language fallback.

## Verification

Integration tests cover full DE/EN homes, compact supporting references, encounter-order independence, LocalBusiness type preservation, unpublished/missing homes and complete standalone previews. The live pilot was checked across 20 language, service and blog routes. The same organization ID and localized home URLs are retained.
