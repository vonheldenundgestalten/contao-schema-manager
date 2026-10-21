# Extension API v1 specification

[Planning index](../entity-expansion-plan.md) · [OKAL handover](okal-bundle-handover.md)

20 October 2026 implementation update: the generic v1 contracts and integration are
implemented and tested on the extension development installation. The [developer guide](../extension-api/README.md)
is the implementation reference and records v1 limits and exact registration rules.
The broader catalogue remains planning; the generic API is released as 1.4.0-beta.1; OKAL deployment remains deferred.

## Ownership

Schema Manager owns identity, registries, typed supplementary storage, validation,
publication checks, localization contexts, graph composition, editor integration,
cache coordination and diagnostics. Native integrations should converge on the API
without changing existing IDs or creating duplicate emitters.

okal-bundle owns all OKAL tables, queries, discriminators, routes, adviser eligibility,
field mappings, import notifications and custom editor changes. It requires
vonheldenundgestalten/contao-schema-manager through Composer. Choose the actual minimum
compatible release only after the API exists; no speculative dependency is installed now.
A separate integration package is useful only if OKAL must run without Schema Manager.

A bundle can contribute a source using core types, types without sources, or both;
relationships are a third independent extension point. Generic housing definitions
may later move to core or a reusable package through an explicit migration. No OKAL
code, table names, fixtures or migration belongs in core. Use synthetic fixtures for
core contract tests.

## Contracts and registration

Proposed public namespace: VHUG\SchemaManagerBundle\Extension\V1.
Interfaces and immutable DTOs below are implemented; the developer guide also covers
EntityEditorInterface and PayloadMigrationInterface.
Existing EntityMapper, EntityModel and backend internals are not public APIs.

```php
interface SourceAdapterInterface
{
    public function key(): string;
    public function describe(): SourceDefinition;
    public function findCandidates(CandidateQuery $query): CandidatePage;
    public function resolve(SourceReference $source, ReadContext $context): SourceResolution;
}

interface TypeProviderInterface
{
    /** @return iterable<TypeDefinition> */
    public function types(): iterable;
}

interface RelationshipProviderInterface
{
    /** @return iterable<RelationshipDefinition> */
    public function relationships(): iterable;
}

interface EntityTypeMapperInterface
{
    public function map(ResolvedEntity $entity, MappingContext $context): NodeContribution;
}

interface EntityTypeValidatorInterface
{
    /** @return iterable<Violation> */
    public function validate(ResolvedEntity $entity, ValidationContext $context): iterable;
}

interface BindingManagerInterface
{
    public function bind(BindRequest $request, WriteContext $context): EntityReference;
    public function resolve(SourceReference $source): ?EntityReference;
}

interface SourceInvalidatorInterface
{
    public function changed(SourceReference $source): void;
    public function adapterChanged(string $adapterKey): void;
}
```

Explicit Symfony service tags, also supplied through interface autoconfiguration:

| Contribution | Tag |
| --- | --- |
| SourceAdapterInterface | schema_manager.source_adapter |
| TypeProviderInterface | schema_manager.type_provider |
| RelationshipProviderInterface | schema_manager.relationship_provider |

Definitions reference mapper/validator service IDs implementing the contracts.
Keys are namespaced, e.g. example.house_model. Collect all definitions before resolving
references. Duplicate keys, unknown targets, incompatible shapes or missing services
fail container compilation with actionable errors; priority never silently overrides
another bundle. Labels use translation keys supplied by their bundle.
No editor-entered PHP, arbitrary callable, direct core-table write or service decoration
is required. The actual PHP namespace of okal-bundle must be inspected later.

## Adapter DTOs and source authority

| DTO | Required shape/behavior |
| --- | --- |
| SourceReference | adapterKey, installationScope, immutable subjectKey |
| SourceDefinition | supported type keys; owned global/localized field keys; discovery capabilities |
| CandidateQuery | authorized backend actor/site scope, search, language, allowed types, opaque cursor, bounded limit (default 50, maximum 100) |
| CandidatePage | safe candidate summaries, nextCursor; no automatic binding/publication |
| ReadContext | purpose (frontend/preview/discovery/AI), site/root, language, actor/access context, clock |
| SourceResolution | status plus available snapshot or safe diagnostic code |
| SourceSnapshot | source reference, revision, type key, global fields, requested localized presentation, canonical home or explicit no-home, relationships, provenance, cache tags, nextVisibilityChange, authorized edit-original link |
| BindRequest | source reference, expected revision, type key, optional explicitly selected existing EntityReference |
| WriteContext | authorized actor and site scope |

Resolution statuses: available, unpublished, missing, forbidden, translation_missing,
unavailable. Only available can contribute publicly, after core additionally checks
entity publication, placement and relationship-target visibility. Backend inspection
permission and public source eligibility are separate checks.

Source keys are not row IDs, page IDs, aliases or entity IDs. Translations share the
same subject key. One row can expose multiple explicitly discriminated subjects.
Use immutable upstream IDs where imports recreate rows. Alias changes never mint
a new identity. Do not infer translated identity from matching names/slugs.

Snapshots must be coherent: facts, relations, language and URL come from the same
revision. Core resolves each subject once per graph build. If a concurrent import
changes the revision, the adapter retries or returns unavailable, never mixed facts.
No private source object is passed through to mappers or AI.

Source-owned fields stay read-only even when empty/null. Supplementary fields may
fill only keys the source does not own. There is no write-back or per-field override
API in v1. Read-only values link to the authorized original editor. This policy applies
equally to manual editing and AI proposals.

Missing translations have an explicit status, no silent language substitution.
A public entity may have no individual home and still have approved placements.
A reader page without a record parameter is not a resolved entity home. Adapters use
actual project routing and establish public eligibility without fabricating URLs.

BindingManager allocates identities transactionally and enforces uniqueness of the
complete SourceReference tuple. Repeated/concurrent binding is idempotent.
One authoritative source binding per entity in v1; linking to an existing identity
requires explicit selection/review. Never merge by name or URL. Other appearances
reference the authority. Changes to immutable source keys require explicit rebinding.

## Types, fields and storage

TypeDefinition declares key, schemaVersion, label key, schemaTypes, reusable
capabilities, fields, supported relationship keys, mapper service, validators and
manual-create/source-bind/AI-proposal flags.

FieldDefinition declares key, global/localized scope, value type, cardinality,
constraints, optional default, allowlisted editor widget and AI policy.
V1 shapes: text, URL, date/date-time, boolean, integer, decimal, enum, measurement,
managed reference and bounded repeatable structures. Measurement includes precise
decimal value, unit and measurement kind/basis. Constraints include enums, bounds
and required-if rules; source and supplementary values use the same validation.

Core provides reusable PropertyValue/QuantitativeValue builders. ProductModel
technical facts can use additionalProperty where accommodation properties would
misdescribe a design. DIN 277 net area and living area retain distinct bases.

Storage proposal: normalized unique binding table; namespaced supplementary payload
per entity plus localized payloads; normalized supplementary relationship rows.
Keep existing core facts in existing typed tables. Extension payloads have registry-
validated shapes and schema versions, never arbitrary editor-created EAV/raw JSON-LD
or serialized PHP. Explicit provider migrations handle shape changes; no migration
during public rendering. Exact SQL is an implementation detail, not the bundle API.
Retain bindings/payloads when a provider is unavailable.

ResolvedEntity gives the mapper validated merged fields and visibility-filtered
relations. MappingContext exposes validated value builders and named nested slots,
not unrestricted database access or a second graph emitter. NodeContribution returns
properties and diagnostics. Core owns @id, definition-derived @type, localized home,
deduplication and final JsonLdManager serialization.

Definitions declare emitted properties/shapes; validate domain/range and any enabled
pending vocabulary. Nested value objects are allowed; arbitrary @context, sibling
entities, script HTML or bypassing reference resolution are not. Support vocabulary
URLs without requiring a corresponding installed Spatie builder class.
Changing labels/presets never retypes existing records. Incompatible retyping or type
key promotion requires reviewed migration and preserves semantic IDs.

## Relationship contracts

RelationshipDefinition declares:
- namespaced key and translated label;
- allowed source/target type keys or capabilities, cardinality and scope;
- public mapping or editorial-only status;
- vocabulary predicate, direction, output shape and vocabulary maturity;
- main-node or declared nested-slot location (e.g. offers);
- compact target projection, traversal/cycle policy, evidence/AI policy.

RelationshipValue contains target EntityReference or SourceReference, optional scope
and evidence. Source references resolve only through existing approved bindings.
Rendering never creates/publishes missing dependencies. Missing/private targets yield
safe backend diagnostics and omitted public edges, without exposing private labels.
Reject invalid source/target pairs on save and source resolution. Optional inverse
output must be explicitly declared and vocabulary-valid.

Illustrative OKAL mappings, to be registered in okal-bundle later:
- ProductModel -> manufacturer -> OKAL Organization.
- Offer -> seller -> Organization, through the mapper-declared offers slot.
- Model -> displayLocation -> confirmed physical show home, only with an explicit
  pending-vocabulary policy.
- Physical house -> represented model: editorial-only until a justified public
  predicate/type combination is agreed; do not pretend every building is Product.
- Adviser -> worksFor only where employment is confirmed; workLocation for an actual office.
- News report -> about -> recipient/model; recipient -> subjectOf -> report.

These distinguish seller, manufacturer, employer, provider and organizer. No generic
relatedTo shortcut or automatic symmetric graph edges. Editorial-only edges are
labelled/filterable in the backend and never emitted as invented schema properties.

Core deduplicates IDs and orders output deterministically. Default relationships
use compact one-hop projections; full expansion follows localized-home policy.
Venue projections retain needed address/geo. Prohibited hierarchy cycles are rejected;
permitted cycles emit references without recursive expansion. Budget traversal and
queries so a provider cannot accidentally serialize the whole source database.

## Lifecycle, permissions, caches and AI

SourceInvalidator is called by the integration after committed writes/import batches.
Adapter-wide invalidation supports batch imports; rolled-back changes must not announce
successful updates. Adapter dependencies include routing/configuration and scheduled
publication changes, not just row updates.

Core tracks reverse dependencies for source and relation targets, including unresolved
references where possible; later binding/publication must invalidate dependent pages.
Shared response expiry is bounded by nextVisibilityChange. Provider-set changes
invalidate a schema cache namespace attached to every dependent response.
Missing adapters/definitions/sources fail closed, with no stale public source facts.
Keep identities and data, expose unresolved backend states, and never invoke absent
code. Reinstalling compatible providers restores bindings without new IDs.
Removing a bundle never deletes entity data automatically.

Backend candidates require Schema Manager and source permissions. Public emission uses
public eligibility, not backend actor rights. Authorized preview can inspect drafts
but preview/AI snapshots never enter shared public caches. Edit links are authorized
backend destinations and never enter JSON-LD or public graph payloads.

AI uses the same registry and ownership checks. Custom type proposals default off
until the provider enables fields/relationships/evidence policies. Only permitted
public source values enter AI context. Applying proposals rechecks revision,
permissions, target state and source ownership. Candidate discovery is not a crawler.

## Compatibility and delivery gates

1. Implement generic API/registries/storage using a synthetic example bundle.
2. Integrate editor, preview, graph, publication, AI and cache behavior. Converge native
   paths through compatibility tests, preserving existing output and avoiding duplicates.
3. Publish executable DTOs/interfaces, minimal example bundle and developer docs;
   assign the real compatible release/version and supported platform requirements.
4. Deploy the generic extension to its development installation and verify affected
   backend flow and frontend JSON-LD, additive migrations and cache rebuild.
5. Install and verify that API-enabled release on OKAL dev.
6. Only after step 5 start okal-bundle mapping/implementation and its dev verification.

Implementation/development verification now covers steps 1-4. Steps 5-6 remain deferred.
The generic API is released as 1.4.0-beta.1; no production deployment is included. Backward-compatible additions use optional DTO fields
or new interfaces; never add mandatory methods to v1 implementations. Breaking API
changes require a new namespace/major compatibility policy and migration guide.

## Implemented v1 boundaries

The developer guide takes precedence over earlier proposal wording. The extension console is administrator-only; applications can use the public interfaces for narrower permission-aware workflows. Definition metadata is declarative and compile-validated. Cache invalidation conservatively covers the shared extension namespace, including negative results, rather than a new reverse-dependency table. SourceResolution can carry cache tags/deadlines without source facts for scheduled publication. AI supports opt-in existing-entity supplementary fields and relationships; custom creation and source binding stay manual. Type and relationship key renames need explicit reviewed migration. No OKAL-specific adapter or industry catalogue is shipped.
