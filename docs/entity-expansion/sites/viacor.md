# VIACOR: products, systems, applications and evidence

[Planning index](../../entity-expansion-plan.md) · [Feedback VI01–VI06](../feedback/viacor.md)

## Observed evidence

| Source | Observation |
| --- | --- |
| [PORPLASTIC S6020P](https://www.viacor.de/en/products-sports-and-industrial-flooring-systems/porplastic-s6020p.html) | A named coating material with technical attributes, a data sheet and links to systems that use it. |
| [PORPLASTIC ACTIVE court](https://www.viacor.de/en/systems/sports-and-fun-floorings/porplastic-active-court.html) | A named flooring system with constituent products, thickness range, application categories, a system data sheet and a responsible contact. The three visible standard badges are DIN EN 14877, DIN EN 71-3 and DIN V 18032-2. The benefits text also mentions DIN 18035-6; that is not automatically a fourth certificate. |
| [Parking overview](https://www.viacor.de/en/systems/parken/overview-car-parking-flooring-systems.html) | A topic overview with sub-areas and system listings. Ramps is also linked as a section anchor; not every category necessarily has its own standalone page. |
| [Team](https://www.viacor.de/en/company/team.html) | Public people with differentiated responsibilities, including Head of Sales and the PORPLASTIC business-unit manager. Reuse the same person wherever displayed. The guessed `/en/team.html` redirects to the homepage and is not evidence of a separate team URL. |
| [Sto acquisition announcement](https://www.viacor.de/en/news/sto-acquires-shares-of-viacor-polymer-gmbh.html) | Historical evidence of group participation and independently operating product brands. It does not alone establish today's exact legal parent chain. |
| [Company certificates](https://www.viacor.de/en/downloadcenter/service.html) | Separate organization-level ISO certificate downloads exist. These are different from a system's performance claims. |

Sampled product/system/category pages emit a shared Organization with ID `https://www.viacor.de/#viacor`, alongside core metadata; no business Product/System node was observed there. Preserve that company ID if imported. Public HTML does not establish the product/system database tables or import process.

## Proposed general model

Both the material and named system can be **Product**, with different editor profiles. The system is not automatically ProductGroup: its constituents are not variants of one product. ProductModel is an alternative if VIACOR's record represents a specification/model, but does not solve composition by itself.

Use **material → Product references** on a system when the selected products genuinely become its constituent materials. This directly fits the coatings/granules case. Keep optional/accessory recommendations separate, using a suitable specific relation or isRelatedTo. Never use CreativeWork's `hasPart` as a generic product bill of materials.

If an actual sold bundle has fixed quantities, a distinct Offer can use includesObject/TypeAndQuantityNode. A recipe's consumption per square metre is not automatically a fixed sale bundle. Ordered layers, alternatives and coverage rates may need structured internal rows; emit only the parts with an honest standard mapping. Avoid maintaining a full costing or manufacturing system in this extension.

Represent Parking / Ramps as categories from the existing source, with localized labels and hierarchy. The public category page is CollectionPage + ItemList. A reusable DefinedTerm/CategoryCode can supply Product.category and Person.knowsAbout, but a topic must not become a Service just to be linkable.

## Certificates are not logos

Support a distinction between **documented conformity to a standard** and an **issued certificate**. Badge text alone does not identify a certifier, certificate number, issue/expiry date or certified configuration. Those fields must stay unknown until evidence is available.

A verified certification instance attaches to the exact system via hasCertification, references its issuer and evidence, and carries validity where known. An ISO certificate for the manufacturer remains on that organization. Do not copy a system certificate to every constituent product, every system in a category or the manufacturer. Retain standard names/versions exactly; translated explanations are separate. Expired evidence needs a review state, not silent renewal.

## People and corporate structure

A sales manager can knowAbout the Parking term and work for VIACOR. Responsibility for sales in that area is more specific than expertise; store a scoped contact assignment if needed. Product.contactPoint is not a valid universal shortcut, and a sales employee is not automatically the Product manufacturer or Service provider. Keep internal responsibility visible to editors without fabricating public properties.

Reuse Organization parent/subOrganization for the confirmed corporate chain. PORPLASTIC/VIASOL brands are not automatically subsidiaries. Confirm the immediate parent entity and official name before encoding StoCretec as a legal parent based on a reporting line.

**Ready:** company, manual people, employment, service offerings, news and preserved ID. **Add:** product measurements/material references, manufacturer/brand reuse (C06/C07). **Design:** source adapters, category ownership, certificate proof/scope and sales responsibilities (C08/C10/C12/C13). No automatic price extraction is proposed.
