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
$GLOBALS['TL_LANG']['schema_ai']['progress'] = 'Analyzing a small batch…';
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
