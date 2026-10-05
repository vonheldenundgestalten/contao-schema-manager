# Woodmark: a group is not an address list

[Planning index](../../entity-expansion-plan.md) · [Feedback WO01–WO04](../feedback/woodmark.md)

## Evidence

- The [service portfolio](https://www.woodmark.de/en/data-ai/services/strategy-process-consulting) describes offered consulting work; Service is a reasonable baseline.
- [Company information](https://www.woodmark.de/en/about-us) separates leadership, awards, certificates, partners and customers. Those sections should not all become memberships or employment relations.
- The [contact page](https://www.woodmark.de/en/contact) presents German offices. Its “subsidiary” label alone is not a separate legal-company proof.
- The [May 2026 announcement](https://www.woodmark.de/de/news/details/woodmark-group-gruendet-neue-tochtergesellschaft-in-tunesien-und-staerkt-internationale-wachstumsstrategie) explicitly names Woodmark Consulting Tunesia sarl., Woodmark Group GmbH and the new Tunis operation. Spain and France are user-supplied expansion plans; their legal entities/addresses were not established in this review.
- The [cases library](https://www.woodmark.de/en/cases) lists many sector/technology examples. A [route-optimization case](https://www.woodmark.de/en/cases/cases-detail/route-optimisation-logistics-aws-sagemaker-transport) describes client context, project work, results and a sales contact. Case storage in News is user-confirmed.

The raw homepage response redirected to German in our direct fetch, while the web index selected English. Plan explicit localized URLs rather than inferring one canonical home from the root redirect. The German response contained handwritten WebSite and Organization nodes without stable @ids; the latter's url was misspelled `https://www.woomdark.de/`. This is a migration-review finding, not a correction made here. Correcting a bad URL and preserving an established identity are different operations.

## Reusable structure

Use Organization for the confirmed group and legal subsidiaries, with explicit parent/subOrganization links. Model a local operating branch as LocalBusiness when appropriate, or its physical office as Place. An office opening does not necessarily establish a new company. A regional service is not automatically a duplicate Service entity merely because it appears on another country site.

Service provider should identify the entity actually offering the service. A shared group-level portfolio can link subsidiary offerings where justified. Per-market contact points and language-specific copy can vary while the underlying service identity remains stable; genuinely different contractual offers may need separate offers.

Keep cases as archive-controlled **Article**, not a fabricated CaseStudy type. Existing about/mentions cover services, public clients and technologies. A `Report` profile could be optional for formal reports, but there is no need to force all cases into that subtype. A case's named business contact is not automatically its author or the project's implementer.

An anonymized case remains useful without a client Organization. Do not create placeholder client entities such as “Logistics Company” if they are merely a description. A public named client can be linked after editorial review; a mention is not proof of a continuing commercial relationship. Schema.org's `customer` belongs to Order/Invoice, not generic Article/Organization.

## What this tests across clients

**Ready:** organization hierarchy, basic offices, services, manual public people and source-owned News cases (C01–C03). **Design:** multi-site same-language homes, regional/provider distinctions, source-bound case homes, relationship roles and group migration (C12–C14/C20). This is the same structural problem as Diakonie's operator/site split without the medical vocabulary.

Do not derive headquarters, direct legal parents, headcounts or service responsibility for the new countries from plans alone. The feedback file asks for the actual legal/entity diagram rather than trying to infer it from brand navigation.
