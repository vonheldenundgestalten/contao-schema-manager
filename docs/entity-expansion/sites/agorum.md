# Agorum: preserve identities; distinguish software, learning and events

[Planning index](../../entity-expansion-plan.md) · [Feedback AG01–AG05](../feedback/agorum.md)

## Existing structured data is part of the contract

The [homepage](https://www.agorum.com/) and [agorum core pro](https://www.agorum.com/agorum-core/agorum-core-pro) were inspected as raw HTML, not only through search snippets. Observed identities include:

| Subject | Existing public ID to preserve |
| --- | --- |
| Organization | `https://www.agorum.com/#organization` |
| WebSite | `https://www.agorum.com/#website` |
| Homepage | `https://www.agorum.com/#webpage` |
| Software | `https://www.agorum.com/agorum-core/agorum-core-pro#software` |
| Software page | `https://www.agorum.com/agorum-core/agorum-core-pro#webpage` |

The software node also carries applicationSubCategory, browserRequirements, publisher, sameAs and an Offer with direct price/currency and a separate pricing-page URL. Some properties are not native 1.2.0 editor fields. Preserve the original offer shape and values during import review; do not flatten it into a new home-page offer or interpret the sampled price as a newly verified commercial promise.

The pages also contain FAQPage contributions, article nodes and Contao metadata. Those are not all standalone business entities. A company migration must not remove independently maintained FAQ data or the article graph. Choose an emitter owner per identity: externally maintained, explicitly imported, or source-managed. Merely changing @id in another place does not merge all emitters.

## Content distinctions found

| Evidence | Model implication |
| --- | --- |
| [Software page](https://www.agorum.com/agorum-core/agorum-core-pro) | SoftwareApplication already exists in 1.2.0; avoid creating an extra Product with another ID for the same software. Only use multiple types when both meanings are genuinely needed. |
| [Use cases](https://www.agorum.com/agorum-core/use-cases) and [AI consulting](https://www.agorum.com/ki-beratung) | An application scenario or industry landing page is not automatically a separate software product or sold Service. Consulting offered to clients is a Service. |
| [Case studies](https://www.agorum.com/case-studies) | Editorial evidence about a client's use of software, connected to the existing software and services. Distinguish the named client from the provider/publisher. |
| [Webinar overview](https://www.agorum.com/webinare) | Primarily recordings/recaps in the sampled list, not simply a list of future live Events. |
| [Campus episode 4 recap](https://www.agorum.com/webinare/ki-wissensdatenbank-im-unternehmen-agorum-campus-folge-4) | Named article author, a different presenter and a past live date; useful counterexample to treating all people and dates alike. |
| [Academy](https://www.agorum.com/agorum-academy) | Actual named training offerings; course identity, scheduled delivery and video lessons can be separate things. |
| [Partner section](https://www.agorum.com/fuer-partner/business-partner) | Commercial partners are not subsidiaries or membership organizations by default. |

## Recommended general approach

Keep **software**, **consulting/service**, **editorial content**, **scheduled occurrence** and **recording** distinct. Reuse identities through real relationships:

- A future webinar occurrence is Event (possibly EducationEvent when educational), with public VirtualLocation and correct timezone. A recording is VideoObject with real upload date, thumbnail and content/embed URL when available.
- Link an actual recording to its event using recordedAt/recordedIn. A prerecorded presentation with no real scheduled occurrence does not need a fake past Event. A simulive session needs an explicit occurrence and truthful recording metadata.
- A recap remains Article/BlogPosting chosen by its archive. Its author can differ from its presenter. Link the software as its subject and the relevant video without replacing the article.
- A reusable training offering may be Course; individual deliveries may be CourseInstance. Do not force all webinars into Course or build a learning-management system here.
- A trade fair organized by a third party remains that organizer's event. Agorum's booth, participation or own session is not proof that Agorum organizes the fair. Record an internal participation relation unless a justified public event/subevent mapping is agreed.

No broad source scan should create organizations for every customer logo, products for every navigation topic, or people for every quote. Source eligibility and import preservation matter more on this large site than the number of generated entities.

**Ready:** SoftwareApplication, Service, organization, archive types, author subjects, retained imported properties (C01–C03/C20). **Design:** richer software relationships and field ownership, source binding, VideoObject/course scope and correct event/recording distinction (C13/C18–C20). The separate calendar branch remains the base for real Calendar events; it is not part of 1.2.0.
