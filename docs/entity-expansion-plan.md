# Entity expansion: research and planning

**Round 2 · 5 October 2026 · Planning in progress · No implementation approved**

Branch: `codex/feature-entity-expansion`. Baseline: released **1.2.0**, commit `b7477d9`. Calendar work remains separately on `codex/feature-calendar-events` (`689fc55`). The earlier parked outline is superseded by this research pack. No version is assigned to the expansion.

## Current round

Start with the [Round 2 proposal](entity-expansion/round-2.md), the [100-use-case catalogue](entity-expansion/use-cases-100.md), and [Round 2 feedback](entity-expansion/feedback/01-round-2.md). Your original shared-decision answers are preserved. Round 2 supersedes the narrow catalogue, optional course/standalone-video scope and import redesign proposed below. The first-round research remains useful evidence, not a frozen scope.

## Initial recommendation and research context

Build one coordinated expansion around a small set of reusable capabilities, rather than a separate implementation for every client's vocabulary. Editors should choose understandable profiles such as **House model**, **Show house**, **Medical department**, **Product system** or **Award result**. Profiles choose appropriate types and field groups; they are not all new Schema.org types.

The important improvement is the ability to distinguish and connect **organizations, places, offerings, people and content**. Awards and certificates add evidence about those things. Category pages help people navigate them. None of these roles should be inferred solely from a page's position or a mention in an article.

Keep ordinary editing in Contao: archive defaults for news/jobs/cases, event defaults on calendars, source data on the original record. The Schema Manager owns shared identities, extra semantic facts and reusable relationships. It must not become a second CMS or a universal ontology editor.

## Read and respond

| File | Purpose |
| --- | --- |
| [Capability matrix](entity-expansion/capability-matrix.md) | What 1.2.0 already solves, bounded additions, and difficult changes; cross-references all clients |
| [Architecture and editorial flow](entity-expansion/architecture.md) | Proposed ownership, field groups, source adapters, homes, localization and graph output |
| [Vocabulary decisions](entity-expansion/vocabulary.md) | Exact relationship semantics, including where Schema.org has no exact equivalent |
| [Acceptance scenarios](entity-expansion/acceptance.md) | Concrete checks a future implementation must satisfy; no test code yet |
| [Research coverage](entity-expansion/sources.md) | Public URLs inspected, redirects, observed JSON-LD and limits of the research |
| [Shared feedback](entity-expansion/feedback/00-shared-decisions.md) | Recommended decisions, alternatives and answer slots for the next round |

| Project | Findings | Feedback |
| --- | --- | --- |
| OKAL | [Houses, awards, locations, advisers, stories and events](entity-expansion/sites/okal.md) | [Answer here](entity-expansion/feedback/okal.md) |
| VIACOR | [Materials, systems, categories, certificates, people and group](entity-expansion/sites/viacor.md) | [Answer here](entity-expansion/feedback/viacor.md) |
| Diakonie | [Hospital, departments, centres, MVZ, medical information and STARS](entity-expansion/sites/diakonie.md) | [Answer here](entity-expansion/feedback/diakonie.md) |
| Woodmark | [Group, subsidiaries, offices, services and cases](entity-expansion/sites/woodmark.md) | [Answer here](entity-expansion/feedback/woodmark.md) |
| Agorum | [Existing identities, software, webinars, learning and cases](entity-expansion/sites/agorum.md) | [Answer here](entity-expansion/feedback/agorum.md) |
| VHUG agency | [Services, cases, news, people and jobs](entity-expansion/sites/vhug.md) | [Answer here](entity-expansion/feedback/vhug.md) |

Fill the **Response** fields in the feedback files, or give feedback using their IDs. An unanswered decision remains open, not approved. Keep resolved answers in place with their decision date so later rounds do not lose context.

## Findings that shape the design

1. **The model and the place are different identities.** OKAL's Black Label 95 is a reusable house design. Arnsberg is a visitable built house. A sales office can be LocalBusiness, while a venue need only be Place. A showroom is not automatically a shop, museum or EventVenue.
2. **Awards need result records, not a made-up Award type.** The 2024 OKAL article reports different placements for two houses. A result needs an exact recipient, year, category and distinction; the article can cover several results. Existing company award text must survive.
3. **A system is not a category or a product variant group.** VIACOR's system combines materials. Parking and Ramps are navigation/application categories. A certificate belongs to its actual certified subject; it must not propagate to all components.
4. **Organizations and geography have separate hierarchies.** Diakonie's hospital departments, interdisciplinary centres, separately operated MVZ and cooperation partners cannot be flattened into locations. Woodmark's subsidiary is not just an office address. Neither shared branding nor co-location proves ownership.
5. **Entity homes must resolve actual content records.** A shared reader page is insufficient for an adviser profile, award article or calendar event. This is a foundational change, not another dropdown option.
6. **Public people require selective source binding.** OKAL's `tl_member` use is a valid public directory use case. It must not turn all members into public people. News authors retain their `tl_user` path. A sales contact is not automatically an article author.
7. **Content remains content.** A case study is normally an Article about services/products and possibly a named client; it is not another sellable Service. A recording is not a newly scheduled Event. An award report is not itself an award winner.
8. **Existing schema is an integration input.** Agorum has real public IDs and additional software/offer properties. Preserve them intentionally; do not replace whole scripts or normalize away established identities.

Sources and qualifications are in the linked site files; the statements above are design conclusions, not an assertion that every proposed relation is implemented.

## Scope proposed for discussion

The coordinated release should cover: a central type/profile definition mechanism; reusable Place/House and organization/medical profiles; generic product facts and composition; typed source/home resolution; opted-in public people; richer award/certification records; content relationships and categories; import/AI/graph coverage for everything accepted.

The user narrowed media scope in round 2: optional recordings attached to Events, no standalone recording manager or Course/CourseInstance handling. Current import behavior remains the baseline; a generalized ownership/conflict redesign is deferred. Earlier publishing and vehicle requirements remain in the matrix and feedback even though no real examples were supplied this round. Smart social images and automatic price extraction remain parked. The expansion must interoperate with those future features without importing them now.

## Next planning rounds

1. **Confirm meaning and ownership:** review the received D01–D09 responses and complete the focused Round 2 questions and the project source/identity questions. Resolve medical organization boundaries and public-member selection before fixing the data model.
2. **Review editorial walkthroughs:** agree the minimal fields, conditional sections, source authority and migration behavior with representative real records. Produce example output for tricky cases, not a giant type checklist.
3. **Freeze the coordinated scope:** approve the type/property/relationship matrix and acceptance scenarios, including consciously deferred cases.
4. **Only then implement:** internal steps may be incremental, but delivery remains one coordinated expansion, as requested. No implementation, deployment, merge to main or release is part of this round.
