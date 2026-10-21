# VHUG agency: the small-site baseline still needs to stay simple

[Planning index](../../entity-expansion-plan.md) · [Feedback VH01–VH04](../feedback/vhug.md)

## Evidence

| Source | Finding |
| --- | --- |
| [Agency homepage](https://www.vonheldenundgestalten.de/) | Handwritten Organization with ID `https://www.vonheldenundgestalten.de/#organization`, contact data and nested expertise catalogues. Award text also includes a partner status, which should not automatically become an award record. |
| [Brand Communication](https://www.vonheldenundgestalten.de/expertise/brand-communication.html) | An offered service area, related work and a business contact. This is a useful Service home, with more specific offerings where their public content justifies them. |
| [AMG employer-branding case](https://www.vonheldenundgestalten.de/cases/employer-branding-mercedes-amg-automotive.html) | A project story with multiple areas of work and deliverables. Do not mint a new Service for each campaign output or infer the sales contact as author. |
| [Leadership announcement](https://www.vonheldenundgestalten.de/blog/breaking-news-wir-verstaerken-unsere-geschaeftsleitung.html) | Named author and a different person who is the subject. Shows why author, about and contact roles must remain separate. |
| [Creative Director job](https://www.vonheldenundgestalten.de/job/creative-director-konzeption-storytelling.html) | Real role, location, employment type, date and application contact. Fits the existing News-based JobPosting approach. |

The sampled case/job/blog pages contained no JSON-LD scripts in the raw response; that is not an assertion that they have no microdata or other structured markup. No production correction was made. Preserve the existing organization ID and inspect all emitters before any future import.

## Generalized handling

Continue with Organization, Service and Person as the core. Reuse existing article subject selectors. Configure separate archives for ordinary news, blog, cases and jobs as appropriate, with archive-level types. No content-specific type override is proposed. An extension launch or agency announcement can be NewsArticle/Article according to its archive; specificity is an editorial decision, not a reason to force everything into BlogPosting.

For cases, Article.about can reference the relevant Service(s), a deliberately managed public client Organization and a genuine product/creative work if one needs independent identity. The case itself is not the Service. Do not add Schema.org `client` or `customer` to Article. Project participation and responsibilities may be internal relations where there is no honest exact mapping.

The agency's organization and VHUG Technologies must not be merged because they share team, naming or links. Employment, service provider and site publisher are explicit. A person can be a named author and have separate business-contact duties without those roles being interchangeable.

News-based JobPosting already handles much of the job example. Reusable job locations could later use the same Place relation infrastructure; preserve expiry and active-publication rules. Do not invent a closing date, salary, fully remote status or `directApply` merely from an email/application link.

The existing OfferCatalog organization structure deserves preservation during migration. An expertise landing page can be a Service and have subservices when those are real offerings; it need not be an arbitrary new specialized type. Keep the editor compact for a client that only needs this familiar set.

**Ready:** most of the business/service/article/job workflow (C01–C03). **Design:** source-based content homes, responsibility distinctions, reused places and exact preservation of existing emitter ownership (C12/C13/C20). This site is the acceptance test that the bigger model has not made a normal agency installation cumbersome.
