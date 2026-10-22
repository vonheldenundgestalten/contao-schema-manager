# Extension API v1

The generic API v1 is available in stable **1.4.0**.
Install with `composer require vonheldenundgestalten/contao-schema-manager:^1.4 --with-all-dependencies`; apply the additive Contao database updates and rebuild the cache. Automatic-source integrations additionally run their own source public-ID adoption migration.

Public namespace: `VHUG\SchemaManagerBundle\Extension\V1`. Classes under Internal,
existing models and emitters are implementation details. Consumers register Symfony
services and use public binding/editor/invalidation interfaces, never core-table SQL.

The [equipment provider](../../examples/extension/src/ExampleProvider.php) and
[file source](../../examples/extension/src/ExampleSourceAdapter.php) are synthetic
examples, not production repositories. They are never auto-registered. A real bundle
uses Composer PSR-4 autoloading and [service registration](../../examples/extension/services.yaml).

## Registration

| Interface | Autoconfiguration tag |
| --- | --- |
| SourceAdapterInterface | schema_manager.source_adapter |
| TypeProviderInterface | schema_manager.type_provider |
| RelationshipProviderInterface | schema_manager.relationship_provider |
| EntityTypeMapperInterface | schema_manager.type_mapper |
| EntityTypeValidatorInterface | schema_manager.type_validator |
| PayloadMigrationInterface | schema_manager.payload_migration |

Definition providers are declarative services without required constructor arguments.
Adapters may inject repositories/routing services, but key() and describe() must be
pure metadata independent of constructor state. Compilation validates metadata without
source access. Invalid/duplicate keys, unknown endpoints, missing services and invalid
vocabulary domains/ranges fail compilation. Cache warmup revalidates the registry.

Namespaced keys such as example.equipment identify definitions. Field keys are local
identifiers such as capacity. Labels use MSC translation keys with readable fallbacks.
TypeDefinition declares Schema.org types, fields, emitted properties, mapper/validator
service IDs, relationships, capabilities, schemaVersion and manual/source/AI flags.
Changing a label never retypes an existing identity.

Fields specify global/localized scope, type, cardinality, widget and constraints.
Supported shapes: text, HTTP(S) URL, date, timezone-aware date-time, boolean, integer,
precise decimal string, enum, measurement, managed reference and bounded structures.
Measurement contains value (decimal string), unit and basis: living area and DIN 277
net area stay distinct. Lists express multiple values. Structures declare named child
FieldDefinitions in constraints.fields. Constraints include min/max, maxLength,
maxItems, choices and requiredIf. Reference fields require constraints.targetTypes
and EntityReference values; prefer named relationships for semantic links.

Complete validation runs on preview/render; supplementary editing allows incomplete
drafts while checking supplied shapes. properties lists the mapper's allowed predicates.
slots maps singular nested properties to vocabulary types, e.g. offers => [Offer].
Core owns identity, type, URL, graph assembly and serialization. Mappers cannot emit
arbitrary references, contexts or sibling entities. New vocabulary does not require
a matching Spatie class. Supporting nodes remain compact; venue projections can
explicitly retain address/geo.

[Bundled vocabulary attribution](../../resources/SCHEMA-VOCABULARY.md) describes the
metadata snapshot. Vocabulary validity does not promise search rich-result eligibility.
Pending predicates need both a pending relationship declaration and application
parameter schema_manager.extension.allow_pending=true; the default is false.

## Source identity and authority

SourceReference consists of adapterKey, installationScope and immutable subjectKey.
Use upstream business IDs where imports recreate rows. Row IDs, page IDs, aliases,
localized homes and semantic identities remain separate. Translations share a subject;
one row can expose multiple explicit subjects.

ReadContext supplies purpose, site/root, language, actor and time. Non-public purposes
require an authorized actor. Adapters still enforce source-specific permissions and
public eligibility. authorized does not grant arbitrary source access. Public rendering
also checks entity publication and target visibility.

SourceResolution contains an available immutable snapshot, or unpublished/missing/
forbidden/translation_missing/unavailable with no facts. Snapshots include declared
fields, revision, relationships, URL or explicit no-home and optional visibility
deadline. Resolve facts, routing and relationships coherently. No translation fallback
is inferred. Discovery returns safe summaries, an opaque cursor and a bounded page
(default 50, maximum 100), without binding or publishing records.

Example from an already authorized backend request:

```php
$read = new ReadContext('preview', $rootId, 'en', $actorId, true, time());
$write = new WriteContext($read, 'https://example.org');
$entity = $bindings->bind(
    new BindRequest($source, $revision, 'example.equipment'),
    $write,
);
```

Binding is transactional/idempotent and creates a draft. Existing-identity linking
requires explicit selection, never a name/URL merge. Each entity has one authoritative
source. Source-owned fields stay read-only even when empty. Sources cannot declare identity, publication, routing or storage-control fields as ordinary editable facts. An automatic source explicitly supplies its persisted identity through `SourceSnapshot.publicIdentity`; its availability and canonical snapshot URL determine public output. There is no write-back/override API.

EntityEditorInterface supports saveFields, saveRelationship, discover and inspect.
Writes require the current entity revision and, for source-backed records, a WriteContext
with expectedSourceRevision. They return the next entity revision. Unknown fields,
wrong scopes/types, source-owned values and stale revisions are rejected. Publication
continues through normal reviewed Contao editing; dependencies never publish themselves.

## Relationships and backend

RelationshipDefinition declares endpoint type keys/capabilities, cardinality, scope,
public predicate or editorial-only status, optional slot, compact projection and
cycle/pending policy. A selector capability:place expands before validation. Built-in
capabilities cover organization, place, person, service, product, software/creative_work
and event; contributed types declare their capabilities. All public endpoints must
satisfy vocabulary domains/ranges.

Declare each direction explicitly; no automatic symmetric/inverse output.
RelationshipValue contains EntityReference or SourceReference. Rendering resolves source
references through existing bindings only. Supplementary saved relations require bound
targets; adapters can return unresolved references for diagnostics. Invalid/draft/private
targets are omitted publicly. Editorial-only links are labelled in the backend and never
become invented predicates. Prohibited cycles are rejected; allowed cycles terminate.

The Extension data console provides discovery, draft binding, typed forms, relationship
selection, diagnostics and preview. This configuration console is administrator-only
in v1. Integrating bundles can expose source-permission-aware workflows for other users
through the public interfaces. Entity/language editors protect source values and link
to extension editing. Source edit links remain restricted backend destinations.

## Cache, lifecycle and migration

Call SourceInvalidatorInterface.changed after committed source changes, or adapterChanged
after committed import/routing/configuration batches. If your integration owns an outer
transaction, invalidate after that outer commit too. Visibility deadlines clamp HTTP
cache lifetime without making private responses public.

V1 conservatively invalidates one shared extension tag rather than maintaining a
separate reverse-dependency index. All schema responses carry it, including missing/
unpublished/unresolved cases. This covers later binding/publication and target changes.
Provider removal requires the normal application cache rebuild.

Unavailable providers/adapters fail closed, retain stored identities/payloads and recover
those identities when reinstalled. No automatic destructive removal cleanup runs.
Traversal is limited to one supporting hop and 100 identities.

A schemaVersion mismatch reports migration_required and emits no stale payload.
Register consecutive PayloadMigrationInterface implementations, then review and apply:

```sh
php vendor/bin/contao-console schema-manager:extension:migrate ENTITY_ID
php vendor/bin/contao-console schema-manager:extension:migrate ENTITY_ID --apply
```

Dry run makes no writes. Apply checks version/revision atomically, validates transformed
payloads, preserves identity/source authority and invalidates output. Migration never
runs on render. Type-key and relationship-key renames need separately reviewed migration.

## Optional AI and compatibility

Type/field/relationship AI flags default off. Existing opt-in public entities support
extension_set (JSON-encoded typed value) and extension_add (JSON integer entity ID)
proposals through the existing helper. Source-owned fields are excluded, including empty
ones. Evidence, scope, language, permissions and source/entity revisions are rechecked.
Legacy proposals cannot overwrite source-backed records. Custom entity creation, binding
and publication remain manual in v1. Rendering makes no AI calls.

Run composer test and the installed tests/extension-integration.php checks. Public v1
contracts evolve through optional DTO fields/new interfaces; breaking changes need a
new API namespace and migration guidance. This API does not ship the broad industry
catalogue or project adapters. [OKAL handover](../entity-expansion/okal-bundle-handover.md)
remains deferred.


Core-type source adapters use the existing scalar fact mapper; unsupported native fields require a contributed type definition. SourceResolution may carry cache tags and nextVisibilityChange without facts, so future publication can expire a negative cached result. Supplementary reference payloads are encoded/decoded by the core; integrations never serialize them directly.

### Explicit language-root approval

Source-scope equality is always checked for rendering, binding, editing and discovery. There is no inferred equivalence between domains or language roots. An application administrator may explicitly map approved Contao root IDs to a canonical source scope using `schema_manager.extension.approved_roots`, for example `{ "2": "1" }`. The default is empty. ReadContext.site then carries that approved canonical scope while ReadContext.root retains the actual routing root. Adapter record permissions/publication still apply; the mapping grants neither backend access nor publication. Use this explicit configuration when the same source identity has homes across language roots.

For manual contributed types, a global name field reuses the existing managed entity name. It does not create a competing supplementary name. A source-owned name still comes exclusively from its source snapshot.

Existing imported JSON-LD must be explicitly reviewed/retired before a record switches to source authority. Binding rejects this overlap instead of silently replacing imported identities or unsupported facts; existing unbound imports are unchanged.


## Automatic source ownership

Set `SourceDefinition(automatic: true)` when every fact and relationship belongs
to the original source. Persist the public ID there and return it through
`SourceSnapshot(publicIdentity: ...)`. Adopt existing IDs through an explicit
integration migration; never allocate replacements for already published subjects.
Generate fresh IDs for new subjects and copied records. On source saves/import
completion, reconcile bindings through `BindingManagerInterface`; the application
makes the reference index addressable while adapter availability controls output.

Automatic rows are excluded from manual entity/discovery editors, supplementary
writes and AI edit suggestions. Runtime ignores stored supplements for them. The
backend graph retains the source nodes, follows source visibility and opens the
snapshot's local Contao `editUrl`. No source writes occur while rendering. Existing
adapters default to `automatic: false` and retain draft binding/supplementary editing.

For reviewed pending properties, approve individual relationship keys through
`schema_manager.extension.approved_pending_relationships`. All other pending
relationships retain the global default of disabled.
