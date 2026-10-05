<?php
declare(strict_types=1);
$GLOBALS['TL_LANG']['schema_ai']['title'] = 'Schema AI helper';
$GLOBALS['TL_LANG']['schema_ai']['intro'] = 'Turn your website content into schema entries. Review each suggestion before saving.';
$GLOBALS['TL_LANG']['schema_ai']['back'] = 'Back to entities';
$GLOBALS['TL_LANG']['schema_ai']['keyTitle'] = 'OpenAI API key';
$GLOBALS['TL_LANG']['schema_ai']['keyMissing'] = 'No key configured. Add a dedicated key to enable analysis.';
$GLOBALS['TL_LANG']['schema_ai']['keyPresent'] = 'Key configured. The stored value is never shown.';
$GLOBALS['TL_LANG']['schema_ai']['keyHelp'] = 'Saved on this server in .env.local as SCHEMA_AI_API_KEY. Use a dedicated key for this site. No model selector or spending enforcement.';
$GLOBALS['TL_LANG']['schema_ai']['saveKey'] = 'Save key';
$GLOBALS['TL_LANG']['schema_ai']['replaceKey'] = 'Replace key';
$GLOBALS['TL_LANG']['schema_ai']['keySaved'] = 'API key saved. No paid request was made.';
$GLOBALS['TL_LANG']['schema_ai']['keyError'] = 'The key could not be saved/read safely. Check file permissions or configure SCHEMA_AI_API_KEY in .env.local on the server.';
$GLOBALS['TL_LANG']['schema_ai']['migrate'] = 'Run the Contao database update to create the optional analysis history table.';
$GLOBALS['TL_LANG']['schema_ai']['startTitle'] = 'Analyze website';
$GLOBALS['TL_LANG']['schema_ai']['root'] = 'Website / language root';
$GLOBALS['TL_LANG']['schema_ai']['origin'] = 'Public identity origin';
$GLOBALS['TL_LANG']['schema_ai']['originHelp'] = 'Permanent public HTTPS origin, e.g. https://www.example.org. Do not use the staging hostname.';
$GLOBALS['TL_LANG']['schema_ai']['discover'] = 'Analyze and prefill new entities';
$GLOBALS['TL_LANG']['schema_ai']['improve'] = 'Check schema for improvements';
$GLOBALS['TL_LANG']['schema_ai']['changed'] = 'Only new or changed content since the last successful analysis of this kind';
$GLOBALS['TL_LANG']['schema_ai']['prepare'] = 'Prepare analysis';
$GLOBALS['TL_LANG']['schema_ai']['disclosure'] = 'The next step sends the collected public text and existing schema context to OpenAI. New entities and localized homes are created unpublished only after review. Changes approved for existing published records affect live JSON-LD.';
$GLOBALS['TL_LANG']['schema_ai']['coverage'] = 'Sources: public rendered HTML where reachable, with published Contao page/article/content and News text as fallback. Noindex, protected and unpublished content is excluded. JavaScript-only content, files and external research are not covered. Use the source list to check coverage.';
$GLOBALS['TL_LANG']['schema_ai']['recent'] = 'Recent analyses';
$GLOBALS['TL_LANG']['schema_ai']['run'] = 'Analysis';
$GLOBALS['TL_LANG']['schema_ai']['status'] = 'Status';
$GLOBALS['TL_LANG']['schema_ai']['sources'] = 'Sources';
$GLOBALS['TL_LANG']['schema_ai']['remaining'] = 'Remaining';
$GLOBALS['TL_LANG']['schema_ai']['continue'] = 'Analyze remaining sources';
$GLOBALS['TL_LANG']['schema_ai']['stop'] = 'Stop after current batch';
$GLOBALS['TL_LANG']['schema_ai']['progress'] = 'Analyzing sources…';
$GLOBALS['TL_LANG']['schema_ai']['review'] = 'Review suggestions';
$GLOBALS['TL_LANG']['schema_ai']['selectAll'] = 'Select all suggestions';
$GLOBALS['TL_LANG']['schema_ai']['clear'] = 'Clear selection';
$GLOBALS['TL_LANG']['schema_ai']['selectHelp'] = '1. Open an entity. 2. Tick the changes you want; check the source if unsure. 3. Apply your selection. Required draft and page steps are selected together.';
$GLOBALS['TL_LANG']['schema_ai']['apply'] = 'Apply selected changes';
$GLOBALS['TL_LANG']['schema_ai']['reject'] = 'Reject selected suggestions';
$GLOBALS['TL_LANG']['schema_ai']['applyHelp'] = 'New entries stay unpublished. Changes to published entries go live when applied. Reject dismisses selected suggestions; leaving them unticked keeps them for later.';
$GLOBALS['TL_LANG']['schema_ai']['applied'] = 'Applied %s suggestions. New records remain unpublished.';
$GLOBALS['TL_LANG']['schema_ai']['rejected'] = 'Selected suggestions rejected. Identical evidence will not suggest them again.';
$GLOBALS['TL_LANG']['schema_ai']['selectSome'] = 'Select at least one pending suggestion.';
$GLOBALS['TL_LANG']['schema_ai']['empty'] = 'No suggestions yet. Analyze sources, or inspect the warnings if analysis has finished.';
$GLOBALS['TL_LANG']['schema_ai']['current'] = 'Current';
$GLOBALS['TL_LANG']['schema_ai']['proposed'] = 'Suggested value';
$GLOBALS['TL_LANG']['schema_ai']['evidence'] = 'Evidence';
$GLOBALS['TL_LANG']['schema_ai']['reason'] = 'Why this change';
$GLOBALS['TL_LANG']['schema_ai']['pending'] = 'pending';
$GLOBALS['TL_LANG']['schema_ai']['invalid'] = 'invalid';
$GLOBALS['TL_LANG']['schema_ai']['appliedState'] = 'applied';
$GLOBALS['TL_LANG']['schema_ai']['rejectedState'] = 'rejected';
$GLOBALS['TL_LANG']['schema_ai']['error'] = 'The operation failed. No new schema changes were applied.';
$GLOBALS['TL_LANG']['schema_ai']['usage'] = 'Reported usage';
$GLOBALS['TL_LANG']['schema_ai']['input'] = 'input tokens';
$GLOBALS['TL_LANG']['schema_ai']['output'] = 'output tokens (including reasoning)';
$GLOBALS['TL_LANG']['schema_ai']['cost'] = 'Approximate Standard API cost, excluding tax and surcharges';
$GLOBALS['TL_LANG']['schema_ai']['noAutomatic'] = 'Preparing the inventory makes no API request. Start analysis explicitly on the next screen.';
$GLOBALS['TL_LANG']['schema_ai']['ready'] = 'ready';
$GLOBALS['TL_LANG']['schema_ai']['working'] = 'working';
$GLOBALS['TL_LANG']['schema_ai']['paused'] = 'paused';
$GLOBALS['TL_LANG']['schema_ai']['complete'] = 'complete';

$GLOBALS['TL_LANG']['schema_ai']['newEntity'] = 'New entity';
$GLOBALS['TL_LANG']['schema_ai']['home'] = 'Page for this language';

$GLOBALS['TL_LANG']['schema_ai']['newAnalysis'] = 'Start another analysis';

$GLOBALS['TL_LANG']['schema_ai']['analysisDetails'] = 'Sources and API usage';

$GLOBALS['TL_LANG']['schema_ai']['createAction'] = 'Create draft';

$GLOBALS['TL_LANG']['schema_ai']['homeAction'] = 'Assign page';

$GLOBALS['TL_LANG']['schema_ai']['linkAction'] = 'Add relationship';

$GLOBALS['TL_LANG']['schema_ai']['fillAction'] = 'Fill empty field';

$GLOBALS['TL_LANG']['schema_ai']['replaceAction'] = 'Replace existing value';

$GLOBALS['TL_LANG']['schema_ai']['selectedCount'] = 'changes selected';

$GLOBALS['TL_LANG']['schema_ai']['toReview'] = 'to review';

$GLOBALS['TL_LANG']['schema_ai']['editSuggestion'] = 'Edit suggested text';

$GLOBALS['TL_LANG']['schema_ai']['draftHint'] = 'Creates a new, unpublished schema entity. Publish it later in Schema Manager when ready.';

$GLOBALS['TL_LANG']['schema_ai']['homeHint'] = 'This page represents the entity in this language. The assignment starts unpublished.';

$GLOBALS['TL_LANG']['schema_ai']['newGroup'] = 'New';

$GLOBALS['TL_LANG']['schema_ai']['updateGroup'] = 'Update';

$GLOBALS['TL_LANG']['schema_ai']['editorReview'] = 'Editorial assessment';

$GLOBALS['TL_LANG']['schema_ai']['feedbackTitle'] = 'Discuss and refine these suggestions';

$GLOBALS['TL_LANG']['schema_ai']['feedbackHelp'] = 'Tell the helper what matters, what is too vague, or which connections are missing. It will explain its choices and prepare a revised set for your review.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackLabel'] = 'Your feedback or question';

$GLOBALS['TL_LANG']['schema_ai']['feedbackExample'] = 'Keep development as one service. AI Label is a Contao extension. Explain which relationships we can represent and which are missing.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackDisclosure'] = 'Sends your feedback, saved public sources and schema context to OpenAI (API charges apply). Creates a separate review; does not apply changes. Apply only the version you choose. Unsaved text edits and checkbox selections are not included.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackSend'] = 'Send feedback and revise suggestions';

$GLOBALS['TL_LANG']['schema_ai']['feedbackWorking'] = 'Preparing revised suggestions…';

$GLOBALS['TL_LANG']['schema_ai']['feedbackWait'] = 'Finish the analysis to discuss its suggestions.';

$GLOBALS['TL_LANG']['schema_ai']['feedbackApplied'] = 'Some suggestions have already been applied. Start a fresh analysis to discuss the updated schema.';

$GLOBALS['TL_LANG']['schema_ai']['alternativeReview'] = 'This is a revised proposal set. Nothing was applied.';

$GLOBALS['TL_LANG']['schema_ai']['originalReview'] = 'Original analysis';

$GLOBALS['TL_LANG']['schema_ai']['conversation'] = 'Conversation so far';

$GLOBALS['TL_LANG']['schema_ai']['you'] = 'You';

$GLOBALS['TL_LANG']['schema_ai']['assistant'] = 'Schema helper';

$GLOBALS['TL_LANG']['schema_ai']['controlsLoading'] = 'Loading analysis controls. If this message remains, reload this page before starting.';

$GLOBALS['TL_LANG']['schema_ai']['lastUpdated'] = 'Updated';

$GLOBALS['TL_LANG']['schema_ai']['viewingAnalysis'] = 'Viewing';

$GLOBALS['TL_LANG']['schema_ai']['openAnalysis'] = 'Open analysis';

$GLOBALS['TL_LANG']['schema_ai']['multilingual'] = 'Include the other languages of this website';
$GLOBALS['TL_LANG']['schema_ai']['multilingualHelp'] = 'Scans published language roots on the same domain and translates new entries using linked pages. Existing translations stay unchanged. Multilingual scans include all sources, even when “only changed” is selected.';

$GLOBALS['TL_LANG']['schema_ai']['localizing'] = 'Translating missing language entries…';

$GLOBALS['TL_LANG']['schema_ai']['waitingBatch'] = 'Waiting for the running batch…';

$GLOBALS['TL_LANG']['schema_ai']['stages'] = 'Setup stages';
$GLOBALS['TL_LANG']['schema_ai']['stage_foundation'] = '2 · Organisation';
$GLOBALS['TL_LANG']['schema_ai']['stage_foundation_help'] = 'Identify or improve the website operator and its localized homes. Review and publish the accepted drafts before content enrichment.';
$GLOBALS['TL_LANG']['schema_ai']['stage_configuration'] = '3 · Websites and archives';
$GLOBALS['TL_LANG']['schema_ai']['stage_configuration_help'] = 'Review website publishers and archive defaults before enriching individual entries. No AI request is needed.';
$GLOBALS['TL_LANG']['schema_ai']['stage_content'] = '4 · Content enrichment';
$GLOBALS['TL_LANG']['schema_ai']['stage_content_help'] = 'Find new subjects or improve existing services, people, products and news using the reviewed foundation.';
$GLOBALS['TL_LANG']['schema_ai']['reviewParents'] = 'Prepare parent review';
$GLOBALS['TL_LANG']['schema_ai']['parentReviewHelp'] = 'Review each setting and select the changes to apply. Current archive types are preserved until you choose otherwise. Draft publishers may be assigned but emit no schema until published. Parent settings affect existing and future child content; this does not create archive entities.';
$GLOBALS['TL_LANG']['schema_ai']['websiteImpact'] = 'Sets the website publisher or public site name for this language root. Existing website identities are preserved.';
$GLOBALS['TL_LANG']['schema_ai']['archiveImpact'] = 'Controls schema type and publisher for every entry in this archive. Choose BlogPosting for a blog, NewsArticle for news, or Article for general editorial content such as case studies. Keep Contao default if no type change is needed. JobPosting requires additional job fields; suppress removes the news schema.';
$GLOBALS['TL_LANG']['schema_ai']['calendarDeferred'] = 'Calendar detected. Calendar-event enrichment and schema defaults are not implemented yet. This review makes no calendar changes.';
$GLOBALS['TL_LANG']['schema_ai']['archiveGroup'] = 'News archive settings';
$GLOBALS['TL_LANG']['schema_ai']['noPublisher'] = 'No publisher assigned';
$GLOBALS['TL_LANG']['schema_ai']['coreType'] = 'Contao default (NewsArticle)';
$GLOBALS['TL_LANG']['schema_ai']['suppressType'] = 'Suppress news schema';
$GLOBALS['TL_LANG']['schema_ai']['draftState'] = 'unpublished draft';
$GLOBALS['TL_LANG']['schema_ai']['reviewSetting'] = 'Review';
$GLOBALS['TL_LANG']['schema_ai']['effect'] = 'Effect on the website';
$GLOBALS['TL_LANG']['schema_ai']['reviewValue'] = 'Setting to review';
$GLOBALS['TL_LANG']['schema_ai']['parentSummary'] = 'Configuration review · no API usage · no entries are created';
$GLOBALS['TL_LANG']['schema_ai']['parentSelectionHelp'] = 'Check each website and archive. Change the settings you need, then select those fields and apply. Already-correct settings can be left unchecked.';
$GLOBALS['TL_LANG']['schema_ai']['parentApplyHelp'] = 'Selected settings take effect on existing and future content. Publisher drafts remain unpublished. Archive types are editorial choices, not AI guesses.';
$GLOBALS['TL_LANG']['schema_ai']['foundationDraftWarning'] = 'Publish the reviewed organisation and its localized homes before content enrichment. Existing drafts are reserved against duplication, but are not used as active content.';
$GLOBALS['TL_LANG']['schema_ai']['calendarGroup'] = 'Calendar settings';
$GLOBALS['TL_LANG']['schema_ai']['enrichEvents'] = 'Enrich Event schema';
$GLOBALS['TL_LANG']['schema_ai']['calendarImpact'] = 'Calendar defaults affect all its events. Event fields override these defaults. Core dates and content remain authoritative; no calendar entity is created.';
$GLOBALS['TL_LANG']['schema_ai']['coreEventType'] = 'Contao default (Event)';

$GLOBALS['TL_LANG']['schema_ai']['authorGroup'] = 'Author mapping';

$GLOBALS['TL_LANG']['schema_ai']['authorGroup'] = 'Author mapping';

$GLOBALS['TL_LANG']['schema_ai']['auditTitle'] = 'Existing structured data';

$GLOBALS['TL_LANG']['schema_ai']['auditEntry'] = 'Check existing markup before setup or repeat the migration audit. No API key or AI charges.';

$GLOBALS['TL_LANG']['schema_ai']['auditHelp'] = 'Reads public JSON-LD and compares it with published Schema Manager output. Existing entities are never edited. Only schema-only HTML elements with a verified replacement can be selected for disabling. Unknown origins and differences require manual review.';

$GLOBALS['TL_LANG']['schema_ai']['draftHome'] = '“%s” is published, but its %s translation/home is still unpublished. Its localized details are not emitted.';

$GLOBALS['TL_LANG']['schema_ai']['preserveHelp'] = 'Select all selects additions and empty-field suggestions only. Replacements of existing values must be selected individually after comparing current and proposed values.';

$GLOBALS['TL_LANG']['schema_ai']['auditPrepare'] = 'Prepare audit';

$GLOBALS['TL_LANG']['schema_ai']['auditScan'] = 'Scan public pages';

$GLOBALS['TL_LANG']['schema_ai']['auditRunning'] = 'Checking pages';

$GLOBALS['TL_LANG']['schema_ai']['auditNodes'] = 'schema nodes';

$GLOBALS['TL_LANG']['schema_ai']['auditManaged'] = 'Schema Manager identity';

$GLOBALS['TL_LANG']['schema_ai']['auditAdditional'] = 'Additional/core markup — check origin';

$GLOBALS['TL_LANG']['schema_ai']['auditReplacement'] = 'Possible replacement; missing or differing properties:';

$GLOBALS['TL_LANG']['schema_ai']['auditCovered'] = 'Compared properties are preserved.';

$GLOBALS['TL_LANG']['schema_ai']['auditUnmapped'] = 'No exact HTML content-element source identified. This can be Contao output, a template, an extension or dynamically generated markup.';

$GLOBALS['TL_LANG']['schema_ai']['auditElement'] = 'HTML content element';

$GLOBALS['TL_LANG']['schema_ai']['auditEdit'] = 'Open editor';

$GLOBALS['TL_LANG']['schema_ai']['auditDisabled'] = 'Disabled by this audit.';

$GLOBALS['TL_LANG']['schema_ai']['auditSelect'] = 'Disable this verified legacy schema element';

$GLOBALS['TL_LANG']['schema_ai']['auditRetireHelp'] = 'Nothing is selected automatically. The page and replacement are checked again when applying. Content is disabled, not deleted; restore it in the content editor if needed.';

$GLOBALS['TL_LANG']['schema_ai']['auditRetire'] = 'Disable selected legacy elements';

$GLOBALS['TL_LANG']['schema_ai']['auditRetired'] = '%d legacy elements disabled.';

$GLOBALS['TL_LANG']['schema_ai']['auditData'] = 'Inspect existing data and replacement';

$GLOBALS['TL_LANG']['schema_ai']['stage_import'] = '1. Import existing schema';

$GLOBALS['TL_LANG']['schema_ai']['stage_import_help'] = 'Preserve existing identities before creating anything new.';

$GLOBALS['TL_LANG']['schema_ai']['importHelp'] = 'Start here if this site already has hand-written JSON-LD. We combine repeated definitions, preserve their IDs, map supported fields and retain additional properties. Contao-generated pages, images and articles are not shown as entities to import. This scan uses no AI.';

$GLOBALS['TL_LANG']['schema_ai']['importPrepare'] = 'Prepare import scan';

$GLOBALS['TL_LANG']['schema_ai']['importScan'] = 'Find existing entities';

$GLOBALS['TL_LANG']['schema_ai']['importEmpty'] = 'No legacy entities found. Continue to the foundation step; check any scan warnings first.';

$GLOBALS['TL_LANG']['schema_ai']['import_pending'] = 'Ready for review';

$GLOBALS['TL_LANG']['schema_ai']['import_imported'] = 'Imported draft';

$GLOBALS['TL_LANG']['schema_ai']['import_published'] = 'Published';

$GLOBALS['TL_LANG']['schema_ai']['importSelect'] = 'Select this entity';

$GLOBALS['TL_LANG']['schema_ai']['importMerge'] = 'Fill gaps in existing entity';

$GLOBALS['TL_LANG']['schema_ai']['importIds'] = 'Public IDs kept unchanged';

$GLOBALS['TL_LANG']['schema_ai']['importNoHome'] = 'Home page needs review';

$GLOBALS['TL_LANG']['schema_ai']['importRetained'] = 'Preserved IDs and additional properties';

$GLOBALS['TL_LANG']['schema_ai']['importApply'] = 'Import selected as drafts';

$GLOBALS['TL_LANG']['schema_ai']['importPublish'] = 'Publish selected imports and translations';

$GLOBALS['TL_LANG']['schema_ai']['importPublishHelp'] = 'First import the selected pending items as drafts. Review them, then select the imported items and publish them with their language records. Existing populated fields and identities are never silently replaced.';

$GLOBALS['TL_LANG']['schema_ai']['importApplied'] = '%d items imported. Review the drafts before publishing.';

$GLOBALS['TL_LANG']['schema_ai']['importPublished'] = '%d imports published with their language records.';

$GLOBALS['TL_LANG']['schema_ai']['importRetireTitle'] = 'Finish the handover';

$GLOBALS['TL_LANG']['schema_ai']['importRetireHelp'] = 'After publishing, verify that the manager preserves the original data. Only then disable matching schema-only content elements. Templates and unsupported sources remain a manual task.';

$GLOBALS['TL_LANG']['schema_ai']['importVerify'] = 'Verify imported output';

$GLOBALS['TL_LANG']['schema_ai']['importUnknownOrigin'] = 'No local HTML source found; check the template or extension manually.';

$GLOBALS['TL_LANG']['schema_ai']['importNext'] = 'Continue: complete the foundation';

$GLOBALS['TL_LANG']['schema_ai']['import_conflict'] = 'Needs review';

$GLOBALS['TL_LANG']['schema_ai']['importWebsiteIdentity'] = "Publishing this import replaces the configured website ID with the original ID from the hand-written schema. Website references generated by Schema Manager will use that original ID. References in custom code must be reviewed separately. Importing the draft changes no public output.";

$GLOBALS['TL_LANG']['schema_ai']['importCurrentIdentity'] = "Currently configured website ID";

$GLOBALS['TL_LANG']['schema_ai']['importOriginalIdentity'] = "Original ID to restore";

$GLOBALS['TL_LANG']['schema_ai']['removeLinkAction'] = 'Remove broken link';
$GLOBALS['TL_LANG']['schema_ai']['storedRelationship'] = 'Stored relationship';

$GLOBALS['TL_LANG']['schema_ai']['comparePage'] = 'Page to compare';

$GLOBALS['TL_LANG']['schema_ai']['compareData'] = 'Compare original and published replacement';

$GLOBALS['TL_LANG']['schema_ai']['compareOriginal'] = 'Original handwritten schema';

$GLOBALS['TL_LANG']['schema_ai']['compareReplacement'] = 'Published replacement (excluding this content element)';

$GLOBALS['TL_LANG']['schema_ai']['compareMissing'] = 'No unique published replacement found.';

$GLOBALS['TL_LANG']['schema_ai']['importEmpty'] = 'No new legacy entities to import. You can still compare existing published replacements below. Check scan warnings before continuing.';

$GLOBALS['TL_LANG']['schema_ai']['importVerify'] = 'Compare published output';
