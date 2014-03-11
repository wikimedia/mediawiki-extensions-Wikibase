<?php

/**
 * Internationalization file for the WikibaseLib extension.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 */
// @codingStandardsIgnoreFile

$messages = array();

/** English
 * @author Jeroen De Dauw
 * @author Adam Shorland
 */
$messages['en'] = array(
	'wikibase-lib-desc' => 'Holds common functionality for the Wikibase and Wikibase Client extensions',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'property',
	'wikibase-entity-query' => 'query',
	'wikibase-deletedentity-item' => 'Deleted item',
	'wikibase-deletedentity-property' => 'Deleted property',
	'wikibase-deletedentity-query' => 'Deleted query',
	'wikibase-diffview-reference' => 'reference',
	'wikibase-diffview-rank' => 'rank',
	'wikibase-diffview-rank-preferred' => 'Preferred rank',
	'wikibase-diffview-rank-normal' => 'Normal rank',
	'wikibase-diffview-rank-deprecated' => 'Deprecated rank',
	'wikibase-diffview-qualifier' => 'qualifier',
	'wikibase-diffview-label' => 'label',
	'wikibase-diffview-alias' => 'aliases',
	'wikibase-diffview-description' => 'description',
	'wikibase-diffview-link' => 'links',
	'wikibase-error-unexpected' => 'An unexpected error occurred.',
	'wikibase-error-save-generic' => 'An error occurred while trying to perform save and because of this, your changes could not be completed.',
	'wikibase-error-remove-generic' => 'An error occurred while trying to perform remove and because of this, your changes could not be completed.',
	'wikibase-error-save-connection' => 'A connection error has occurred while trying to perform save, and because of this your changes could not be completed. Please check your Internet connection.',
	'wikibase-error-remove-connection' => 'A connection error occurred while trying to perform remove, and because of this your changes could not be completed. Please check your Internet connection.',
	'wikibase-error-save-timeout' => 'We are experiencing technical difficulties, and because of this your "save" could not be completed.',
	'wikibase-error-remove-timeout' => 'We are experiencing technical difficulties, and because of this your "remove" could not be completed.',
	'wikibase-error-autocomplete-connection' => 'Could not query site API. Please try again later.',
	'wikibase-error-autocomplete-response' => 'Server responded: $1',
	'wikibase-error-ui-client-error' => 'The connection to the client page failed. Please try again later.',
	'wikibase-error-ui-no-external-page' => 'The specified article could not be found on the corresponding site.',
	'wikibase-error-ui-cant-edit' => 'You are not allowed to perform this action.',
	'wikibase-error-ui-no-permissions' => 'You do not have sufficient rights to perform this action.',
	'wikibase-error-ui-link-exists' => 'You cannot link to this page because another item already links to it.',
	'wikibase-error-ui-session-failure' => 'Your session has expired. Please log in again.',
	'wikibase-error-ui-edit-conflict' => 'There is an edit conflict. Please reload and save again.',
	'wikibase-quantitydetails-amount' => 'Amount',
	'wikibase-quantitydetails-upperbound' => 'Upper bound',
	'wikibase-quantitydetails-lowerbound' => 'Lower bound',
	'wikibase-quantitydetails-unit' => 'Unit',
	'wikibase-timedetails-time' => 'Time',
	'wikibase-timedetails-isotime' => 'ISO timestamp',
	'wikibase-timedetails-timezone' => 'Timezone',
	'wikibase-timedetails-calendar' => 'Calendar',
	'wikibase-timedetails-precision' => 'Precision',
	'wikibase-timedetails-before' => 'Before',
	'wikibase-timedetails-after' => 'After',
	'wikibase-globedetails-longitude' => 'Longitude',
	'wikibase-globedetails-latitude' => 'Latitude',
	'wikibase-globedetails-precision' => 'Precision',
	'wikibase-globedetails-globe' => 'Globe',
	'wikibase-replicationnote' => 'Please notice that it can take several minutes until the changes are visible on all wikis.',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia pages linked to this item',
	'wikibase-sitelinks-sitename-columnheading' => 'Language',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Linked page',
	'wikibase-tooltip-error-details' => 'Details',
	'wikibase-undeserializable-value' => 'The value is invalid and cannot be displayed.',
	'wikibase-validator-bad-type' => '$2 instead of $1',
	'wikibase-validator-too-long' => 'Must be no more than {{PLURAL:$1|one character|$1 characters}} long',
	'wikibase-validator-too-short' => 'Must be at least {{PLURAL:$1|one character|$1 characters}} long',
	'wikibase-validator-too-high' => 'Out of range, must be no higher than $1',
	'wikibase-validator-too-low' => 'Out of range, must be no lower than $1',
	'wikibase-validator-malformed-value' => 'Malformed input: $1',
	'wikibase-validator-bad-entity-id' => 'Malformed ID: $1',
	'wikibase-validator-bad-entity-type' => 'Unexpected entity type $1',
	'wikibase-validator-no-such-entity' => '$1 not found',
	'wikibase-validator-no-such-property' => 'Property $1 not found',
	'wikibase-validator-bad-value' => 'Illegal value: $1',
	'wikibase-validator-bad-value-type' => 'Bad value type $1, expected $2',
	'wikibase-validator-bad-url' => 'Malformed URL: $1', //FIXME: make sure $1 is escaped!
	'wikibase-validator-bad-url-scheme' => 'Unsupported URL scheme: $1',
	'wikibase-validator-bad-http-url' => 'Malformed HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Malformed mailto URL: $1',
	'wikibase-validator-unknown-unit' => 'Unknown unit: $1',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Commons media file',
	'version-wikibase' => 'Wikibase',

	// TimeFormats
	'wikibase-time-precision-Gannum' => 'in $1 billion years',
	'wikibase-time-precision-Mannum' => 'in $1 million years',
	'wikibase-time-precision-annum' => 'in $1 years',
	'wikibase-time-precision-millennium' => '$1. millennium',
	'wikibase-time-precision-century' => '$1. century',
	'wikibase-time-precision-10annum' => '$1s',

	'wikibase-time-precision-BCE-Gannum' => '$1 billion years ago',
	'wikibase-time-precision-BCE-Mannum' => '$1 million years ago',
	'wikibase-time-precision-BCE-annum' => '$1 years ago',
	'wikibase-time-precision-BCE-millennium' => '$1. millennium BCE',
	'wikibase-time-precision-BCE-century' => '$1. century BCE',
	'wikibase-time-precision-BCE-10annum' => '$1s BCE',
);

/** Message documentation (Message documentation)
 * @author Amire80
 * @author Jeblad
 * @author Lloffiwr
 * @author Metalhead64
 * @author Nemo bis
 * @author Nnemo
 * @author Raymond
 * @author Shirayuki
 * @author Siebrand
 * @author Waldir
 */
$messages['qqq'] = array(
	'wikibase-lib-desc' => '{{desc|name=Wikibase Lib|url=http://www.mediawiki.org/wiki/Extension:WikibaseLib}}',
	'wikibase-entity-item' => "How we refer to entities of type item. See also Wikidata's glossary on [[d:Wikidata:Glossary#item|item]].
{{Identical|Item}}",
	'wikibase-entity-property' => 'How we refer to entities of type property. See also Wikidatas glossary on [[d:Wikidata:Glossary#entity|entity]].
{{Identical|Property}}',
	'wikibase-entity-query' => 'How we refer to entities of type query. See also Wikidatas glossary on [[d:Wikidata:Glossary#entity|entity]].
{{Identical|Query}}',
	'wikibase-deletedentity-item' => "Message displayed instead of an Item's label if the Item has been deleted (see [[d:Wikidata:Glossary]]).

See also:
* {{msg-mw|Wikibase-entity-item}}
* {{msg-mw|Wikibase-deletedentity-property}}
* {{msg-mw|Wikibase-deletedentity-query}}",
	'wikibase-deletedentity-property' => "Message displayed instead of an Property's label if the Property has been deleted (see [[d:Wikidata:Glossary]]).

See also:
* {{msg-mw|Wikibase-entity-property}}
* {{msg-mw|Wikibase-deletedentity-item}}
* {{msg-mw|Wikibase-deletedentity-query}}",
	'wikibase-deletedentity-query' => "Message displayed instead of an Query's label if the Query has been deleted (see [[d:Wikidata:Glossary]]).

See also:
* {{msg-mw|Wikibase-entity-query}}
* {{msg-mw|Wikibase-deletedentity-item}}
* {{msg-mw|Wikibase-deletedentity-property}}",
	'wikibase-diffview-reference' => 'Label within the header of a diff-operation on the entity diff view to describe that the diff-operation affects a reference. Will be shown as e.g. "claim / property q1 / reference".
{{Identical|Reference}}',
	'wikibase-diffview-rank' => 'Label within the header of a diff-operation on the entity diff view to describe that the diff-operation affects the rank of the statement. Will be shown as e.g. "claim / property q1 / rank".
{{Identical|Rank}}',
	'wikibase-diffview-rank-preferred' => 'The [[d:Wikidata:Glossary#Rank-preferred|Preferred Rank]] to be shown in diffs.',
	'wikibase-diffview-rank-normal' => 'The [[d:Wikidata:Glossary#Rank-normal|Normal Rank]] to be shown in diffs.',
	'wikibase-diffview-rank-deprecated' => 'The [[d:Wikidata:Glossary#Rank-deprecated|Deprecated Rank]] to be shown in diffs.',
	'wikibase-diffview-qualifier' => 'Label within the header of a diff-operation on the entity diff view to describe that the diff-operation affects a qualifier. Will be shown as e.g. "claim / property q1 / qualifier".',
	'wikibase-diffview-label' => 'Sub heading for label changes in a diff.
{{Identical|Label}}',
	'wikibase-diffview-alias' => 'Sub heading for alias changes in a diff
{{Identical|Alias}}',
	'wikibase-diffview-description' => 'Sub heading for description changes in a diff.
{{Identical|Description}}',
	'wikibase-diffview-link' => 'Sub heading for link changes in a diff.
{{Identical|Link}}',
	'wikibase-error-unexpected' => 'Error message that is used as a fallback message if no other message can be assigned to the error that occurred. This error message being displayed should never happen. However, there may be "unexpected" errors not covered by the implemented error handling.',
	'wikibase-error-save-generic' => 'Generic error message for an error happening during a save operation.',
	'wikibase-error-remove-generic' => 'Generic error message for an error happening during a remove operation',
	'wikibase-error-save-connection' => 'Error message for an error happening during a save operation. The error might most likely be caused by a connection problem.',
	'wikibase-error-remove-connection' => 'Error message for an error happening during a remove operation. The error might most likely be caused by a connection problem.',
	'wikibase-error-save-timeout' => 'Error message for an error happening during a save operation. The error was caused by a request time out.',
	'wikibase-error-remove-timeout' => 'Error message for an error happening during a remove operation. The error was caused by a request time out.',
	'wikibase-error-autocomplete-connection' => 'Error message for page auto-complete input box; displayed when API could not be reached.',
	'wikibase-error-autocomplete-response' => 'When querying the API for auto-completion fails, this message contains more detailed information about the error. $1 is the actual server error response or jQuery error code (e.g. when the server did not respond).',
	'wikibase-error-ui-client-error' => 'This is a human readable version of the API error "wikibase-api-client-error" which is shown in the UI.',
	'wikibase-error-ui-no-external-page' => 'This is a human readable version of the API error "wikibase-api-no-external-page" which is shown in the UI.',
	'wikibase-error-ui-cant-edit' => 'This is a human readable version of the API error "wikibase-api-cant-edit" which is shown in the UI.',
	'wikibase-error-ui-no-permissions' => 'This is a human readable version of the API error "wikibase-api-no-permission" which is shown in the UI.',
	'wikibase-error-ui-link-exists' => 'This is a human readable version of the API error "wikibase-api-link-exists" which is shown in the UI.',
	'wikibase-error-ui-session-failure' => 'This is a human readable version of the API error "wikibase-api-session-failure" which is shown in the UI.',
	'wikibase-error-ui-edit-conflict' => 'This is a human readable version of the API error "edit-conflict" which is shown in the UI.
Note that the default message says the user shall "reload and save", but after a reload the content that should be saved will be lost.',
	'wikibase-quantitydetails-amount' => 'Label used for the "amount" field of a quantity value when showing a detailed representation of the quantity, e.g. in a diff.
{{Identical|Amount}}',
	'wikibase-quantitydetails-upperbound' => 'Label used for the "upper bound" field of a quantity value when showing a detailed representation of the quantity, e.g. in a diff.',
	'wikibase-quantitydetails-lowerbound' => 'Label used for the "lower bound" field of a quantity value when showing a detailed representation of the quantity, e.g. in a diff.',
	'wikibase-quantitydetails-unit' => 'Label used for the "unit" field of a quantity value when showing a detailed representation of the quantity, e.g. in a diff.
{{Identical|Unit}}',
	'wikibase-timedetails-time' => 'Label used for the rendered version of a time value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-timedetails-isotime' => 'Label used for the "isotime" field of a time value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-timedetails-timezone' => 'Label used for the "timezone" field of a time value when showing a detailed representation of the time, e.g. in a diff.
{{Identical|Time zone}}',
	'wikibase-timedetails-calendar' => 'Label used for the "calendar" field of a time value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-timedetails-precision' => 'Label used for the "precision" field of a time value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-timedetails-before' => 'Label used for the "before" field of a time value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-timedetails-after' => 'Label used for the "after" field of a time value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-globedetails-longitude' => 'Label used for the "longitude" field of a globecoordinate value when showing a detailed representation of the time, e.g. in a diff.
{{Identical|Longitude}}',
	'wikibase-globedetails-latitude' => 'Label used for the "latitude" field of a globecoordinate value when showing a detailed representation of the time, e.g. in a diff.
{{Identical|Latitude}}',
	'wikibase-globedetails-precision' => 'Label used for the "precision" field of a globecoordinate value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-globedetails-globe' => 'Label used for the "globe" field of a globecoordinate value when showing a detailed representation of the time, e.g. in a diff.',
	'wikibase-replicationnote' => 'Note telling the user that it can take a few minutes until the made changes are visible on all wikis.
Preceded by message {{msg-mw|Wikibase-linkitem-success-link}}',
	'wikibase-sitelinks-wikipedia' => '[[File:Screenshot WikidataRepo 2012-05-13 A.png|right|0x150px]]
Header messages for pages on different Wikipedias linked to this item. Similar messages can be created for each group of target sites, depending on configuration.
See also Wikidatas glossary for [[d:Wikidata:Glossary#sitelinks|site links]] and [[d:Wikidata:Glossary#Item|item]].
{{Related|Wikibase-sitelinks}}',
	'wikibase-sitelinks-sitename-columnheading' => 'Site links table column heading for the column containing the language names.
{{Identical|Language}}',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site links table column heading for the sitename column for special sites such as e.g. Commons.
{{Identical|Site}}',
	'wikibase-sitelinks-siteid-columnheading' => 'Site links table column heading for the column containing the language codes.
{{Identical|Code}}',
	'wikibase-sitelinks-link-columnheading' => 'Site links table column heading for the column containg the title/link of/to the referenced (wiki) page.
{{Identical|Linked page}}',
	'wikibase-tooltip-error-details' => 'Link within an error tooltip that will unfold additional information regarding the error (i.e. the more specific error message returned from the underlying API).
{{Identical|Detail}}',
	'wikibase-undeserializable-value' => 'Message to display for any data values that are invalid and cannot be deserialized.  The message is displayed in such places as when users view a diff.',
	'wikibase-validator-bad-type' => 'Input validation error shown when the input has the wrong type.

Parameters:
* $1 - the expected type
* $2 - the actual type
{{Related|Wikibase-validator}}',
	'wikibase-validator-too-long' => 'Input validation error shown when the input is too long. Parameters:
* $1 - the maximum length
* $2 - (Unused) the actual length
{{Related|Wikibase-validator}}',
	'wikibase-validator-too-short' => 'Input validation error shown when the input is too short. Parameters:
* $1 - the minimum length
* $2 - (Unused) the actual length
{{Related|Wikibase-validator}}',
	'wikibase-validator-too-high' => 'Input validation error shown when the input is too high. Parameters:
* $1 - the maximum value
* $2 - (Unused) the actual length
{{Related|Wikibase-validator}}',
	'wikibase-validator-too-low' => 'Input validation error shown when the input is too low. Parameters:
* $1 - the minimum value
* $2 - (Unused) the actual length
{{Related|Wikibase-validator}}',
	'wikibase-validator-malformed-value' => "Input validation error shown when the user's input was malformed in some way.

Parameters:
* $1 - the malformed input
{{Related|Wikibase-validator}}",
	'wikibase-validator-bad-entity-id' => 'Input validation error shown when the entity ID given by the user is malformed.

Parameters:
* $1 - the malformed entity ID
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-entity-type' => 'Input validation error shown when the entity specified by the user was not of the correct type.

Parameters:
* $1 - the actual type
{{Related|Wikibase-validator}}',
	'wikibase-validator-no-such-entity' => 'Input validation error shown when the entity specified by the user was not found.

Parameters:
* $1 - the entity ID
{{Related|Wikibase-validator}}',
	'wikibase-validator-no-such-property' => 'Input validation error shown when the property used for a statement could not be found.

This kind of error is unlikely to occur during normal operation, since the user interface should prevent illegal values from being entered.

Parameters:
* $1 - the property ID
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-value' => 'Input validation error shown when the value is syntactically or structurally invalid.

This kind of error is unlikely to occur during normal operation, since the user interface should prevent illegal values from being entered.

Parameters:
* $1 - the technical error message describing the problem
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-value-type' => 'Input validation error shown when the value has the wrong type for the property it is applied to.

This kind of error is unlikely to occur during normal operation, since the user interface should prevent illegal values from being entered.

Parameters:
* $1 - the actual value type
* $2 - the expected value type
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-url' => 'Input validation error shown when the value is an invalid URL.

Parameters:
* $1 - the malformed URL
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-url-scheme' => 'Input validation error shown when the value is a URL using an unsupported protocol (scheme).

Parameters:
* $1 - the scheme name
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-http-url' => 'Input validation error shown when the value is an HTTP or HTTPS URL.

Parameters:
* $1 - the malformed URL
{{Related|Wikibase-validator}}',
	'wikibase-validator-bad-mailto-url' => 'Input validation error shown when the value is an invalid "mailto" URL.

Parameters:
* $1 - the malformed URL
{{Related|Wikibase-validator}}',
	'wikibase-validator-unknown-unit' => 'Input validation error when the value has an unknown unit.

Parameters:
* $1 - the unknown unit
{{Related|Wikibase-validator}}',
	'datatypes-type-wikibase-item' => 'The name of a data type for items in Wikibase.
{{Identical|Item}}',
	'datatypes-type-commonsMedia' => 'The name of a data type for media files on Wikimedia Commons (proper name, capitalised in English; first letter capitalised anyway in this message and relatives).',
	'version-wikibase' => 'Name of the Wikibase extension collection, used on [[Special:Version]]',
	'wikibase-time-precision-Gannum' => '!!DO NOT TRANSLATE!! Used to present a point in time with the precession of 1 billion of years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-Mannum' => '!!DO NOT TRANSLATE!! Used to present a point in time with the precession of 1 million of years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-annum' => '!!DO NOT TRANSLATE!! Used to present a point in time with the precession of 10000 years to 100000 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-millennium' => '!!DO NOT TRANSLATE!! Used to present a point in time with the precession of 1000 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-century' => '!!DO NOT TRANSLATE!! Used to present a point in time with the precession of 100 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-10annum' => '!!DO NOT TRANSLATE!! Used to present a point in time with the precession of 10 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-BCE-Gannum' => '!!DO NOT TRANSLATE!! Used to present a point in time BCE (before current era) with the precession of 1 billion of years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-BCE-Mannum' => '!!DO NOT TRANSLATE!! Used to present a point in time BCE (before current era) with the precession of 1 million of years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-BCE-annum' => '!!DO NOT TRANSLATE!! Used to present a point in time BCE (before current era) with the precession of 10000 years to 100000 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-BCE-millennium' => '!!DO NOT TRANSLATE!! Used to present a point in time BCE (before current era) with the precession of 1000 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-BCE-century' => '!!DO NOT TRANSLATE!! Used to present a point in time BCE (before current era) with the precession of 100 years
{{Related|Wikibase-time-precision}}',
	'wikibase-time-precision-BCE-10annum' => '!!DO NOT TRANSLATE!! Used to present a point in time BCE (before current era) with the precession of 10 years
{{Related|Wikibase-time-precision}}',
);

/** Afrikaans (Afrikaans)
 * @author Naudefj
 * @author Robby
 */
$messages['af'] = array(
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'eienskap',
	'wikibase-entity-query' => 'soekopdrag',
	'wikibase-diffview-reference' => 'verwysing',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-qualifier' => 'kwalifiseerder',
	'wikibase-diffview-alias' => 'aliasse',
	'wikibase-error-unexpected' => "'n Onverwagte fout het voorgekom.",
	'wikibase-error-autocomplete-connection' => 'Die Wikipedia-API kon die bereik word nie. Probeer asseblief later weer.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Antwoord van bediener: $1',
	'wikibase-error-ui-client-error' => 'Die verbinding na die eksterne bladsy het gefaal. Probeer asseblief later weer.',
	'wikibase-error-ui-no-external-page' => 'Die gespesifiseerde bladsy kon nie op die ooreenkomende webwerf gevind word nie.',
	'wikibase-error-ui-cant-edit' => 'U mag nie hierdie handeling uitvoer nie.',
	'wikibase-error-ui-no-permissions' => 'U het nie die nodige regte om hierdie handeling uit te voer nie.',
	'wikibase-error-ui-link-exists' => "U kan nie na die bladsy skakel nie omdat 'n ander item reeds hieraan gekoppel is.",
	'wikibase-error-ui-session-failure' => 'U sessie het uitgeloop. Meld asseblief weer aan.',
	'wikibase-error-ui-edit-conflict' => 'Twee wysigings bots met mekaar. Laai asseblief oor en stoor weer.',
	'wikibase-replicationnote' => "Let daarop dat dit verskeie minute dan duur voor die wysigings op alle wiki's sigbaar sal wees.",
	'wikibase-sitelinks-sitename-columnheading' => 'Taal',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Geskakelde artikel', # Fuzzy
	'wikibase-tooltip-error-details' => 'Details',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Medialêer van Commons',
	'version-wikibase' => 'Wikibase',
);

/** Arabic (العربية)
 * @author Asaifm
 * @author Gagnabil
 * @author Meno25
 * @author زكريا
 * @author مشعل الحربي
 */
$messages['ar'] = array(
	'wikibase-entity-item' => 'عنصر',
	'wikibase-entity-property' => 'خاصية',
	'wikibase-entity-query' => 'طلب',
	'wikibase-deletedentity-item' => 'عنصر محذوف',
	'wikibase-deletedentity-property' => 'خاصية محذوفة',
	'wikibase-deletedentity-query' => 'طلب محذوف',
	'wikibase-diffview-reference' => 'مرجع',
	'wikibase-diffview-qualifier' => 'صفة',
	'wikibase-diffview-label' => 'علامة',
	'wikibase-diffview-alias' => 'أسماء أخرى',
	'wikibase-diffview-description' => 'وصف',
	'wikibase-diffview-link' => 'وصلات',
	'wikibase-error-unexpected' => 'وقع خطأ غير متوقع.',
	'wikibase-error-save-generic' => 'وقع خطأ عند الحفظ فلم تحفظ تعديلاتك.',
	'wikibase-error-remove-generic' => 'وقع خطأ عند الحذف ولم تحفظ تعديلاتك.',
	'wikibase-error-save-connection' => 'وقع خطأ في الاتصال عند الحفظ فلم تحفظ تعديلاتك. تحقق من الاتصال بالإنترنت.',
	'wikibase-error-remove-connection' => 'وقع خطأ في الاتصال عند الحذف فلم تحفظ تعديلاتك. تحقق من الاتصال بالإنترنت.',
	'wikibase-error-save-timeout' => 'يشهد الموقع صعوبات تقنية حاليا فلم ينفذ الحفظ.',
	'wikibase-error-remove-timeout' => 'يشهد الموقع صعوبات تقنية حاليا فلم ينفذ الحذف.',
	'wikibase-timedetails-time' => 'زمن',
	'wikibase-timedetails-isotime' => 'الطابع الزمني ISO',
	'wikibase-timedetails-timezone' => 'المنطقة الزمنية',
	'wikibase-timedetails-calendar' => 'التقويم',
	'wikibase-timedetails-precision' => 'الدقة',
	'wikibase-timedetails-before' => 'قبل',
	'wikibase-timedetails-after' => 'بعد',
	'wikibase-globedetails-longitude' => 'خطّ الطول',
	'wikibase-globedetails-latitude' => 'خطّ العرض',
	'wikibase-globedetails-precision' => 'الدقة',
	'wikibase-replicationnote' => 'قد يلزم وقت لتظهر التعديلات في جميع الويكيات.',
	'wikibase-sitelinks-sitename-columnheading' => 'اللغة',
	'wikibase-sitelinks-sitename-columnheading-special' => 'الموقع',
	'wikibase-tooltip-error-details' => 'التفاصيل',
	'wikibase-validator-bad-type' => '$2 عوضا عن $1',
	'wikibase-validator-unknown-unit' => 'وحدة غير معروفة: $1',
	'datatypes-type-wikibase-item' => 'عنصر',
	'version-wikibase' => 'قاعدة ويكي',
);

/** Asturian (asturianu)
 * @author Xuacu
 */
$messages['ast'] = array(
	'wikibase-lib-desc' => 'Contién les funciones comúnes pa les estensiones Wikibase y Wikibase Client.',
	'wikibase-entity-item' => 'elementu',
	'wikibase-entity-property' => 'propiedá',
	'wikibase-entity-query' => 'consulta',
	'wikibase-deletedentity-item' => 'Elementu desaniciáu',
	'wikibase-deletedentity-property' => 'Propiedá desaniciada',
	'wikibase-deletedentity-query' => 'Consulta desaniciada',
	'wikibase-diffview-reference' => 'referencia',
	'wikibase-diffview-rank' => 'rangu',
	'wikibase-diffview-rank-preferred' => 'Rangu preferíu',
	'wikibase-diffview-rank-normal' => 'Rangu normal',
	'wikibase-diffview-rank-deprecated' => 'Rangu anticuáu',
	'wikibase-diffview-qualifier' => 'calificador',
	'wikibase-diffview-label' => 'etiqueta',
	'wikibase-diffview-alias' => 'alcuños',
	'wikibase-diffview-description' => 'descripción',
	'wikibase-diffview-link' => 'enllaces',
	'wikibase-error-unexpected' => 'Hebo un fallu inesperáu.',
	'wikibase-error-save-generic' => 'Hebo un error al intentar el guardáu y, por eso, nun pudieron completase los cambios.',
	'wikibase-error-remove-generic' => 'Hebo un error al intentar el desaniciu y, por eso, nun pudieron completase los cambios.',
	'wikibase-error-save-connection' => 'Hebo un error de conexón al intentar el guardáu y, por eso, nun pudieron completase los cambios. Por favor, compruebe la so conexón a Internet.',
	'wikibase-error-remove-connection' => 'Hebo un error de conexón al intentar el desaniciu y, por eso, nun pudieron completase los cambios. Por favor, compruebe la so conexón a Internet.',
	'wikibase-error-save-timeout' => 'Tamos teniendo dificultaes téuniques, y por eso nun se pudo completar el guardáu.',
	'wikibase-error-remove-timeout' => 'Tamos teniendo dificultaes téuniques, y por eso nun se pudo completar el desaniciu.',
	'wikibase-error-autocomplete-connection' => 'Nun pudo consultase la API del sitiu. Por favor, vuelva a intentalo más sero.',
	'wikibase-error-autocomplete-response' => 'El sirvidor respondió: $1',
	'wikibase-error-ui-client-error' => 'Falló la conexón cola páxina del cliente. Por favor, vuelva a intentalo más sero.',
	'wikibase-error-ui-no-external-page' => "Nun pudo alcontrase l'artículu especificáu nel sitiu correspondiente.",
	'wikibase-error-ui-cant-edit' => 'Nun tien permisu pa facer esta aición.',
	'wikibase-error-ui-no-permissions' => 'Nun tien permisos bastantes pa facer esta aición.',
	'wikibase-error-ui-link-exists' => 'Nun pue enllazar con esta páxina porque otru elementu yá enllaza con ella.',
	'wikibase-error-ui-session-failure' => 'Caducó la sesión. Vuelva a aniciar sesión.',
	'wikibase-error-ui-edit-conflict' => "Hai un conflictu d'edición. Recargue la páxina y vuelva a guardar.",
	'wikibase-quantitydetails-amount' => 'Cantidá',
	'wikibase-quantitydetails-upperbound' => 'Máximu',
	'wikibase-quantitydetails-lowerbound' => 'Mínimu',
	'wikibase-quantitydetails-unit' => 'Unidá',
	'wikibase-replicationnote' => 'Tenga en cuenta que puen pasar dellos minutos fasta que los cambeos se vean en toles wikis',
	'wikibase-sitelinks-wikipedia' => 'Páxines de Wikipedia enllazaes con esti elementu',
	'wikibase-sitelinks-sitename-columnheading' => 'Llingua',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sitiu',
	'wikibase-sitelinks-siteid-columnheading' => 'Códigu',
	'wikibase-sitelinks-link-columnheading' => 'Páxina enllazada',
	'wikibase-tooltip-error-details' => 'Detalles',
	'wikibase-undeserializable-value' => 'El valor ye inválidu y nun se pue amosar.',
	'wikibase-validator-bad-type' => '$2 en llugar de $1',
	'wikibase-validator-too-long' => "Nun pue tener más {{PLURAL:$1|d'un carácter|de $1 carácteres}} de llargo",
	'wikibase-validator-too-short' => "Tien de ser polo menos {{PLURAL:$1|d'un carácter|de $1 carácteres}} de llargo",
	'wikibase-validator-too-high' => 'Fuera de rangu; nun pue ser mayor que $1',
	'wikibase-validator-too-low' => 'Fuera de rangu; nun pue ser menor que $1',
	'wikibase-validator-malformed-value' => 'Entrada con mal formatu: $1',
	'wikibase-validator-bad-entity-id' => 'ID con mal formatu: $1',
	'wikibase-validator-bad-entity-type' => "Tipu inesperáu d'entidá, $1",
	'wikibase-validator-no-such-entity' => "Nun s'alcontró $1",
	'wikibase-validator-no-such-property' => "Nun s'alcontró la propiedá $1",
	'wikibase-validator-bad-value' => 'Valor illegal: $1',
	'wikibase-validator-bad-value-type' => 'Tipu de valor incorreutu $1, esperábase $2',
	'wikibase-validator-bad-url' => 'URL con mal formatu: $1',
	'wikibase-validator-bad-url-scheme' => "Esquema d'URL ensin encontu: $1",
	'wikibase-validator-bad-http-url' => 'URL HTTP con mal formatu: $1',
	'wikibase-validator-bad-mailto-url' => 'URL de corréu con mal formatu: $1',
	'wikibase-validator-unknown-unit' => 'Unidá desconocida: $1',
	'datatypes-type-wikibase-item' => 'Elementu',
	'datatypes-type-commonsMedia' => 'Ficheru multimedia de Commons',
	'version-wikibase' => 'Wikibase',
);

/** Belarusian (беларуская)
 * @author Чаховіч Уладзіслаў
 */
$messages['be'] = array(
	'wikibase-lib-desc' => 'Утрымлівае агульны функцыянал пашырэнняў Wikibase і Wikibase Client.',
	'wikibase-entity-item' => "аб'ект",
	'wikibase-entity-property' => 'уласцівасць',
	'wikibase-entity-query' => 'запыт',
	'wikibase-diffview-reference' => 'крыніца',
	'wikibase-diffview-rank-deprecated' => 'Нерэкамендаваны ранг',
	'wikibase-error-unexpected' => 'Узнікла нечаканая памылка.',
	'wikibase-error-save-generic' => 'Падчас спробы захавання адбылася памылка, з-за чаго змены не былі ўнесеныя цалкам.',
	'wikibase-error-remove-generic' => 'Падчас спробы выдалення адбылася памылка, з-за чаго змены не былі ўнесеныя цалкам.',
	'wikibase-error-save-connection' => 'Падчас спробы захавання адбылася памылка злучэння, з-за чаго вашыя змены не былі захаваны. Калі ласка, праверце ваша злучэнне з Інтэрнэтам.',
	'wikibase-error-remove-connection' => 'Пры выдаленні ўзнікла памылка сувязі, з-за гэтага вашы змены маглі не захавацца. Калі ласка, праверце ваша злучэнне з Інтэрнэтам.',
	'wikibase-error-save-timeout' => 'Мы маем тэхнічныя праблемы, з-за чаго немагчыма завершыць захаванне.',
	'wikibase-error-remove-timeout' => 'Мы маем тэхнічныя праблемы, з-за чаго немагчыма завершыць выдаленне.',
	'wikibase-error-autocomplete-connection' => 'Не атрымалася запытаць Wikipedia API. Калі ласка, паспрабуйце пазней.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Адказ сервера: $1',
	'wikibase-error-ui-client-error' => 'Знікла сувязь з кліенцкай старонкай. Калі ласка, паспрабуйце пазней.',
	'wikibase-error-ui-no-external-page' => 'Не атрымалася знайсці ўказаную старонку на адпаведным праекце.',
	'wikibase-error-ui-cant-edit' => 'Вы не можаце выканаць гэта дзеянне.',
	'wikibase-error-ui-no-permissions' => 'У вас не хапае правоў для выканання гэтага дзеяння.',
	'wikibase-error-ui-link-exists' => "Вы не можаце спаслацца на гэту старонку, бо іншы аб'ект ужо на яе спасылаецца.",
	'wikibase-error-ui-session-failure' => 'Вашая сесія скончылася. Калі ласка, увайдзіце ў сістэму зноў.',
	'wikibase-error-ui-edit-conflict' => 'Адбыўся канфлікт правак. Калі ласка, абнавіце старонку і захавайце зноў.',
	'wikibase-replicationnote' => 'Калі ласка, звернеце ўвагу, што можа прайсці некалькі хвілін, пакуль змены стануць бачнымі ва ўсіх вікі-праектах',
	'wikibase-sitelinks-sitename-columnheading' => 'Мова',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Злучаны артыкул', # Fuzzy
	'wikibase-tooltip-error-details' => 'Падрабязнасці',
	'datatypes-type-wikibase-item' => 'Элемент',
	'datatypes-type-commonsMedia' => 'Медыяфайл з Вікісховішча',
);

/** Belarusian (Taraškievica orthography) (беларуская (тарашкевіца)‎)
 * @author Wizardist
 * @author Zedlik
 */
$messages['be-tarask'] = array(
	'wikibase-lib-desc' => 'Утрымлівае агульны функцыянал пашырэньняў Wikibase і Wikibase Client.',
	'wikibase-entity-item' => 'аб’ект',
	'wikibase-entity-property' => 'уласьцівасьць',
	'wikibase-entity-query' => 'запыт',
	'wikibase-deletedentity-item' => 'Выдалены аб’ект',
	'wikibase-deletedentity-property' => 'Выдаленая ўласьцівасьць',
	'wikibase-deletedentity-query' => 'Выдалены запыт',
	'wikibase-diffview-reference' => 'крыніца',
	'wikibase-diffview-rank' => 'ранг',
	'wikibase-diffview-qualifier' => 'кваліфікатар',
	'wikibase-diffview-label' => 'метка',
	'wikibase-diffview-alias' => 'сынонімы',
	'wikibase-diffview-description' => 'апісаньне',
	'wikibase-diffview-link' => 'спасылкі',
	'wikibase-error-unexpected' => 'Узьнікла нечаканая памылка.',
	'wikibase-error-save-generic' => 'У час спробы захаваньня адбылася памылка, з-за чаго зьмены не былі ўнесеныя цалкам.',
	'wikibase-error-remove-generic' => 'У час спробы выдаленьня адбылася памылка, з-за чаго зьмены не былі ўнесеныя цалкам.',
	'wikibase-error-save-connection' => 'У час спробы захаваньня адбылася памылка злучэньня, з-за чаго вашыя зьмены не былі захаваныя. Калі ласка, праверце вашае злучэньне з Інтэрнэтам.',
	'wikibase-error-remove-connection' => 'Пры выдаленьні ўзьнікла памылка сувязі, з-за гэтага вашыя зьмены маглі не захавацца. Праверце вашае злучэньне з Інтэрнэтам, калі ласка.',
	'wikibase-error-save-timeout' => 'Мы маем тэхнічныя праблемы, з-за чаго немагчыма завершыць захаваньне.',
	'wikibase-error-remove-timeout' => 'Мы маем тэхнічныя праблемы, з-за чаго немагчыма завершыць выдаленьне.',
	'wikibase-error-autocomplete-connection' => 'Не атрымалася запытаць API сайта. Калі ласка, паспрабуйце пазьней.',
	'wikibase-error-autocomplete-response' => 'Адказ сэрвэра: $1',
	'wikibase-error-ui-client-error' => 'Зьнікла сувязь з кліенцкай старонкай. Паспрабуйце пазьней, калі ласка.',
	'wikibase-error-ui-no-external-page' => 'Пазначаны артыкул на адпаведным сайце ня знойдзены.',
	'wikibase-error-ui-cant-edit' => 'Вам не дазволена выканаць гэтае дзеяньне.',
	'wikibase-error-ui-no-permissions' => 'Вам бракуе правоў для гэтага дзеяньня.',
	'wikibase-error-ui-link-exists' => 'Вы ня можаце спаслацца на гэтую старонку, бо іншы аб’ект ужо на яе спасылаецца.',
	'wikibase-error-ui-session-failure' => 'Вашая сэсія скончылася. Увайдзіце ў сыстэму зноў, калі ласка.',
	'wikibase-error-ui-edit-conflict' => 'Адбыўся канфлікт рэдагаваньня. Абнавіце старонку і захавайце зноў, калі ласка.',
	'wikibase-replicationnote' => 'Калі ласка заўважце, што зьмены могуць зьявіцца ў вікі-праектах толькі празь некалькі хвілін.',
	'wikibase-sitelinks-wikipedia' => 'Старонкі Вікіпэдыі, далучаныя да гэтага аб’екта',
	'wikibase-sitelinks-sitename-columnheading' => 'Мова',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Злучаны артыкул', # Fuzzy
	'wikibase-tooltip-error-details' => 'Падрабязнасьці',
	'wikibase-validator-bad-type' => '$2 замест $1',
	'wikibase-validator-too-long' => 'Ня мусіць перавышаць даўжынёй $1 {{PLURAL:$1|сымбаль|сымбаля|сымбаляў}}',
	'wikibase-validator-too-short' => 'Мусіць быць даўжынёй прынамсі $1 {{PLURAL:$1|сымбаль|сымбаля|сымбаляў}}',
	'wikibase-validator-malformed-value' => 'Няслушны ўвод: $1',
	'wikibase-validator-bad-entity-id' => 'Няслушны ідэнтыфікатар: $1',
	'wikibase-validator-bad-entity-type' => 'Нечаканы тып элемэнта $1',
	'wikibase-validator-no-such-entity' => '$1 ня знойдзены',
	'wikibase-validator-no-such-property' => 'Уласьцівасьць $1 ня знойдзеная',
	'wikibase-validator-bad-value' => 'Недапушчальнае значэньне: $1',
	'wikibase-validator-bad-value-type' => 'Благі тып значэньня $1, чакаецца $2',
	'wikibase-validator-bad-url' => 'Некарэктны URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Схема URL не падтрымліваецца: $1',
	'wikibase-validator-bad-http-url' => 'Некарэктны HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Некарэктны mailto URL: $1',
	'datatypes-type-wikibase-item' => 'Аб’ект',
	'datatypes-type-commonsMedia' => 'Мэдыяфайл зь Вікісховішча',
	'version-wikibase' => 'Wikibase',
);

/** Bulgarian (български)
 * @author Spiritia
 */
$messages['bg'] = array(
	'wikibase-entity-item' => 'обект',
	'wikibase-entity-property' => 'свойство',
	'wikibase-entity-query' => 'заявка',
	'wikibase-error-unexpected' => 'Възникна неочаквана грешка.',
	'wikibase-error-save-generic' => 'Промените не могат да бъдат завършени, поради възникнала грешка при опита за съхраняване.',
	'wikibase-error-remove-generic' => 'Промените не могат да бъдат завършени, поради възникнала грешка при опита за изтриване.',
	'wikibase-error-save-connection' => 'Промените не могат да бъдат завършени, поради възникнал проблем с интернет връзката при опита за съхраняване. Проверете интернет връзката си.',
	'wikibase-error-remove-connection' => 'Промените не могат да бъдат завършени, поради възникнал проблем с интернет връзката при опита за изтриване. Проверете интернет връзката си.',
	'wikibase-error-save-timeout' => 'Поради възникнали технически трудности съхраняването не можа да бъде завършено.',
	'wikibase-error-remove-timeout' => 'Поради възникнали технически трудности изтриването не можа да бъде завършено.',
	'wikibase-error-autocomplete-connection' => 'Възникна проблем със заявката към API интерфейса на Уикипедия. Опитайте по-късно.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Отговор на сървъра: $1',
	'wikibase-error-ui-no-external-page' => 'Посочената статия не беше намерена в съответния сайт.',
	'wikibase-error-ui-cant-edit' => 'Нямате права да извършите това действие.',
	'wikibase-error-ui-no-permissions' => 'Нямате необходимите права да извършите това действие.',
	'wikibase-error-ui-link-exists' => 'Свързването с тази страница е невъзможно. С нея вече е свързан друг обект от Уикиданни.',
	'wikibase-error-ui-session-failure' => 'Сесията ви е изтекла. Влезте отново в системата.',
	'wikibase-error-ui-edit-conflict' => 'Настъпил е конфликт на редакции. Презаредете и съхранете отново.',
	'wikibase-replicationnote' => 'Може да отнеме няколко минути, докато промените се отразят във всички уикита.',
	'wikibase-sitelinks-sitename-columnheading' => 'Език',
	'wikibase-sitelinks-siteid-columnheading' => 'Езиков код',
	'wikibase-sitelinks-link-columnheading' => 'Свързана статия', # Fuzzy
	'wikibase-tooltip-error-details' => 'Подробности',
	'datatypes-type-wikibase-item' => 'Обект',
	'datatypes-type-commonsMedia' => 'Файл от Общомедия',
);

/** Bengali (বাংলা)
 * @author Aftab1995
 * @author Bellayet
 * @author Gitartha.bordoloi
 * @author Leemon2010
 */
$messages['bn'] = array(
	'wikibase-entity-item' => 'আইটেম',
	'wikibase-entity-property' => 'বৈশিষ্ট্য',
	'wikibase-entity-query' => 'কোয়েরি',
	'wikibase-deletedentity-item' => 'আইটেম অপসারণ করা হয়েছে',
	'wikibase-diffview-reference' => 'তথ্যসূত্র',
	'wikibase-diffview-rank' => 'র‍্যাঙ্ক',
	'wikibase-diffview-qualifier' => 'কোয়ালিফায়ার',
	'wikibase-diffview-label' => 'লেভেল',
	'wikibase-diffview-alias' => 'উপনাম',
	'wikibase-diffview-description' => 'বিবরণ',
	'wikibase-diffview-link' => 'সংযোগ',
	'wikibase-error-unexpected' => 'একটি অনাকাঙ্ক্ষিত ত্রুটি দেখা দিয়েছে।',
	'wikibase-error-save-generic' => 'সংরক্ষণ কার্য সম্পাদনার সময় একটি ত্রুটি দেখা দিয়েছে এবং এর কারণে আপনার পরিবর্তনগুলো সম্পন্ন হয়নি।',
	'wikibase-error-remove-generic' => 'অপসারণ কার্য সম্পাদনার সময় একটি ত্রুটি দেখা দিয়েছে এবং এর কারণে আপনার পরিবর্তনগুলো সম্পন্ন হয়নি।',
	'wikibase-error-save-connection' => 'সংরক্ষণ কার্য সম্পাদনার সময় একটি যোগাযোগ ত্রুটি দেখা দিয়েছে এবং এর কারণে আপনার পরিবর্তনগুলো সম্পন্ন হয়নি। অনুগ্রহ করে আপনার ইন্টারনেট সংযোগটি পরীক্ষা করুন।',
	'wikibase-error-remove-connection' => 'অপসারণ কার্য সম্পাদনার সময় একটি যোগাযোগ ত্রুটি দেখা দিয়েছে এবং এর কারণে আপনার পরিবর্তনগুলো সম্পন্ন হয়নি। অনুগ্রহ করে আপনার ইন্টারনেট সংযোগটি পরীক্ষা করুন।',
	'wikibase-error-autocomplete-response' => 'সার্ভারের প্রতিক্রিয়া: $1',
	'wikibase-error-ui-client-error' => 'ক্লায়েন্ট পাতায় যোগাযোগ করতে ব্যর্থ হয়েছে। পরে আবার চেষ্টা করুন।',
	'wikibase-error-ui-no-external-page' => 'উল্লেখিত নিবন্ধ সংশ্লিষ্ট সাইটে খুঁজে পাওয়া যায়নি।',
	'wikibase-error-ui-cant-edit' => 'আপনাকে এই কর্ম সঞ্চালন করার অনুমতি দেওয়া হয়নি।',
	'wikibase-error-ui-no-permissions' => 'আপনার এই কর্ম সঞ্চালন করার যথেষ্ট অধিকার নেই।',
	'wikibase-error-ui-link-exists' => 'আপনি এই পাতায় সংযোগ দিতে পারবেন না কারণ ইতিমধ্যেই আরেকটি আইটেম এখানে সংযোগ করা আছে।',
	'wikibase-error-ui-session-failure' => 'আপনার সেশনের মেয়াদ শেষ হয়ে গেছে। দয়া করে আবার প্রবেশ করুন।',
	'wikibase-error-ui-edit-conflict' => 'এখানে একটি সম্পাদনা দ্বন্দ্ব হয়েছে। দয়া করে পুনঃলোড করুন এবং আবার সংরক্ষণ করুন।',
	'wikibase-replicationnote' => 'দয়া করে লক্ষ্য করুন যে এই পরিবর্তনগুলি সকল উইকিতে দৃশ্যমান হতে কয়েক মিনিট পর্যন্ত সময় লাগতে পারে।',
	'wikibase-sitelinks-wikipedia' => 'এই আইটেমটির সাথে সংযুক্ত উইকিপিডিয়ার পাতা',
	'wikibase-sitelinks-sitename-columnheading' => 'ভাষা',
	'wikibase-sitelinks-sitename-columnheading-special' => 'সাইট',
	'wikibase-sitelinks-siteid-columnheading' => 'কোড',
	'wikibase-sitelinks-link-columnheading' => 'সংযুক্ত পাতা',
	'wikibase-tooltip-error-details' => 'বিস্তারিত',
	'wikibase-undeserializable-value' => 'মানটি অবৈধ ও প্রদর্শন করা যাবে না।',
	'wikibase-validator-bad-type' => '$1 এর পরিবর্তে $2',
	'wikibase-validator-too-long' => '{{PLURAL:$1|একটি অক্ষরের|$1টি অক্ষরের}} চেয়ে বেশি হবে না',
	'wikibase-validator-too-short' => 'অন্তত {{PLURAL:$1|একটি অক্ষর|$1টি অক্ষর}} হতে হবে',
	'wikibase-validator-too-high' => 'সীমার বাইরে, অবশ্যই $1-এর থেকে বেশি হতে পারবে না।',
	'wikibase-validator-too-low' => 'সীমার বাইরে, অবশ্যই $1-এর থেকে কম হতে পারবে না।',
	'wikibase-validator-malformed-value' => 'ত্রুটিপূর্ণভাবে গঠিত ইনপুট: $1',
	'wikibase-validator-bad-entity-id' => 'ত্রুটিপূর্ণভাবে গঠিত আইডি: $1',
	'wikibase-validator-bad-entity-type' => '$1 অপ্রত্যাশিত ভুক্তির ধরণ',
	'wikibase-validator-no-such-entity' => '$1 খুঁজে পাওয়া যায়নি',
	'wikibase-validator-bad-value' => 'অবৈধ মান: $1',
	'wikibase-validator-unknown-unit' => 'অজানা একক: $1',
	'datatypes-type-wikibase-item' => 'আইটেম',
	'datatypes-type-commonsMedia' => 'কমন্স মিডিয়া ফাইল',
	'version-wikibase' => 'উইকিবেজ',
);

/** Breton (brezhoneg)
 * @author Fohanno
 * @author Fulup
 */
$messages['br'] = array(
	'wikibase-diffview-reference' => 'dave',
	'wikibase-diffview-rank' => 'renk',
	'wikibase-sitelinks-sitename-columnheading' => 'Yezh',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-tooltip-error-details' => 'Munudoù',
);

/** Bosnian (bosanski)
 * @author Edinwiki
 */
$messages['bs'] = array(
	'wikibase-lib-desc' => 'Sadrži zajedničku funkcionalnost za Wikibazu i proširenje za Wikibaza klijent.',
	'wikibase-entity-item' => 'stavka',
	'wikibase-entity-property' => 'svojstvo',
	'wikibase-entity-query' => 'upit',
	'wikibase-diffview-label' => 'oznaka',
	'wikibase-diffview-description' => 'opis',
	'wikibase-diffview-link' => 'veze',
	'wikibase-error-save-generic' => 'Greška je se desila tokom sačuvanja vaše izmjene. Zbog ovoga se vaše izmjene nisu mogle izvršiti.',
	'wikibase-error-remove-generic' => 'Greška je se desila tokom brisanja. Zbog ovoga se vaše izmjene nisu mogle izvršiti.',
	'wikibase-error-save-connection' => 'Desila je se greška sa konekcijom tokom sačuvanja. Zbog ovoga se vaše izmjene nisu mogle izvršiti. Provjerite vašu konekciju.',
	'wikibase-error-remove-connection' => 'Desila je se greška sa konekcijom tokom brisanja. Zbog ovoga se vaše izmjene nisu mogle izvršiti. Provjerite vašu konekciju.',
	'wikibase-error-save-timeout' => 'Trenutno imamo tehničkih poteškoća i zbog toga se ništa nije moglo sačuvati.',
	'wikibase-error-remove-timeout' => 'Trenutno imamo tehničkih poteškoća i zbog toga se ništa nije moglo izbrisati.',
	'wikibase-error-autocomplete-connection' => 'Nije bilo moguće poslati upit prema Wikipedija API. Pokušajte kasnije ponovo.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Odgovor servera: $1',
	'wikibase-error-ui-client-error' => 'Konekcija sa klijent stranicom je prekinuta. Pokušajte kasnije ponovo.',
	'wikibase-error-ui-no-external-page' => 'Naveden članak nije pronađen na odgovarajućoj stranici.',
	'wikibase-error-ui-cant-edit' => 'Niste ovlašteni da izvršite ovo djelo.',
	'wikibase-error-ui-no-permissions' => 'Nemate dovoljno prava da izvršite ovo djelo.',
	'wikibase-error-ui-link-exists' => 'Nemožete povezivati ovu stranicu zato što već druga stavka ima vezu prema njoj.',
	'wikibase-error-ui-session-failure' => 'Vaša sesija je istekla. Prijavite se ponovo.',
	'wikibase-error-ui-edit-conflict' => 'Došlo je do sukoba između izmjena. Osvježite stranicu i pokušajte ponovo sačuvati vaše izmjene.',
	'wikibase-replicationnote' => 'Budite svjesni da može potrajati nekoliko minuta dok izmjene budu vidljive na svim wiki strancima',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia stranice koje su povezane uz ovu stavku',
	'wikibase-sitelinks-sitename-columnheading' => 'Jezik',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Povezana stranica', # Fuzzy
	'wikibase-tooltip-error-details' => 'Detalji',
	'datatypes-type-wikibase-item' => 'Stavka',
	'datatypes-type-commonsMedia' => 'Commons medijska datoteka',
	'version-wikibase' => 'Wikibase',
);

/** Catalan (català)
 * @author Alvaro Vidal-Abarca
 * @author Arnaugir
 * @author Grondin
 * @author Qllach
 * @author Toniher
 * @author පසිඳු කාවින්ද
 */
$messages['ca'] = array(
	'wikibase-lib-desc' => 'Té la funcionalitat comuna per les extensions de Wikibase i de Wikibase Client',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'propietat',
	'wikibase-entity-query' => 'consulta',
	'wikibase-error-autocomplete-response' => 'El servidor ha respost: $1',
	'wikibase-error-ui-cant-edit' => 'No teniu permís per dur a terme aquesta acció.',
	'wikibase-error-ui-no-permissions' => 'No teniu els drets necessaris per a dur a terme aquesta acció.',
	'wikibase-error-ui-link-exists' => 'No podeu enllaçar a aquesta pàgina perquè ja hi ha un altre element que hi enllaça.',
	'wikibase-error-ui-edit-conflict' => "S'ha produït un conflicte d'edició. Si us plau, recarregueu la pàgina i torneu-la a desar.",
	'wikibase-sitelinks-wikipedia' => 'Pàgines de la Viquipèdia vinculades a aquest element',
	'wikibase-sitelinks-sitename-columnheading' => 'Llengua',
	'wikibase-sitelinks-siteid-columnheading' => 'Codi',
	'wikibase-sitelinks-link-columnheading' => 'Pàgina enllaçada',
	'wikibase-tooltip-error-details' => 'Detalls',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Fitxer multimèdia de Commons',
);

/** Chechen (нохчийн)
 * @author Умар
 */
$messages['ce'] = array(
	'wikibase-lib-desc' => 'Wikibase а Wikibase Client а массо функцеш',
	'wikibase-entity-item' => 'элемент',
	'wikibase-entity-property' => 'свойство',
	'wikibase-entity-query' => 'жоп деха',
	'wikibase-diffview-reference' => 'хьост',
	'wikibase-diffview-rank' => 'дарж',
	'wikibase-diffview-qualifier' => 'квалификатор',
	'wikibase-diffview-label' => 'билгало',
	'wikibase-diffview-alias' => 'синоним',
	'wikibase-diffview-description' => 'цуьнах лаьцна',
	'wikibase-diffview-link' => 'хьажоргаш',
	'wikibase-error-unexpected' => 'Дагахь доцу гӀалат хилла.',
	'wikibase-error-save-generic' => 'Ӏалаш ечу хенахь гӀалат даьлла, цундела хьан хийцам чекхбаккха цало.',
	'wikibase-error-remove-generic' => 'ДӀайоккхучу хенахь гӀалат даьлла, цундела хьан хийцам чекхбаккха цало.',
	'wikibase-error-save-connection' => 'Ӏалаш ечу хенахь интернетах дӀатасаран гӀалат даьлла, цундела хьан хийцам чекхбаккха цало. Дехар до хьажа хьай интернет дӀатасаре.',
	'wikibase-error-remove-connection' => 'ДӀайоккхучу хенахь интернетах дӀатасаран гӀалат даьлла, цундела хьан хийцам чекхбаккха цало. Дехар до хьажа хьай интернет дӀатасаре.',
	'wikibase-error-autocomplete-response' => 'Серверо жоп делла: $1',
	'wikibase-error-ui-no-external-page' => 'Кара цайо оцу сайтан чохь иштта цӀе йолу агӀо.',
	'wikibase-error-ui-session-failure' => 'Хьан сессин хан чекх ела. Дехар до, юха чувала/яла.',
	'wikibase-timedetails-time' => 'Хан',
	'wikibase-timedetails-isotime' => 'Хан билгал яр ISO-форматехь',
	'wikibase-timedetails-timezone' => 'Сахьтан аса',
	'wikibase-timedetails-calendar' => 'Календарь',
	'wikibase-timedetails-precision' => 'Билгала',
	'wikibase-timedetails-before' => 'Кхачале',
	'wikibase-timedetails-after' => 'ТӀехьа',
	'wikibase-globedetails-longitude' => 'Дохалла',
	'wikibase-globedetails-latitude' => 'Шоралла',
	'wikibase-globedetails-precision' => 'Билгала',
	'wikibase-globedetails-globe' => 'Глобус',
	'wikibase-replicationnote' => 'Дехар до, тидам бар бина хийцам гучу ца болуш маситта минут яла там бу массо вики-проекташкахь.',
	'wikibase-sitelinks-wikipedia' => 'ХӀокху элементах тесна йолу агӀонаш',
	'wikibase-sitelinks-sitename-columnheading' => 'Мотт',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Вовшахтесна йолу агӀонаш', # Fuzzy
	'wikibase-tooltip-error-details' => 'Мадарра',
	'datatypes-type-wikibase-item' => 'Элемент',
	'datatypes-type-commonsMedia' => 'Медиафайл Викидlайуьллуче чохь',
	'version-wikibase' => 'Вики-баз',
);

/** Sorani Kurdish (کوردی)
 * @author Calak
 */
$messages['ckb'] = array(
	'wikibase-entity-item' => 'بەند',
	'wikibase-entity-property' => 'تایبەتمەندی',
	'wikibase-diffview-reference' => 'سەرچاوە',
	'wikibase-diffview-rank' => 'پلە',
	'wikibase-error-unexpected' => 'ھەڵەیەکی چاوەڕوان‌نەکراو ڕووی دا.',
	'wikibase-error-save-generic' => 'ھەڵەیەکی چاوەڕوان‌نەکراو لە کاتی پاشەکەوتکردندا ڕووی دا و لەبەر ئەمە، گۆڕانکارییەکانت بە ئەنجام نەگەییشت.',
	'wikibase-error-remove-generic' => 'ھەڵەیەکی چاوەڕوان‌نەکراو لە کاتی سڕینەوەدا ڕووی دا و لەبەر ئەمە، گۆڕانکارییەکانت بە ئەنجام نەگەییشت.',
	'wikibase-error-save-connection' => 'ھەڵەیەکی چاوەڕوان‌نەکراو لە کاتی پاشەکەوتکردندا ڕووی دا و لەبەر ئەمە، گۆڕانکارییەکانت بە ئەنجام نەگەییشت. تکایە پەیوەندی ئینتەرنێتەکەت تاوتوێ بکە.',
	'wikibase-error-remove-connection' => 'ھەڵەیەکی چاوەڕوان‌نەکراو لە کاتی سڕینەوەدا ڕووی دا و لەبەر ئەمە، گۆڕانکارییەکانت بە ئەنجام نەگەییشت. تکایە پەیوەندی ئینتەرنێتەکەت تاوتوێ بکە.',
	'wikibase-error-ui-no-external-page' => 'وتاری دەستنیشان‌کراو لە پێگەی ئاماژەکراودا نەدۆزرایەوە.',
	'wikibase-replicationnote' => 'تکایە ئاگادار بن چەند خولەک دەگرێ ھەتا گۆڕانکارییەکان لە ھەموو ویکییەکاندا دەربکەوێ.',
	'wikibase-sitelinks-wikipedia' => 'پەڕە بەسراوەکانی ویکیپیدیا بەم بەندەوە',
	'wikibase-sitelinks-sitename-columnheading' => 'زمان',
	'wikibase-sitelinks-siteid-columnheading' => 'کۆد',
	'wikibase-sitelinks-link-columnheading' => 'وتاری بەستەردراو', # Fuzzy
	'wikibase-tooltip-error-details' => 'وردەکارییەکان',
	'datatypes-type-wikibase-item' => 'بەند',
);

/** Czech (čeština)
 * @author Danny B.
 * @author JAn Dudík
 * @author Littledogboy
 * @author Mormegil
 * @author Vks
 */
$messages['cs'] = array(
	'wikibase-lib-desc' => 'Obsahuje společné funkce pro rozšíření Wikibase a Klient Wikibase',
	'wikibase-entity-item' => 'položka',
	'wikibase-entity-property' => 'vlastnost',
	'wikibase-entity-query' => 'dotaz',
	'wikibase-deletedentity-item' => 'Smazaná položka',
	'wikibase-deletedentity-property' => 'Smazaná vlastnost',
	'wikibase-deletedentity-query' => 'Smazaný dotaz',
	'wikibase-diffview-reference' => 'reference',
	'wikibase-diffview-rank' => 'hodnocení',
	'wikibase-diffview-rank-preferred' => 'Preferované postavení',
	'wikibase-diffview-rank-normal' => 'Normální postavení',
	'wikibase-diffview-rank-deprecated' => 'Zavržené postavení',
	'wikibase-diffview-qualifier' => 'vymezení',
	'wikibase-diffview-label' => 'štítek',
	'wikibase-diffview-alias' => 'aliasy',
	'wikibase-diffview-description' => 'popis',
	'wikibase-diffview-link' => 'odkazy',
	'wikibase-error-unexpected' => 'Došlo k neočekávané chybě.',
	'wikibase-error-save-generic' => 'Při pokusu provést akci došlo k chybě, takže vaše změny nebyly provedeny.',
	'wikibase-error-remove-generic' => 'Při pokusu o odstranění došlo k chybě, takže vaše změny nebyly provedeny.',
	'wikibase-error-save-connection' => 'Při pokusu o uložení došlo k chybě připojení, takže vaše změny nebyly provedeny. Prosím zkontrolujte své připojení k Internetu.',
	'wikibase-error-remove-connection' => 'Při pokusu o odstranění došlo k chybě připojení, takže vaše změny nebyly provedeny. Prosím zkontrolujte své připojení k Internetu.',
	'wikibase-error-save-timeout' => 'Kvůli současným technickým problémům se vaše „uložit“ nepodařilo dokončit.',
	'wikibase-error-remove-timeout' => 'Kvůli současným technickým problémům se vaše „odstranit“ nepodařilo dokončit.',
	'wikibase-error-autocomplete-connection' => 'Dotaz na API serveru se nezdařil. Zkuste to prosím později.',
	'wikibase-error-autocomplete-response' => 'Odpověď serveru: $1',
	'wikibase-error-ui-client-error' => 'Připojení ke klientské stránce se nezdařilo. Zkuste to prosím později.',
	'wikibase-error-ui-no-external-page' => 'Takový článek nebyl na příslušném webu nalezen.',
	'wikibase-error-ui-cant-edit' => 'Nemáte oprávnění k provedení této akce.',
	'wikibase-error-ui-no-permissions' => 'Nemáte dostatečná práva k provedení této akce.',
	'wikibase-error-ui-link-exists' => 'Na tuto stránku nemůžete odkázat, protože na ni již odkazuje jiná položka.',
	'wikibase-error-ui-session-failure' => 'Platnost vaší relace skončila. Prosíme, přihlaste se znovu.',
	'wikibase-error-ui-edit-conflict' => 'Nastal editační konflikt. Prosím obnovte stránku a uložte ji znovu.',
	'wikibase-quantitydetails-amount' => 'Množství',
	'wikibase-quantitydetails-upperbound' => 'Horní mez',
	'wikibase-quantitydetails-lowerbound' => 'Dolní mez',
	'wikibase-quantitydetails-unit' => 'Jednotka',
	'wikibase-replicationnote' => 'Vezměte prosím na vědomí, že než se změny projeví na všech wiki, může to pár minut trvat.',
	'wikibase-sitelinks-wikipedia' => 'Stránky Wikipedie provázané s touto položkou',
	'wikibase-sitelinks-sitename-columnheading' => 'Jazyk',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Projekt',
	'wikibase-sitelinks-siteid-columnheading' => 'Kód',
	'wikibase-sitelinks-link-columnheading' => 'Propojená stránka',
	'wikibase-tooltip-error-details' => 'Podrobnosti',
	'wikibase-undeserializable-value' => 'Hodnota je neplatná a nelze ji zobrazit.',
	'wikibase-validator-bad-type' => '$2 namísto $1',
	'wikibase-validator-too-long' => 'Nesmí být delší než {{PLURAL:$1|jeden znak|$1 znaky|$1 znaků}}',
	'wikibase-validator-too-short' => 'Nesmí být kratší než {{PLURAL:$1|jeden znak|$1 znaky|$1 znaků}}',
	'wikibase-validator-too-high' => 'Mimo rozsah, nesmí být vyšší než $1',
	'wikibase-validator-too-low' => 'Mimo rozsah, nesmí být nižší než $1',
	'wikibase-validator-malformed-value' => 'Chybný vstup: $1',
	'wikibase-validator-bad-entity-id' => 'Chybné ID: $1',
	'wikibase-validator-bad-entity-type' => 'Neočekávaný typ entity: $1',
	'wikibase-validator-no-such-entity' => '$1 nenalezeno',
	'wikibase-validator-no-such-property' => 'Vlastnost $1 nenalezena',
	'wikibase-validator-bad-value' => 'Neplatná hodnota: $1',
	'wikibase-validator-bad-value-type' => 'Chybný typ hodnoty $1, očekáváno $2',
	'wikibase-validator-bad-url' => 'URL má nesprávný tvar: $1',
	'wikibase-validator-bad-url-scheme' => 'Nepodporovaný formát URL: $1',
	'wikibase-validator-bad-http-url' => 'HTTP URL má nesprávný tvar: $1',
	'wikibase-validator-bad-mailto-url' => 'URL mailto: má nesprávný tvar: $1',
	'wikibase-validator-unknown-unit' => 'Neznámá jednotka: $1',
	'datatypes-type-wikibase-item' => 'Položka',
	'datatypes-type-commonsMedia' => 'Mediální soubor na Commons',
	'version-wikibase' => 'Wikibase',
);

/** Church Slavic (словѣньскъ / ⰔⰎⰑⰂⰡⰐⰠⰔⰍⰟ)
 * @author ОйЛ
 */
$messages['cu'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'ѩꙁꙑкъ',
);

/** Welsh (Cymraeg)
 * @author Lloffiwr
 * @author Robin Owain
 */
$messages['cy'] = array(
	'wikibase-entity-item' => 'yr eitem',
	'wikibase-entity-property' => 'y nodwedd',
	'wikibase-entity-query' => 'chwiliad',
	'wikibase-deletedentity-item' => 'Eitem a ddilewyd',
	'wikibase-deletedentity-query' => 'Gofyniad a ddilewyd',
	'wikibase-diffview-reference' => 'ffynhonnell',
	'wikibase-diffview-rank' => 'gradd',
	'wikibase-diffview-qualifier' => 'goleddfwr',
	'wikibase-diffview-label' => 'label',
	'wikibase-diffview-alias' => 'arallenwau',
	'wikibase-diffview-description' => 'disgrifiad',
	'wikibase-diffview-link' => 'cysylltau',
	'wikibase-error-unexpected' => 'Cafwyd nam annisgwyl',
	'wikibase-error-save-generic' => "Cafwyd nam tra'n ceisio rhoi ar gadw ac oherwydd hyn ni allwyd cadw eich newidiadau.",
	'wikibase-error-remove-generic' => "Cafwyd nam tra'n ceisio diddymu ac oherwydd hyn ni allwyd cwbwlhau eich newidiadau.",
	'wikibase-error-save-connection' => "Cafwyd nam ar y cysylltiad wrth geisio cadw eich gwaith, ac oherwydd hyn ni allwyd cadw eich newidiadau. Gwirwch eich cysylltiad â'r Rhyngrwyd.",
	'wikibase-error-remove-connection' => "Cafwyd nam ar y cysylltiad wrth geisio diddymu, ac oherwydd hyn ni allwyd cadw eich newidiadau. Gwirwch eich cysylltiad â'r Rhyngrwyd.",
	'wikibase-error-save-timeout' => 'Mae nam technegol yn bodoli, ac oherwydd hyn ni allwyd cadw eich newidiadau.',
	'wikibase-error-remove-timeout' => 'Mae nam technegol yn bodoli, ac oherwydd hyn ni allwyd cwbwlhau eich "diddymiad".',
	'wikibase-error-autocomplete-connection' => 'Ni lwyddwyd gofyn i API y wefan. Ceisiwch eto toc.',
	'wikibase-error-autocomplete-response' => 'Ateb y gweinydd: $1',
	'wikibase-error-ui-client-error' => "Methodd y cysylltiad i'r dudalen gleient. Ceisiwch rhywdro eto.",
	'wikibase-error-ui-no-external-page' => "Ni chafwyd hyd i'r erthygl a nodwyd ar y wefan gyfatebol.",
	'wikibase-error-ui-cant-edit' => "Nid yw'r gallu gennych i gyflawni'r weithred hon.",
	'wikibase-error-ui-no-permissions' => "Nid yw eich cyfrif wedi derbyn y gallu i gwblhau'r weithred hon.",
	'wikibase-error-ui-session-failure' => 'Daeth eich sesiwn i ben. Mewngofnodwch eto.',
	'wikibase-error-ui-edit-conflict' => "Cafwyd gwrthdaro rhwng golygiadau. Ail-lwythwch y dudalen a'i chadw eildro.",
	'wikibase-quantitydetails-upperbound' => 'Y terfyn uchaf',
	'wikibase-quantitydetails-lowerbound' => 'Y terfyn isaf',
	'wikibase-quantitydetails-unit' => 'Uned',
	'wikibase-replicationnote' => 'Dalier sylw: efallai na welwch y newidiadau ar bob wici cyn pen rhai munudau.',
	'wikibase-sitelinks-wikipedia' => "Tudalennau Wicipedia sy'n cysylltu i'r eitem hon",
	'wikibase-sitelinks-sitename-columnheading' => 'Iaith',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Gwefan',
	'wikibase-sitelinks-siteid-columnheading' => 'Cod',
	'wikibase-sitelinks-link-columnheading' => 'Tudalen a gysylltwyd',
	'wikibase-tooltip-error-details' => 'Manylion',
	'wikibase-undeserializable-value' => "Ni ellir dangos y gwerth oherwydd nad yw'n ddilys.",
	'wikibase-validator-bad-type' => '$2 yn lle $1',
	'wikibase-validator-too-long' => 'Ni all fod yn hwy {{PLURAL:$1||nag un|na $1}} nod o hyd',
	'wikibase-validator-unknown-unit' => 'Uned anhysbys: $1',
	'datatypes-type-wikibase-item' => 'Eitem',
	'datatypes-type-commonsMedia' => 'Ffeil cyfrwng ar y Comin',
	'version-wikibase' => 'Wikibase',
);

/** Danish (dansk)
 * @author Byrial
 * @author Christian List
 * @author Hede2000
 * @author HenrikKbh
 * @author Poul G
 */
$messages['da'] = array(
	'wikibase-lib-desc' => 'Fælles funktionalitet for Wikibase og Wikibase-klientudvidelser',
	'wikibase-entity-item' => 'emne',
	'wikibase-entity-property' => 'egenskab',
	'wikibase-entity-query' => 'forespørgsel',
	'wikibase-deletedentity-item' => 'Slettet emne',
	'wikibase-deletedentity-property' => 'Slettet egenskab',
	'wikibase-deletedentity-query' => 'Slettet forespørgsel',
	'wikibase-diffview-reference' => 'kilde',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-rank-preferred' => 'Foretrukne rang',
	'wikibase-diffview-rank-normal' => 'Normal rang',
	'wikibase-diffview-rank-deprecated' => 'Forældet rang',
	'wikibase-diffview-qualifier' => 'operator',
	'wikibase-diffview-label' => 'etiket',
	'wikibase-diffview-alias' => 'aliasser',
	'wikibase-diffview-description' => 'beskrivelse',
	'wikibase-diffview-link' => 'henvisninger',
	'wikibase-error-unexpected' => 'Der opstod en uventet fejl.',
	'wikibase-error-save-generic' => 'Der opstod en fejl under forsøget på at gemme og derfor kan ændringerne ikke gennemføres.',
	'wikibase-error-remove-generic' => 'Der opstod en fejl under forsøget på at fjerne og derfor kan ændringerne ikke gennemføres.',
	'wikibase-error-save-connection' => 'En forbindelsesfejl opstod under forsøget på at gemme og derfor kunne dine ændringer ikke gennemføres. Kontroller forbindelsen til internettet.',
	'wikibase-error-remove-connection' => 'En forbindelsesfejl opstod under forsøget på at fjerne og derfor kunne dine ændringer ikke gennemføres. Kontroller forbindelsen til internettet.',
	'wikibase-error-save-timeout' => 'Vi oplever tekniske problemer og derfor kunne dit ønske om at gemme ikke gennemføres.',
	'wikibase-error-remove-timeout' => 'Vi oplever tekniske problemer og derfor kunne dit ønske om at fjerne ikke gennemføres.',
	'wikibase-error-autocomplete-connection' => 'Webstedets API kunne ikke forespørges. Prøv igen senere.',
	'wikibase-error-autocomplete-response' => 'Serveren svarede: $1',
	'wikibase-error-ui-client-error' => 'Forbindelsen til side-klienten mislykkedes. Prøv igen senere.',
	'wikibase-error-ui-no-external-page' => 'Den angivne artikel blev ikke fundet på det tilsvarende websted.',
	'wikibase-error-ui-cant-edit' => 'Du har ikke tilladelse til at udføre denne handling.',
	'wikibase-error-ui-no-permissions' => 'Du har ikke tilstrækkelige rettigheder til at udføre denne handling.',
	'wikibase-error-ui-link-exists' => 'Du kan ikke sammenkæde med denne side, fordi et andet emne allerede er forbundet til den.',
	'wikibase-error-ui-session-failure' => 'Din session er udløbet. Log venligst ind igen.',
	'wikibase-error-ui-edit-conflict' => 'Der er en redigeringskonflikt. Genindlæs og gem igen.',
	'wikibase-quantitydetails-amount' => 'Mængde',
	'wikibase-quantitydetails-upperbound' => 'Øvre grænse',
	'wikibase-quantitydetails-lowerbound' => 'Nedre grænse',
	'wikibase-quantitydetails-unit' => 'Enhed',
	'wikibase-replicationnote' => 'Vær opmærksom på, at der kan gå flere minutter før ændringerne er synlige på alle wikier.',
	'wikibase-sitelinks-wikipedia' => 'Wikipediasider knyttet til dette emne',
	'wikibase-sitelinks-sitename-columnheading' => 'Sprog',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Websted',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Linket side',
	'wikibase-tooltip-error-details' => 'Detaljer',
	'wikibase-undeserializable-value' => 'Værdien er ugyldig og kan ikke vises.',
	'wikibase-validator-bad-type' => '$2 i stedet for $1',
	'wikibase-validator-too-long' => 'Skal være mere end {{PLURAL:$1|et tegn|$1 tegn}} lang',
	'wikibase-validator-too-short' => 'Skal være mindst {{PLURAL:$1|et tegn|$1 tegn}} lang',
	'wikibase-validator-too-high' => 'Uden for det lovlige område, må ikke være højere end $1',
	'wikibase-validator-too-low' => 'Uden for det lovlige område, må ikke være lavere end $1',
	'wikibase-validator-malformed-value' => 'Fejlformateret input: $1',
	'wikibase-validator-bad-entity-id' => 'Fejlformateret ID: $1',
	'wikibase-validator-bad-entity-type' => 'Uventet entitetstype $1',
	'wikibase-validator-no-such-entity' => '$1 ikke fundet',
	'wikibase-validator-no-such-property' => 'Egenskaben $1 blev ikke fundet',
	'wikibase-validator-bad-value' => 'Ugyldig værdi: $1',
	'wikibase-validator-bad-value-type' => 'Forkert værditype $1, forventede $2',
	'wikibase-validator-bad-url' => 'Forkert udformet URL-adresse: $1',
	'wikibase-validator-bad-url-scheme' => 'Ikke-understøttet URL-skema: $1',
	'wikibase-validator-bad-http-url' => 'Forkert udformet HTTP URL-adresse: $1',
	'wikibase-validator-bad-mailto-url' => 'Forkert udformet mailto-URL: $1',
	'wikibase-validator-unknown-unit' => 'Ukendt enhed: $1',
	'datatypes-type-wikibase-item' => 'Emne',
	'datatypes-type-commonsMedia' => 'Commons media-fil',
	'version-wikibase' => 'Wikibase',
);

/** German (Deutsch)
 * @author G.Hagedorn
 * @author Inkowik
 * @author Kghbln
 * @author Metalhead64
 * @author Se4598
 * @author TMg
 */
$messages['de'] = array(
	'wikibase-lib-desc' => 'Stellt dem Repositorium strukturierter Daten Funktionen bereit',
	'wikibase-entity-item' => 'Datenobjekt',
	'wikibase-entity-property' => 'Eigenschaft',
	'wikibase-entity-query' => 'Abfrage',
	'wikibase-deletedentity-item' => 'Gelöschtes Datenobjekt',
	'wikibase-deletedentity-property' => 'Gelöschte Eigenschaft',
	'wikibase-deletedentity-query' => 'Gelöschte Abfrage',
	'wikibase-diffview-reference' => 'Referenz',
	'wikibase-diffview-rank' => 'Rang',
	'wikibase-diffview-rank-preferred' => 'Vorrangig',
	'wikibase-diffview-rank-normal' => 'Normaler Rang',
	'wikibase-diffview-rank-deprecated' => 'Herabgestufter Rang',
	'wikibase-diffview-qualifier' => 'Qualifikator',
	'wikibase-diffview-label' => 'Bezeichnung',
	'wikibase-diffview-alias' => 'Aliasse',
	'wikibase-diffview-description' => 'Beschreibung',
	'wikibase-diffview-link' => 'Links',
	'wikibase-error-unexpected' => 'Es ist ein unerwarteter Fehler aufgetreten.',
	'wikibase-error-save-generic' => 'Beim Speichern ist ein Fehler aufgetreten. Die Änderungen konnten daher nicht vollständig durchgeführt werden.',
	'wikibase-error-remove-generic' => 'Beim Versuch zu entfernen, ist ein Fehler aufgetreten. Diese Änderungen konnten daher nicht fertig durchgeführt werden.',
	'wikibase-error-save-connection' => 'Beim Versuch zu speichern ist ein Verbindungsfehler aufgetreten. Diese Änderungen konnten daher nicht fertig durchgeführt werden. Die Internetverbindung sollte überprüft werden.',
	'wikibase-error-remove-connection' => 'Beim Versuch zu entfernen ist ein Verbindungsfehler aufgetreten. Diese Änderungen konnten daher nicht fertig durchgeführt werden. Die Internetverbindung sollte überprüft werden.',
	'wikibase-error-save-timeout' => 'Wir haben technische Schwierigkeiten. Diese Änderungen konnten daher nicht fertig gespeichert werden.',
	'wikibase-error-remove-timeout' => 'Wir haben technische Schwierigkeiten. Diese Änderungen konnten daher nicht fertig gespeichert werden.',
	'wikibase-error-autocomplete-connection' => 'Die Website-API konnte nicht abgefragt werden. Bitte versuche es später noch einmal.',
	'wikibase-error-autocomplete-response' => 'Serverantwort: $1',
	'wikibase-error-ui-client-error' => 'Die Verbindung zur externen Webseite ist gescheitert. Bitte versuche es später noch einmal.',
	'wikibase-error-ui-no-external-page' => 'Der angegebene Artikel konnte nicht auf der zugehörigen Website gefunden werden.',
	'wikibase-error-ui-cant-edit' => 'Du bist nicht berechtigt, diese Aktion auszuführen.',
	'wikibase-error-ui-no-permissions' => 'Du hast keine ausreichende Berechtigung, um diese Aktion auszuführen.',
	'wikibase-error-ui-link-exists' => 'Du kannst nicht auf diese Seite verlinken, da ein anderes Datenobjekt bereits auf sie verlinkt.',
	'wikibase-error-ui-session-failure' => 'Deine Sitzung ist abgelaufen. Du musst dich daher erneut anmelden.',
	'wikibase-error-ui-edit-conflict' => 'Es gab einen Bearbeitungskonflikt. Bitte lade und speichere die Seite erneut.',
	'wikibase-quantitydetails-amount' => 'Menge',
	'wikibase-quantitydetails-upperbound' => 'Obergrenze',
	'wikibase-quantitydetails-lowerbound' => 'Untergrenze',
	'wikibase-quantitydetails-unit' => 'Einheit',
	'wikibase-timedetails-time' => 'Zeit',
	'wikibase-timedetails-isotime' => 'ISO-Zeitstempel',
	'wikibase-timedetails-timezone' => 'Zeitzone',
	'wikibase-timedetails-calendar' => 'Kalender',
	'wikibase-timedetails-precision' => 'Genauigkeit',
	'wikibase-timedetails-before' => 'Vor',
	'wikibase-timedetails-after' => 'Nach',
	'wikibase-globedetails-longitude' => 'Längengrad',
	'wikibase-globedetails-latitude' => 'Breitengrad',
	'wikibase-globedetails-precision' => 'Genauigkeit',
	'wikibase-globedetails-globe' => 'Globus',
	'wikibase-replicationnote' => 'Bitte bedenke, dass es einige Minuten dauern kann, bis die Änderungen auf allen Wikis sichtbar sind.',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia-Seiten zu diesem Objekt',
	'wikibase-sitelinks-sitename-columnheading' => 'Sprache',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Website',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Verlinkte Seite',
	'wikibase-tooltip-error-details' => 'Einzelheiten',
	'wikibase-undeserializable-value' => 'Der Wert ist ungültig und kann nicht angezeigt werden.',
	'wikibase-validator-bad-type' => '$2 anstatt von $1',
	'wikibase-validator-too-long' => 'Darf nicht länger als {{PLURAL:$1|ein Zeichen|$1 Zeichen}} sein.',
	'wikibase-validator-too-short' => 'Muss mindestens {{PLURAL:$1|ein Zeichen|$1 Zeichen}} lang sein',
	'wikibase-validator-too-high' => 'Außerhalb des Bereichs. Darf nicht höher sein als $1.',
	'wikibase-validator-too-low' => 'Außerhalb des Bereichs. Darf nicht niedriger sein als $1.',
	'wikibase-validator-malformed-value' => 'Fehlerhafte Eingabe: $1',
	'wikibase-validator-bad-entity-id' => 'Fehlerhafte Kennung: $1',
	'wikibase-validator-bad-entity-type' => 'Unerwarteter Objekttyp „$1“',
	'wikibase-validator-no-such-entity' => '$1 nicht gefunden',
	'wikibase-validator-no-such-property' => 'Eigenschaft $1 nicht gefunden',
	'wikibase-validator-bad-value' => 'Ungültiger Wert: $1',
	'wikibase-validator-bad-value-type' => 'Ungültiger Wertetyp $1, erwartet $2',
	'wikibase-validator-bad-url' => 'Fehlerhafte URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Nicht unterstütztes URL-Schema: $1',
	'wikibase-validator-bad-http-url' => 'Fehlerhafte HTTP-URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Fehlerhafte mailto-URL: $1',
	'wikibase-validator-unknown-unit' => 'Unbekannte Einheit: $1',
	'datatypes-type-wikibase-item' => 'Datenobjekt',
	'datatypes-type-commonsMedia' => 'Mediendatei auf Commons',
	'version-wikibase' => 'Wikibase-Erweiterungen',
);

/** German (formal address) (Deutsch (Sie-Form)‎)
 * @author G.Hagedorn
 * @author Kghbln
 */
$messages['de-formal'] = array(
	'wikibase-error-autocomplete-connection' => 'Die Wikipedia-API konnte nicht abgefragt werden. Bitte versuchen Sie es später noch einmal.', # Fuzzy
	'wikibase-error-ui-client-error' => 'Die Verbindung zur externen Webseite ist gescheitert. Bitte versuchen Sie es später noch einmal.',
	'wikibase-error-ui-cant-edit' => 'Sie sind nicht berechtigt, diese Aktion auszuführen.',
	'wikibase-error-ui-no-permissions' => 'Sie haben keine ausreichende Berechtigung, um diese Aktion auszuführen.',
	'wikibase-error-ui-link-exists' => 'Sie können nicht auf diese Seite verlinken, da ein anderes Datenobjekt bereits auf sie verlinkt.',
	'wikibase-error-ui-session-failure' => 'Ihre Sitzung ist abgelaufen. Sie müssen sich daher erneut anmelden.',
	'wikibase-error-ui-edit-conflict' => 'Es gab einen Bearbeitungskonflikt. Bitte laden und speichern Sie die Seite erneut.',
);

/** Zazaki (Zazaki)
 * @author Erdemaslancan
 * @author Marmase
 * @author Mirzali
 */
$messages['diq'] = array(
	'wikibase-entity-item' => 'çêki',
	'wikibase-entity-query' => 'persen',
	'wikibase-diffview-reference' => 'referans',
	'wikibase-diffview-rank' => 'rêze',
	'wikibase-diffview-qualifier' => 'kalifikator',
	'wikibase-diffview-label' => 'etiket',
	'wikibase-diffview-alias' => 'nameyê bini',
	'wikibase-diffview-description' => 'şınasnayış',
	'wikibase-diffview-link' => 'gırey',
	'wikibase-quantitydetails-amount' => 'Miqdar',
	'wikibase-quantitydetails-unit' => 'Lete',
	'wikibase-sitelinks-sitename-columnheading' => 'Zıwan',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sita',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Pela gırebiyayiye',
	'wikibase-tooltip-error-details' => 'Teferruati',
	'datatypes-type-wikibase-item' => 'Çêki',
	'version-wikibase' => 'Wikibase',
);

/** Lower Sorbian (dolnoserbski)
 * @author Michawiki
 */
$messages['dsb'] = array(
	'wikibase-lib-desc' => 'Stoj powšyknu funkcionalnosć za rozšyrjeni Wikibase a Wikibase Client k dispoziciji',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'kakosć',
	'wikibase-entity-query' => 'wótpšašanje',
	'wikibase-diffview-reference' => 'referenca',
	'wikibase-diffview-rank' => 'rěd',
	'wikibase-diffview-qualifier' => 'kwalifikator',
	'wikibase-error-unexpected' => 'Njewócakowana zmólka jo nastała.',
	'wikibase-error-save-generic' => 'Pśi składowanju jo zmólka nastała, a togodla njedaju se změny pśewjasć.',
	'wikibase-error-remove-generic' => 'Pśi wótpóranju jo zmólka nastała, a togodla njedaju se změny pśewjasć.',
	'wikibase-error-save-connection' => 'Zwiskowa zmólka jo pśi składowanju namakała a twóje změny njedaju se togodla pśewjasć. Pšosym pśeglědaj swój internetowy zwisk.',
	'wikibase-error-remove-connection' => 'Zwiskowa zmólka jo pśi wótporanju namakała a twóje změny njedaju se togodla pśewjasć. Pšosym pśeglědaj swój internetowy zwisk.',
	'wikibase-error-save-timeout' => 'Mamy techniske śěžkosći a togodla njedajo se nic składowaś.',
	'wikibase-error-remove-timeout' => 'Mamy techniske śěžkosći a togodla njedajo se nic wótpóraś.',
	'wikibase-error-autocomplete-connection' => 'API sedła njedajo se napšašowaś. Pšosym wopytaj pózdźej hyšći raz.',
	'wikibase-error-autocomplete-response' => 'Serwer jo wótegronił: $1',
	'wikibase-error-ui-client-error' => 'Zwisk k eksternemu webbokoju jo se njeraźił. Pšosym wopytaj pózdźej hyšći raz.',
	'wikibase-error-ui-no-external-page' => 'Pódany nastawk njedajo se na wótpowědujucem sedle namakaś.',
	'wikibase-error-ui-cant-edit' => 'Njesmějoš toś tu akciju wuwjasć.',
	'wikibase-error-ui-no-permissions' => 'Njamaš dosć pšawow, aby toś tu akciju wuwjadł.',
	'wikibase-error-ui-link-exists' => 'Njamóžoš k toś tomu bokoju wótkazowaś, dokulaž drugi element južo k njomu wótkazujo.',
	'wikibase-error-ui-session-failure' => 'Twójo pósejźenje jo se pśepadnuło. Pšosym pśizjaw se hyšći raz.',
	'wikibase-error-ui-edit-conflict' => 'Jo wobźěłowański konflikt dał. Pšosym zacytuj a składuj znowego.',
	'wikibase-replicationnote' => 'Pšosym źiwaj na to, až móžo někotare minuty traś, až změny njejsu widobne na wšych wikijach.',
	'wikibase-sitelinks-sitename-columnheading' => 'Rěc',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Wótkazany bok',
	'wikibase-tooltip-error-details' => 'Drobnostki',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Medijowa dataja na Wikimedia Commons',
);

/** Greek (Ελληνικά)
 * @author Nikosguard
 */
$messages['el'] = array(
	'wikibase-entity-item' => 'αντικείμενο',
	'wikibase-entity-property' => 'ιδιότητα',
	'wikibase-entity-query' => 'ερώτημα',
	'wikibase-deletedentity-item' => 'Διαγραμμένο αντικείμενο',
	'wikibase-deletedentity-property' => 'Διαγραμμένη ιδιότητα',
	'wikibase-deletedentity-query' => 'Διαγραμμένο ερώτημα',
	'wikibase-diffview-reference' => 'πηγή',
	'wikibase-diffview-qualifier' => 'προσδιοριστής',
	'wikibase-diffview-label' => 'ετικέτα',
	'wikibase-diffview-description' => 'περιγραφή',
	'wikibase-diffview-link' => 'σύνδεσμοι',
	'wikibase-error-unexpected' => 'Παρουσιάστηκε ένα απροσδόκητο σφάλμα.',
	'wikibase-error-save-generic' => 'Παρουσιάστηκε σφάλμα κατά την προσπάθειά σας να αποθηκεύσετε και εξαιτίας αυτού, οι αλλαγές σας μπορεί να μην ολοκληρώθηκαν.',
	'wikibase-error-remove-generic' => 'Παρουσιάστηκε σφάλμα κατά την προσπάθεια να προβείτε στην αφαίρεση και εξαιτίας αυτού, οι αλλαγές σας μπορεί να μην έχουν ολοκληρωθεί.',
	'wikibase-error-save-connection' => 'Παρουσιάστηκε σφάλμα σύνδεσης ενώ προσπαθήσατε  να αποθηκεύσετε, και εξαιτίας αυτού οι αλλαγές σας μπορεί να μην ολοκληρώθηκαν. Παρακαλούμε να ελέγξετε σύνδεσή σας στο Διαδίκτυο.',
	'wikibase-error-remove-connection' => 'Παρουσιάστηκε σφάλμα κατά την προσπάθεια να προβείτε στην αφαίρεση και εξαιτίας αυτού, οι αλλαγές σας μπορεί να μην έχουν ολοκληρωθεί. Παρακαλούμε να ελέγξετε σύνδεσή σας στο Διαδίκτυο.',
	'wikibase-error-save-timeout' => 'Αντιμετωπίζουμε τεχνικές δυσκολίες, και εξαιτίας αυτού η  "Αποθήκευση" δεν ήταν δυνατό να ολοκληρωθεί.',
	'wikibase-error-remove-timeout' => 'Αντιμετωπίζουμε τεχνικές δυσκολίες, και εξαιτίας αυτού η "Κατάργηση" δεν ήταν δυνατό να ολοκληρωθεί.',
	'wikibase-error-autocomplete-response' => 'Απόκριση διακομιστή:$1',
	'wikibase-error-ui-client-error' => 'Απέτυχε η σύνδεση με τη σελίδα του πελάτη. Παρακαλώ προσπαθήστε ξανά αργότερα.',
	'wikibase-error-ui-no-external-page' => 'Η συγκεκριμένη σελίδα δεν βρέθηκε στην αντίστοιχη ιστοσελίδα.',
	'wikibase-error-ui-cant-edit' => 'Δεν σας επιτρέπεται να εκτελέσετε αυτήν την ενέργεια.',
	'wikibase-error-ui-no-permissions' => 'Δεν διαθέτετε επαρκή δικαιώματα για να εκτελέσετε αυτήν την ενέργεια.',
	'wikibase-error-ui-link-exists' => 'Δεν μπορείτε να συνδέσετε αυτή τη σελίδα επειδή ένα άλλο αντικείμενο ήδη συνδέει σε αυτό.',
	'wikibase-error-ui-session-failure' => 'Η συνεδρία σας έχει λήξει. Παρακαλώ συνδεθείτε ξανά.',
	'wikibase-error-ui-edit-conflict' => 'Υπάρχει σύγκρουση επεξεργασίας. Παρακαλούμε  φορτώσετε εκ νέου και αποθηκεύστε ξανά.',
	'wikibase-quantitydetails-upperbound' => 'Άνωτατο όριο',
	'wikibase-quantitydetails-lowerbound' => 'Κατώτερο όριο',
	'wikibase-quantitydetails-unit' => 'Μονάδα',
	'wikibase-replicationnote' => 'Παρακαλώ να λάβετε υπόψη ότι έως ότου οι αλλαγές γίνουν ορατές σε όλα τα wiki μπορεί να περάσουν μερικά λεπτά.',
	'wikibase-sitelinks-wikipedia' => 'Σελίδες της Βικιπαίδειας που συνδέονται με αυτό το αντικείμενο',
	'wikibase-sitelinks-sitename-columnheading' => 'Γλώσσα',
	'wikibase-sitelinks-siteid-columnheading' => 'Κωδικός',
	'wikibase-sitelinks-link-columnheading' => 'Σελίδες που συνδέονται',
	'wikibase-tooltip-error-details' => 'Λεπτομέρειες',
	'wikibase-undeserializable-value' => 'Η τιμή δεν είναι έγκυρη και δεν μπορεί να εμφανιστεί.',
	'wikibase-validator-bad-type' => '$2 αντί για $1',
	'wikibase-validator-too-short' => 'Πρέπει να οι χαρακτήρες να είναι τουλάχιστον  {{PLURAL:$1|ένας|$1}}',
	'wikibase-validator-malformed-value' => 'Ακατάλληλη εισαγωγή: $1',
	'wikibase-validator-bad-entity-id' => 'Ακατάλληλο αναγνωριστικό:$1',
	'wikibase-validator-bad-entity-type' => 'Απροσδόκητη τύπος οντότητας $1',
	'wikibase-validator-no-such-entity' => 'το $1 δεν βρέθηκε',
	'wikibase-validator-no-such-property' => 'Η ιδιότητα $1  δεν βρέθηκε',
	'wikibase-validator-bad-value-type' => 'Εσφαλμένη τιμή τύπου  $1 , με την αναμενόμενη$2',
	'wikibase-validator-bad-url' => 'Εσφαλμένη διεύθυνση URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Μη υποστηριζόμενο σύστημα URL:$1',
	'wikibase-validator-bad-http-url' => 'Ακατάλληλο HTTP URL: $1',
	'wikibase-validator-unknown-unit' => 'Άγνωστη μονάδα: $1',
	'datatypes-type-wikibase-item' => 'Αντικείμενο',
	'datatypes-type-commonsMedia' => 'αρχείο πολυμέσων των Commons',
	'version-wikibase' => 'Wikibase',
);

/** Esperanto (Esperanto)
 * @author ArnoLagrange
 * @author KuboF
 */
$messages['eo'] = array(
	'wikibase-lib-desc' => 'Enhavas komunajn funckiojn por Vikidatumaj kaj por la Vikidatuma klienta etendaĵo',
	'wikibase-entity-item' => 'ero',
	'wikibase-entity-property' => 'Atributo',
	'wikibase-entity-query' => 'Serĉomendo',
	'wikibase-deletedentity-item' => 'Forigita ero',
	'wikibase-diffview-reference' => 'referenco',
	'wikibase-diffview-label' => 'etikedo',
	'wikibase-diffview-alias' => 'kromnomoj',
	'wikibase-diffview-description' => 'priskribo',
	'wikibase-diffview-link' => 'ligiloj',
	'wikibase-error-unexpected' => 'Okazis neatendita eraro.',
	'wikibase-error-save-generic' => 'Eraro okazis dum konservado, sekve viaj ŝanĝoj ne estis konservitaj',
	'wikibase-error-remove-generic' => 'Eraro okazis dum forigado, sekve viaj ŝanĝoj ne estis konservitaj',
	'wikibase-error-save-connection' => 'Konekteraro okazis dum konservado, sekve viaj ŝanĝoj ne estis konservitaj. Bonvolu kontroli vian retkonekton.',
	'wikibase-error-remove-connection' => 'Konekteraro okazis dum forigado, sekve viaj ŝanĝoj ne estis konservitaj. Bonvolu kontroli vian retkonekton.',
	'wikibase-error-save-timeout' => 'Ni spertas teĥnikajn problemojn, kaj tial via konservado ne povis esti plenumita',
	'wikibase-error-remove-timeout' => 'Ni spertas teĥnikajn problemojn, kaj tial via forigado ne povis esti plenumita',
	'wikibase-error-autocomplete-connection' => 'Ne eblis peti la retejan API-on. Bonvolu reprovi poste.',
	'wikibase-error-autocomplete-response' => 'Servilo respondis: $1',
	'wikibase-error-ui-client-error' => 'Konekto al la klienta paĝo malsukcesis. Bonvolu provi pli poste.',
	'wikibase-error-ui-no-external-page' => 'La menciita artikolo ne povas esti trovita en la koresponda vikio.',
	'wikibase-error-ui-cant-edit' => 'Vi ne rajtas plenumi ĉi tiun agon.',
	'wikibase-error-ui-no-permissions' => 'Vi ne havas sufiĉajn rajtojn por plenumi ĉi tiun agon',
	'wikibase-error-ui-link-exists' => 'Vi ne povas ligi al ĉi tiu paĝo ĉar alia ero ajm ligas al ĝi.',
	'wikibase-error-ui-session-failure' => 'Via sesio ĉesis. bonvolu denove ensaluti.',
	'wikibase-error-ui-edit-conflict' => 'Estas redaktokonflikto. Bonvolu reŝargi kaj konservi denove.',
	'wikibase-replicationnote' => 'Bonvolu noti, ke povas daŭri kelkajn minutojn ĝis la ŝanĝoj estos videblaj en ĉiuj vikioj.',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingvo',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodo',
	'wikibase-sitelinks-link-columnheading' => 'Ligata paĝo',
	'wikibase-tooltip-error-details' => 'Detaloj',
	'wikibase-validator-no-such-entity' => '$1 ne trovita',
	'wikibase-validator-bad-value' => 'Malvalida valoro: $1',
	'datatypes-type-wikibase-item' => 'Ero',
	'datatypes-type-commonsMedia' => 'Multrimeda dosiero en Komunejo',
	'version-wikibase' => 'Vikibazo',
);

/** Spanish (español)
 * @author Armando-Martin
 * @author Dalton2
 * @author Ihojose
 * @author Pegna
 * @author Savh
 * @author Vivaelcelta
 */
$messages['es'] = array(
	'wikibase-lib-desc' => 'Contiene una funcionalidad común para las extensiones Wikibase y cliente de Wikibase.',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'propiedad',
	'wikibase-entity-query' => 'consulta',
	'wikibase-deletedentity-item' => 'Elemento borrado',
	'wikibase-deletedentity-property' => 'Propiedad borrada',
	'wikibase-deletedentity-query' => 'Búsqueda borrada',
	'wikibase-diffview-reference' => 'referencia',
	'wikibase-diffview-rank' => 'clasificación',
	'wikibase-diffview-qualifier' => 'calificador',
	'wikibase-diffview-label' => 'etiqueta',
	'wikibase-diffview-alias' => 'alias',
	'wikibase-diffview-description' => 'descripción',
	'wikibase-diffview-link' => 'enlaces',
	'wikibase-error-unexpected' => 'Ocurrió un error inesperado.',
	'wikibase-error-save-generic' => 'Hubo un error al intentar hacer el guardado, por lo que no se pudieron completar los cambios.',
	'wikibase-error-remove-generic' => 'Hubo un error al intentar realizar la eliminación, y debido a esto no se pudieron completar los cambios.',
	'wikibase-error-save-connection' => 'Ha ocurrido un error de conexión al intentar guardar, y debido a esto no se pudieron completar los cambios. Compruebe su conexión a internet.',
	'wikibase-error-remove-connection' => 'Hubo un error de conexión al intentar eliminar, y debido a esto no se pudieron completar tus cambios. Comprueba tu conexión a internet.',
	'wikibase-error-save-timeout' => 'Estamos experimentando dificultades técnicas, y debido a esto no se pudieron terminar de guardar tus cambios.',
	'wikibase-error-remove-timeout' => 'Estamos experimentando dificultades técnicas, y debido a esto no se pudo finalizar la eliminación.',
	'wikibase-error-autocomplete-connection' => 'No se pudo consultar en la API de sitio. Inténtalo de nuevo más tarde.',
	'wikibase-error-autocomplete-response' => 'El servidor respondió: $1',
	'wikibase-error-ui-client-error' => 'Error en la conexión a la página del cliente. Por favor, inténtalo más tarde.',
	'wikibase-error-ui-no-external-page' => 'No se encontró el artículo especificado en el sitio correspondiente.',
	'wikibase-error-ui-cant-edit' => 'No estás autorizado para realizar esta acción.',
	'wikibase-error-ui-no-permissions' => 'No tienes suficientes derechos para realizar esta acción.',
	'wikibase-error-ui-link-exists' => 'No se puede vincular a esta página porque otro elemento ya se vincula a ella.',
	'wikibase-error-ui-session-failure' => 'Tu sesión ha caducado. Inicia la sesión de nuevo.',
	'wikibase-error-ui-edit-conflict' => 'Hay un conflicto de edición. Por favor, vuelve a cargar y guarda de nuevo.',
	'wikibase-timedetails-time' => 'Tiempo',
	'wikibase-timedetails-timezone' => 'Zona horaria',
	'wikibase-timedetails-calendar' => 'Calendario',
	'wikibase-timedetails-precision' => 'Precisión',
	'wikibase-timedetails-before' => 'Antes',
	'wikibase-timedetails-after' => 'Después',
	'wikibase-globedetails-longitude' => 'Longitud',
	'wikibase-globedetails-latitude' => 'Latitud',
	'wikibase-globedetails-precision' => 'Precisión',
	'wikibase-globedetails-globe' => 'Globo',
	'wikibase-replicationnote' => 'Tenga en cuenta que puede tardar varios minutos, hasta que los cambios sean visibles en todas las wikis.',
	'wikibase-sitelinks-wikipedia' => 'Páginas de Wikipedia con enlaces hacia este elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Idioma',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Artículo enlazado', # Fuzzy
	'wikibase-tooltip-error-details' => 'Detalles',
	'wikibase-validator-no-such-entity' => 'No se encontró "$1"',
	'wikibase-validator-no-such-property' => 'No se encontró la propiedad $1',
	'wikibase-validator-bad-value' => 'Valor ilegal: $1',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'Archivo multimedia de Commons',
	'version-wikibase' => 'Wikibase',
);

/** Estonian (eesti)
 * @author Avjoska
 * @author Pikne
 */
$messages['et'] = array(
	'wikibase-entity-item' => 'üksus',
	'wikibase-entity-property' => 'omadus',
	'wikibase-entity-query' => 'päring',
	'wikibase-deletedentity-item' => 'Kustutatud üksus',
	'wikibase-deletedentity-property' => 'Kustutatud omadus',
	'wikibase-deletedentity-query' => 'Kustutatud päring',
	'wikibase-diffview-reference' => 'viide',
	'wikibase-diffview-rank' => 'järk',
	'wikibase-diffview-rank-preferred' => 'Eelisjärk',
	'wikibase-diffview-rank-normal' => 'Tavajärk',
	'wikibase-diffview-rank-deprecated' => 'Igandjärk',
	'wikibase-diffview-qualifier' => 'näitaja',
	'wikibase-diffview-label' => 'silt',
	'wikibase-diffview-alias' => 'rööpkujud',
	'wikibase-diffview-description' => 'kirjeldus',
	'wikibase-diffview-link' => 'lingid',
	'wikibase-error-unexpected' => 'Ilmnes tundmatu tõrge.',
	'wikibase-error-save-generic' => 'Salvestamisel ilmnes tõrge ja seetõttu ei saanud sinu muudatusi lõpule viia.',
	'wikibase-error-remove-generic' => 'Eemaldamisel ilmnes tõrge ja seetõttu ei saanud sinu muudatusi lõpule viia.',
	'wikibase-error-save-connection' => 'Salvestamisel ilmnes tõrge ja seetõttu ei saanud sinu muudatusi lõpule viia. Palun kontrolli oma internetiühendust.',
	'wikibase-error-remove-connection' => 'Eemaldamisel ilmnes tõrge ja seetõttu ei saanud sinu muudatusi lõpule viia. Palun kontrolli oma internetiühendust.',
	'wikibase-error-save-timeout' => 'Praegu esinevate tehniliste probleemide tõttu ei saa salvestamist lõpule viia.',
	'wikibase-error-remove-timeout' => 'Praegu esinevate tehniliste probleemide tõttu ei saa eemaldamist lõpule viia.',
	'wikibase-error-autocomplete-connection' => 'Võrgukoha API päringut ei saa teha. Palun proovi hiljem uuesti.',
	'wikibase-error-autocomplete-response' => 'Serveri vastus: $1',
	'wikibase-error-ui-client-error' => 'Ühendamine kliendi leheküljega ebaõnnestus. Palun proovi hiljem uuesti.',
	'wikibase-error-ui-no-external-page' => 'Määratud artiklit ei õnnestu vastavast võrgukohast leida.',
	'wikibase-error-ui-cant-edit' => 'Sul pole lubatud seda toimingut sooritada.',
	'wikibase-error-ui-no-permissions' => 'Sul pole selle toimingu sooritamiseks vajalikke õigusi.',
	'wikibase-error-ui-link-exists' => 'Sellele leheküljele ei saa linkida, sest teine üksus juba lingib sellele.',
	'wikibase-error-ui-session-failure' => 'Seanss on aegunud. Palun logi uuesti sisse.',
	'wikibase-error-ui-edit-conflict' => 'Esines redigeerimiskonflikt. Palun värskenda lehekülge ja salvesta uuesti.',
	'wikibase-quantitydetails-unit' => 'Ühik',
	'wikibase-replicationnote' => 'Palun pane tähele, et võib kuluda mitu minutit, enne kui muudatused on kõigis vikides nähtavad.',
	'wikibase-sitelinks-wikipedia' => 'Sellele üksusele viitavad Vikipeedia-leheküljed',
	'wikibase-sitelinks-sitename-columnheading' => 'Keel',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Võrgukoht',
	'wikibase-sitelinks-siteid-columnheading' => 'Kood',
	'wikibase-sitelinks-link-columnheading' => 'Lingitud lehekülg',
	'wikibase-tooltip-error-details' => 'Üksikasjad',
	'wikibase-undeserializable-value' => 'Väärtus on vigane ja seda ei saa kuvada.',
	'wikibase-validator-bad-type' => 'Tüübi $1 asemel $2',
	'wikibase-validator-too-long' => 'Ei tohi olla {{PLURAL:$1|ühest|$1}} märgist pikem.',
	'wikibase-validator-too-short' => 'Peab olema vähemalt {{PLURAL:$1|ühe|$1}} märgi pikkune.',
	'wikibase-validator-too-high' => 'Pole vahemikus, ei tohi olla suurem kui $1.',
	'wikibase-validator-too-low' => 'Pole vahemikus, ei tohi olla väiksem kui $1.',
	'wikibase-validator-malformed-value' => 'Rikutud sisend: $1',
	'wikibase-validator-bad-entity-id' => 'Rikutud identifikaator: $1',
	'wikibase-validator-bad-entity-type' => 'Ootamatu olemitüüp $1',
	'wikibase-validator-no-such-entity' => 'Olemit $1 ei leidu',
	'wikibase-validator-no-such-property' => 'Omadust $1 ei leidu',
	'wikibase-validator-bad-value' => 'Lubamatu väärtus: $1',
	'wikibase-validator-bad-value-type' => 'Vigane väärtuse tüüp $1, oodatav $2',
	'wikibase-validator-bad-url' => 'Rikutud internetiaadress: $1',
	'wikibase-validator-bad-http-url' => 'Rikutud HTTP-internetiaadress: $1',
	'wikibase-validator-bad-mailto-url' => "Rikutud ''mailto''-internetiaadress: $1",
	'wikibase-validator-unknown-unit' => 'Tundmatu ühik: $1',
	'datatypes-type-wikibase-item' => 'Üksus',
	'datatypes-type-commonsMedia' => 'Commonsi meediafail',
	'version-wikibase' => 'Vikibaas',
);

/** Basque (euskara)
 * @author පසිඳු කාවින්ද
 */
$messages['eu'] = array(
	'wikibase-tooltip-error-details' => 'Xehetasunak',
);

/** Persian (فارسی)
 * @author Alireza
 * @author Armin1392
 * @author Calak
 * @author Dalba
 * @author Ebraminio
 * @author Ladsgroup
 * @author Mahan
 * @author Reza1615
 * @author Rtemis
 * @author ZxxZxxZ
 */
$messages['fa'] = array(
	'wikibase-lib-desc' => 'نگهداری قابلیت‌های اساسی برای ویکی‌بیس و افزونه‌های کارخواه ویکی‌بیس',
	'wikibase-entity-item' => 'آیتم',
	'wikibase-entity-property' => 'ویژگی',
	'wikibase-entity-query' => 'کوئری',
	'wikibase-deletedentity-item' => 'آیتم حذف‌شده',
	'wikibase-deletedentity-property' => 'خصوصیات حذف شده',
	'wikibase-deletedentity-query' => 'جستار حذف‌شده',
	'wikibase-diffview-reference' => 'منبع',
	'wikibase-diffview-rank' => 'رتبه',
	'wikibase-diffview-rank-preferred' => 'رتبهٔ مورد نظر',
	'wikibase-diffview-rank-normal' => 'رتبهٔ عادی',
	'wikibase-diffview-rank-deprecated' => 'رتبهٔ بد',
	'wikibase-diffview-qualifier' => 'گستره‌نما',
	'wikibase-diffview-label' => 'برچسب',
	'wikibase-diffview-alias' => 'نام‌های دیگر',
	'wikibase-diffview-description' => 'توضیحات',
	'wikibase-diffview-link' => 'پیوندها',
	'wikibase-error-unexpected' => 'یک خطای غیرمنتظره رخ داد.',
	'wikibase-error-save-generic' => 'خطایی هنگام تلاش برای انجام ذخیره‌سازی رخ داد و به این خاطر تکمیل تغییراتتان ناموفق بود.',
	'wikibase-error-remove-generic' => 'خطایی هنگام تلاش برای حذف‌کردن رخ داد و به این خاطر تکمیل تغییراتتان ناموفق بود.',
	'wikibase-error-save-connection' => 'هنگام انجام ذخیره‌سازی خطایی در اتصال رخ داد، و به این دلیل امکان تکمیل تغییرات شما نبود. خواهشمندیم اتصال اینترنتی خود را بررسی کنید.',
	'wikibase-error-remove-connection' => 'هنگام حذف‌کردن خطایی رخ داد و به این دلیل امکان تکمیل تغییراتتان نبود. خواهشمندیم اتصال اینترنتی خود را بررسی کنید.',
	'wikibase-error-save-timeout' => 'در حال حاضر با مشکلات فنی‌ای روبه‌رو شده‌ایم و به همین خاطر «ذخیره‌سازی» شما کامل نشد.',
	'wikibase-error-remove-timeout' => 'در حال حاضر با مشکلات فنیی‌ای روبه‌رو شده‌ایم و به همین خاطر عمل «حذف‌کردن» کامل نشد.',
	'wikibase-error-autocomplete-connection' => 'امکان پرسمان از واسط برنامه‌نویسی کاربردی  وب‌گاه وجود نداشت. لطفاً بعداً امتحان کنید.',
	'wikibase-error-autocomplete-response' => 'پاسخ سرور: $1',
	'wikibase-error-ui-client-error' => 'اتصال به صفحهٔ کارخواه ناموفق بود. لطفاً بعداً امتحان کنید.',
	'wikibase-error-ui-no-external-page' => 'مقالهٔ یادشده در وب‌گاه مربوطه پیدا نشد.',
	'wikibase-error-ui-cant-edit' => 'شما مجاز به انجام این عمل نیستید.',
	'wikibase-error-ui-no-permissions' => 'شما دسترسی‌های لازم برای انجام این عمل را ندارید.',
	'wikibase-error-ui-link-exists' => 'نمی‌توانید به این صفحه پیوند دهید چون آیتم دیگری از قبل به آن پیوند داده‌است.',
	'wikibase-error-ui-session-failure' => 'نشست شما منقضی شده‌است. لطفاً دوباره به سامانه وارد شوید.',
	'wikibase-error-ui-edit-conflict' => 'تعارض ویرایشی رخ داده است. خواهشمندیم از نو بارگذاری و ذخیره کنید.',
	'wikibase-quantitydetails-amount' => 'مبلغ',
	'wikibase-quantitydetails-upperbound' => 'حد بالا',
	'wikibase-quantitydetails-lowerbound' => 'حد پایین',
	'wikibase-quantitydetails-unit' => 'واحد',
	'wikibase-timedetails-time' => 'زمان',
	'wikibase-timedetails-isotime' => 'مهر زمان آی‌اس‌اُ',
	'wikibase-timedetails-timezone' => 'منطقهٔ زمان',
	'wikibase-timedetails-calendar' => 'تقویم',
	'wikibase-timedetails-precision' => 'وضوح',
	'wikibase-timedetails-before' => 'قبل از',
	'wikibase-timedetails-after' => 'پس از',
	'wikibase-globedetails-longitude' => 'طول جغرافیایی',
	'wikibase-globedetails-latitude' => 'عرض جغرافیایی',
	'wikibase-globedetails-precision' => 'وضوح',
	'wikibase-globedetails-globe' => 'جهان',
	'wikibase-replicationnote' => 'لطفاً توجه کنید چند دقیقه زمان لازم است تا تغییرات در همهٔ ویکی‌ها قابل مشاهده باشد.',
	'wikibase-sitelinks-wikipedia' => 'صفحه‌های ویکی‌پدیا که به این آیتم پیوند دارند',
	'wikibase-sitelinks-sitename-columnheading' => 'زبان',
	'wikibase-sitelinks-sitename-columnheading-special' => 'وب‌گاه',
	'wikibase-sitelinks-siteid-columnheading' => 'کد',
	'wikibase-sitelinks-link-columnheading' => 'صفحهٔ پیوندداده‌شده',
	'wikibase-tooltip-error-details' => 'جزئیات',
	'wikibase-undeserializable-value' => 'این مقدار معتبر نیست و قابل نمایش نیست.',
	'wikibase-validator-bad-type' => '$2 به جای $1',
	'wikibase-validator-too-long' => 'نباید بیشتر از {{PLURAL:$1|یک شناسه|$1 شناسه}} طول داشته باشد',
	'wikibase-validator-too-short' => 'باید بیشتر از {{PLURAL:$1|یک شناسه|$1 شناسه}} طول داشته باشد',
	'wikibase-validator-too-high' => 'خارج از محدوده، نباید بیش از $1 باشد',
	'wikibase-validator-too-low' => 'خارج از محدوده، نباید کمتر از $1 باشد',
	'wikibase-validator-malformed-value' => 'ورودی ناقص:$1',
	'wikibase-validator-bad-entity-id' => 'شناسه ناقص:$1',
	'wikibase-validator-bad-entity-type' => 'نوع ورودی غیرمنتظره  $1',
	'wikibase-validator-no-such-entity' => '$1 یافت نشد',
	'wikibase-validator-no-such-property' => 'خصوصیت $1 یافت نشد',
	'wikibase-validator-bad-value' => 'مقدار نامجاز: $1',
	'wikibase-validator-bad-value-type' => 'نوع دادۀ غلط $1 برای $2',
	'wikibase-validator-bad-url' => 'یوآرال ناقص: $1',
	'wikibase-validator-bad-url-scheme' => 'یوآرال پوشش داده نشده: $1',
	'wikibase-validator-bad-http-url' => 'یوآرال HTTP ناقص: $1',
	'wikibase-validator-bad-mailto-url' => 'یوآرال رایانامۀ ناقص: $1',
	'wikibase-validator-unknown-unit' => 'واحد ناشناخته: $1',
	'datatypes-type-wikibase-item' => 'آیتم',
	'datatypes-type-commonsMedia' => 'پرونده‌های ویکی‌انبار',
	'version-wikibase' => 'ویکی‌بیس',
);

/** Finnish (suomi)
 * @author Crt
 * @author Harriv
 * @author Nedergard
 * @author Nike
 * @author Stryn
 * @author VezonThunder
 */
$messages['fi'] = array(
	'wikibase-lib-desc' => 'Sisältää Wikibase- ja Wikibase Client -laajennuksille yhteistä toiminnallisuutta',
	'wikibase-entity-item' => 'kohde',
	'wikibase-entity-property' => 'ominaisuus',
	'wikibase-entity-query' => 'kysely',
	'wikibase-deletedentity-item' => 'Kohde poistettu',
	'wikibase-deletedentity-property' => 'Ominaisuus poistettu',
	'wikibase-deletedentity-query' => 'Kysely poistettu',
	'wikibase-diffview-reference' => 'lähde',
	'wikibase-diffview-rank' => 'sija',
	'wikibase-diffview-rank-preferred' => 'Suosittu asema',
	'wikibase-diffview-rank-normal' => 'Tavallinen asema',
	'wikibase-diffview-rank-deprecated' => 'Vanhentunut asema',
	'wikibase-diffview-qualifier' => 'tarkenne',
	'wikibase-diffview-label' => 'nimi',
	'wikibase-diffview-alias' => 'aliakset',
	'wikibase-diffview-description' => 'kuvaus',
	'wikibase-diffview-link' => 'linkit',
	'wikibase-error-unexpected' => 'Odottamaton virhe.',
	'wikibase-error-save-generic' => 'Tallennus epäonnistui. Muutoksiasi ei voitu toteuttaa.',
	'wikibase-error-remove-generic' => 'Poistaminen epäonnistui. Muutoksiasi ei voitu toteuttaa.',
	'wikibase-error-save-connection' => 'Tallennettaessa tapahtui yhteysvirhe. Muutoksiasi ei voitu toteuttaa. Tarkista Internet-yhteytesi.',
	'wikibase-error-remove-connection' => 'Poistaminen epäonnistui verkkovirheen takia. Muutoksiasi ei voitu toteuttaa. Tarkista Internet-yhteytesi.',
	'wikibase-error-save-timeout' => 'Sivustolla on teknisiä ongelmia. Tallennustasi ei voitu toteuttaa.',
	'wikibase-error-remove-timeout' => 'Sivustolla on teknisiä ongelmia. Poistoasi ei voitu toteuttaa.',
	'wikibase-error-autocomplete-connection' => 'Kysely sivuston rajapinnalta epäonnistui. Yritä myöhemmin uudelleen.',
	'wikibase-error-autocomplete-response' => 'Palvelin vastasi: $1',
	'wikibase-error-ui-client-error' => 'Yhteys asiakassivuun epäonnistui. Yritä myöhemmin uudelleen.',
	'wikibase-error-ui-no-external-page' => 'Määritettyä artikkelia ei löytynyt vastaavalta sivustolta.',
	'wikibase-error-ui-cant-edit' => 'Sinulla ei ole oikeutta suorittaa tätä toimintoa.',
	'wikibase-error-ui-no-permissions' => 'Sinulla ei ole tämän toiminnon suorittamiseen vaadittavia oikeuksia.',
	'wikibase-error-ui-link-exists' => 'Et voi lisätä linkkiä tähän sivuun, koska toisessa kohteessa on jo sama linkki.',
	'wikibase-error-ui-session-failure' => 'Istuntosi on vanhentunut. Kirjaudu sisään uudelleen.',
	'wikibase-error-ui-edit-conflict' => 'Tapahtui muokkausristiriita. Päivitä sivu ja tallenna uudelleen.',
	'wikibase-quantitydetails-amount' => 'Summa',
	'wikibase-quantitydetails-upperbound' => 'Yläraja',
	'wikibase-quantitydetails-lowerbound' => 'Alaraja',
	'wikibase-quantitydetails-unit' => 'Yksikkö',
	'wikibase-replicationnote' => 'Huomaa, että voi kestää useita minuutteja ennen kuin muutokset näkyvät kaikissa wikeissä.',
	'wikibase-sitelinks-wikipedia' => 'Tähän kohteeseen linkitetyt Wikipedia-sivut',
	'wikibase-sitelinks-sitename-columnheading' => 'Kieli',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sivusto',
	'wikibase-sitelinks-siteid-columnheading' => 'Koodi',
	'wikibase-sitelinks-link-columnheading' => 'Linkitetty sivu',
	'wikibase-tooltip-error-details' => 'Tiedot',
	'wikibase-undeserializable-value' => 'Arvo on virheellinen, eikä sitä voida näyttää.',
	'wikibase-validator-too-long' => 'Saa olla enintään {{PLURAL:$1|yhden merkin|$1 merkin}} pituinen',
	'wikibase-validator-too-short' => 'Pitää olla vähintään {{PLURAL:$1|yhden merkin|$1 merkin}} pituinen',
	'wikibase-validator-no-such-entity' => '$1 ei löydy',
	'wikibase-validator-no-such-property' => 'Ominaisuutta $1 ei löydy',
	'wikibase-validator-bad-value' => 'Virheellinen arvo: $1',
	'wikibase-validator-bad-url-scheme' => 'Ei-tuettu URL-järjestelmä: $1',
	'wikibase-validator-unknown-unit' => 'Tuntematon yksikkö: $1',
	'datatypes-type-wikibase-item' => 'Kohde',
	'datatypes-type-commonsMedia' => 'Commonsin mediatiedosto',
	'version-wikibase' => 'Wikibase',
);

/** French (français)
 * @author Alno
 * @author Arkanosis
 * @author Boniface
 * @author Crochet.david
 * @author DavidL
 * @author Gomoko
 * @author Jean-Frédéric
 * @author Jgaignerot
 * @author Ltrlg
 * @author Metroitendo
 * @author NemesisIII
 * @author Nnemo
 * @author Tititou36
 * @author Wyz
 */
$messages['fr'] = array(
	'wikibase-lib-desc' => 'Regroupe des fonctionnalités communes aux extensions Wikibase et Wikibase Client',
	'wikibase-entity-item' => 'élément',
	'wikibase-entity-property' => 'propriété',
	'wikibase-entity-query' => 'requête',
	'wikibase-deletedentity-item' => 'Élément supprimé',
	'wikibase-deletedentity-property' => 'Propriété supprimée',
	'wikibase-deletedentity-query' => 'Requête supprimée',
	'wikibase-diffview-reference' => 'référence',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-rank-preferred' => 'Rang privilégié',
	'wikibase-diffview-rank-normal' => 'Rang normal',
	'wikibase-diffview-rank-deprecated' => 'Rang déprécié',
	'wikibase-diffview-qualifier' => 'qualificateur',
	'wikibase-diffview-label' => 'libellé',
	'wikibase-diffview-alias' => 'alias',
	'wikibase-diffview-description' => 'description',
	'wikibase-diffview-link' => 'liens',
	'wikibase-error-unexpected' => 'Une erreur inattendue s’est produite.',
	'wikibase-error-save-generic' => "Une erreur est survenue lors de l'enregistrement, en conséquence, vos modifications n'ont pas pu être prises en compte.",
	'wikibase-error-remove-generic' => "Une erreur est survenue lors de la suppression, en conséquence, vos modifications n'ont pas pu être prises en compte.",
	'wikibase-error-save-connection' => "Une erreur de connexion est survenue lors de l'enregistrement, en conséquence, vos modifications n'ont pas pu être prises en compte. Vérifiez votre connexion Internet.",
	'wikibase-error-remove-connection' => "Une erreur de connexion est survenue lors de la suppression, en conséquence, vos modifications n'ont pas pu être prises en compte. Vérifiez votre connexion Internet.",
	'wikibase-error-save-timeout' => 'Nous rencontrons actuellement quelques problèmes techniques, la sauvegarde que vous avez demandée ne peut être réalisée.',
	'wikibase-error-remove-timeout' => "Nous rencontrons quelques problèmes techniques, en conséquence la suppression que vous avez demandée n'a pas pu être réalisée.",
	'wikibase-error-autocomplete-connection' => "Impossible d'interroger l'API du site. Veuillez réessayer plus tard.",
	'wikibase-error-autocomplete-response' => 'Le serveur a répondu&nbsp;: $1',
	'wikibase-error-ui-client-error' => 'Échec de la connexion à la page client. Veuillez réessayer ultérieurement.',
	'wikibase-error-ui-no-external-page' => "L'article spécifié est introuvable sur le site correspondant.",
	'wikibase-error-ui-cant-edit' => 'Vous n’êtes pas autorisé(e) à effectuer cette action.',
	'wikibase-error-ui-no-permissions' => 'Vous n’avez pas de droits suffisants pour effectuer cette action.',
	'wikibase-error-ui-link-exists' => "Vous ne pouvez pas faire de lien vers cette page parce qu'un autre élément la référence déjà.",
	'wikibase-error-ui-session-failure' => 'Votre session a expiré. Veuillez vous connecter à nouveau.',
	'wikibase-error-ui-edit-conflict' => 'Il y a conflit d’édition. Rechargez la page et enregistrez de nouveau.',
	'wikibase-quantitydetails-amount' => 'Montant',
	'wikibase-quantitydetails-upperbound' => 'Limite supérieure',
	'wikibase-quantitydetails-lowerbound' => 'Limite inférieure',
	'wikibase-quantitydetails-unit' => 'Unité',
	'wikibase-timedetails-time' => 'Heure',
	'wikibase-timedetails-isotime' => 'Horodatage ISO',
	'wikibase-timedetails-timezone' => 'Zone horaire',
	'wikibase-timedetails-calendar' => 'Calendrier',
	'wikibase-timedetails-precision' => 'Précision',
	'wikibase-timedetails-before' => 'Avant',
	'wikibase-timedetails-after' => 'Après',
	'wikibase-globedetails-longitude' => 'Longitude',
	'wikibase-globedetails-latitude' => 'Latitude',
	'wikibase-globedetails-precision' => 'Précision',
	'wikibase-globedetails-globe' => 'Globe',
	'wikibase-replicationnote' => 'Veuillez noter que cela peut prendre plusieurs minutes avant que les modifications soient visibles sur tous les wikis.',
	'wikibase-sitelinks-wikipedia' => 'Pages de Wikipédia liées à cet élément',
	'wikibase-sitelinks-sitename-columnheading' => 'Langue',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Page liée',
	'wikibase-tooltip-error-details' => 'Détails',
	'wikibase-undeserializable-value' => 'La valeur n’est pas valide et ne peut pas être affichée.',
	'wikibase-validator-bad-type' => '$2 au lieu de $1',
	'wikibase-validator-too-long' => 'Ne doit pas dépasser {{PLURAL:$1|un caractère|$1 caractères}} de long',
	'wikibase-validator-too-short' => 'Doit faire au moins {{PLURAL:$1|un caractère|$1 caractères}} de long',
	'wikibase-validator-too-high' => 'Hors limite, ne doit pas être supérieur à $1',
	'wikibase-validator-too-low' => 'Hors limite, ne doit pas être inférieur à $1',
	'wikibase-validator-malformed-value' => 'Entrée au mauvais format : $1',
	'wikibase-validator-bad-entity-id' => 'ID au mauvais format : $1',
	'wikibase-validator-bad-entity-type' => 'Type d’entité $1 non prévu',
	'wikibase-validator-no-such-entity' => '$1 non trouvé',
	'wikibase-validator-no-such-property' => 'Propriété $1 non trouvée',
	'wikibase-validator-bad-value' => 'Valeur interdite : $1',
	'wikibase-validator-bad-value-type' => 'Mauvais type de valeur $1, $2 attendu',
	'wikibase-validator-bad-url' => 'URL mal formée : $1',
	'wikibase-validator-bad-url-scheme' => 'Schéma d’URL non supporté : $1',
	'wikibase-validator-bad-http-url' => 'URL HTTP mal formée : $1',
	'wikibase-validator-bad-mailto-url' => 'URL mailto mal formée : $1',
	'wikibase-validator-unknown-unit' => 'Unité inconnue : $1',
	'datatypes-type-wikibase-item' => 'Élément',
	'datatypes-type-commonsMedia' => 'Fichier multimédia de Commons',
	'version-wikibase' => 'Wikibase',
);

/** Franco-Provençal (arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'wikibase-entity-item' => 'èlèment',
	'wikibase-entity-property' => 'propriètât',
	'wikibase-entity-query' => 'demanda',
	'wikibase-error-autocomplete-response' => 'Lo sèrvior at rèpondu : $1',
	'wikibase-sitelinks-sitename-columnheading' => 'Lengoua',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Articllo liyê', # Fuzzy
	'wikibase-tooltip-error-details' => 'Dètalys',
	'datatypes-type-wikibase-item' => 'Èlèment',
	'datatypes-type-commonsMedia' => 'Fichiér mèdia de Commons',
);

/** Galician (galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'wikibase-lib-desc' => 'Contén funcionalidades comúns para as extensións Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'propiedade',
	'wikibase-entity-query' => 'pescuda',
	'wikibase-deletedentity-item' => 'Elemento borrado',
	'wikibase-deletedentity-property' => 'Propiedade borrada',
	'wikibase-deletedentity-query' => 'Pescuda borrada',
	'wikibase-diffview-reference' => 'referencia',
	'wikibase-diffview-rank' => 'clasificación',
	'wikibase-diffview-rank-preferred' => 'Rango privilexiado',
	'wikibase-diffview-rank-normal' => 'Rango normal',
	'wikibase-diffview-rank-deprecated' => 'Rango obsoleto',
	'wikibase-diffview-qualifier' => 'cualificador',
	'wikibase-diffview-label' => 'etiqueta',
	'wikibase-diffview-alias' => 'pseudónimos',
	'wikibase-diffview-description' => 'descrición',
	'wikibase-diffview-link' => 'ligazóns',
	'wikibase-error-unexpected' => 'Produciuse un erro inesperado.',
	'wikibase-error-save-generic' => 'Houbo un erro ao levar a cabo o gardado, polo que non se puideron completar os cambios.',
	'wikibase-error-remove-generic' => 'Houbo un erro ao levar a cabo a eliminación, polo que non se puideron completar os cambios.',
	'wikibase-error-save-connection' => 'Houbo un erro na conexión ao levar a cabo o gardado, polo que non se puideron completar os cambios. Comprobe a súa conexión á internet.',
	'wikibase-error-remove-connection' => 'Houbo un erro na conexión ao levar a cabo a eliminación, polo que non se puideron completar os cambios. Comprobe a súa conexión á internet.',
	'wikibase-error-save-timeout' => 'Estamos experimentando dificultades técnicas, polo que non se puido completar o gardado.',
	'wikibase-error-remove-timeout' => 'Estamos experimentando dificultades técnicas, polo que non se puido completar a eliminación.',
	'wikibase-error-autocomplete-connection' => 'Non se puido pescudar na API do sitio. Inténteo de novo máis tarde.',
	'wikibase-error-autocomplete-response' => 'O servidor respondeu: $1',
	'wikibase-error-ui-client-error' => 'Fallou a conexión coa páxina do cliente. Inténteo de novo máis tarde.',
	'wikibase-error-ui-no-external-page' => 'Non se puido atopar o artigo especificado no sitio correspondente.',
	'wikibase-error-ui-cant-edit' => 'Non lle está permitido levar a cabo esa acción.',
	'wikibase-error-ui-no-permissions' => 'Non ten os dereitos necesarios para levar a cabo esta acción.',
	'wikibase-error-ui-link-exists' => 'Non pode ligar con esta páxina porque xa hai outro elemento que liga con ela.',
	'wikibase-error-ui-session-failure' => 'A súa sesión caducou. Acceda ao sistema de novo.',
	'wikibase-error-ui-edit-conflict' => 'Hai un conflito de edición. Volva cargar a páxina e garde de novo.',
	'wikibase-quantitydetails-amount' => 'Cantidade',
	'wikibase-quantitydetails-upperbound' => 'Límite superior',
	'wikibase-quantitydetails-lowerbound' => 'Límite inferior',
	'wikibase-quantitydetails-unit' => 'Unidade',
	'wikibase-timedetails-time' => 'Hora',
	'wikibase-timedetails-isotime' => 'Data e hora ISO',
	'wikibase-timedetails-timezone' => 'Fuso horario',
	'wikibase-timedetails-calendar' => 'Calendario',
	'wikibase-timedetails-precision' => 'Precisión',
	'wikibase-timedetails-before' => 'Antes',
	'wikibase-timedetails-after' => 'Despois',
	'wikibase-globedetails-longitude' => 'Lonxitude',
	'wikibase-globedetails-latitude' => 'Latitude',
	'wikibase-globedetails-precision' => 'Precisión',
	'wikibase-globedetails-globe' => 'Globo',
	'wikibase-replicationnote' => 'Teña en conta que pode levar varios minutos que as modificacións sexan visibles en todos os wikis.',
	'wikibase-sitelinks-wikipedia' => 'Páxinas da Wikipedia con ligazóns cara a este elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingua',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sitio',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Páxina ligada',
	'wikibase-tooltip-error-details' => 'Detalles',
	'wikibase-undeserializable-value' => 'O valor non é válido e non se pode mostrar.',
	'wikibase-validator-bad-type' => '"$2" no canto de "$1"',
	'wikibase-validator-too-long' => 'Non debe superar {{PLURAL:$1|$1 carácter|$1 caracteres}} de longo',
	'wikibase-validator-too-short' => 'Debe ter, polo menos, {{PLURAL:$1|un carácter|$1 caracteres}} de longo',
	'wikibase-validator-too-high' => 'Fóra do rango; non debe ser maior que $1',
	'wikibase-validator-too-low' => 'Fóra do rango; non debe ser menor que $1',
	'wikibase-validator-malformed-value' => 'Entrada con formato non válido: $1',
	'wikibase-validator-bad-entity-id' => 'ID con formato non válido: $1',
	'wikibase-validator-bad-entity-type' => 'Tipo de entidade, "$1", inesperado',
	'wikibase-validator-no-such-entity' => 'Non se atopou "$1"',
	'wikibase-validator-no-such-property' => 'Non se atopou a propiedade $1',
	'wikibase-validator-bad-value' => 'Valor ilegal: $1',
	'wikibase-validator-bad-value-type' => '"$1" é un tipo de valor incorrecto; agardábase "$2"',
	'wikibase-validator-bad-url' => 'Enderezo URL con formato non válido: $1',
	'wikibase-validator-bad-url-scheme' => 'Esquema de enderezo URL non soportado: $1',
	'wikibase-validator-bad-http-url' => 'Enderezo URL HTTP con formato non válido: $1',
	'wikibase-validator-bad-mailto-url' => 'Enderezo URL mailto con formato non válido: $1',
	'wikibase-validator-unknown-unit' => 'Unidade descoñecida: $1',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'Ficheiro multimedia de Commons',
	'version-wikibase' => 'Wikibase',
);

/** Swiss German (Alemannisch)
 * @author Als-Holder
 */
$messages['gsw'] = array(
	'wikibase-lib-desc' => 'Stellt vu dr Erwyterige Wikibase un Wikibase Client gmeinsam gnutzti Funktione z Verfiegig',
	'wikibase-entity-item' => 'Objäkt',
	'wikibase-entity-property' => 'Eigeschaft',
	'wikibase-entity-query' => 'Abfrog',
	'datatypes-type-wikibase-item' => 'Objäkt',
	'datatypes-type-commonsMedia' => 'Mediedatei uf dr Commons',
);

/** Gujarati (ગુજરાતી)
 * @author KartikMistry
 */
$messages['gu'] = array(
	'wikibase-timedetails-time' => 'સમય',
	'wikibase-timedetails-timezone' => 'સમયવિસ્તાર',
	'wikibase-timedetails-before' => 'પહેલાં',
	'wikibase-timedetails-after' => 'પછી',
	'wikibase-globedetails-longitude' => 'રેખાંશ',
	'wikibase-globedetails-latitude' => 'અક્ષાંસ',
	'wikibase-globedetails-globe' => 'ગોળો',
);

/** Hebrew (עברית)
 * @author Amire80
 * @author Yona b
 */
$messages['he'] = array(
	'wikibase-lib-desc' => 'הפעולות המשותפות להרחבות Wikibase ו־Wikibase Client',
	'wikibase-entity-item' => 'פריט',
	'wikibase-entity-property' => 'מאפיין',
	'wikibase-entity-query' => 'שאילתה',
	'wikibase-deletedentity-item' => 'פריט מחוק',
	'wikibase-deletedentity-property' => 'מאפיין מחוק',
	'wikibase-deletedentity-query' => 'שאילתה מחוקה',
	'wikibase-diffview-reference' => 'הפניה',
	'wikibase-diffview-rank' => 'דירוג',
	'wikibase-diffview-rank-preferred' => 'דירוג מועדף',
	'wikibase-diffview-rank-normal' => 'דירוג רגיל',
	'wikibase-diffview-rank-deprecated' => 'דירוג ירוד',
	'wikibase-diffview-qualifier' => 'מבחין',
	'wikibase-diffview-label' => 'תווית',
	'wikibase-diffview-alias' => 'כינויים',
	'wikibase-diffview-description' => 'תיאור',
	'wikibase-diffview-link' => 'קישורים',
	'wikibase-error-unexpected' => 'אירעה שגיאה בלתי־צפויה.',
	'wikibase-error-save-generic' => 'אירעה שגיאה בעת ניסיון לבצע שמירה ובגלל זה לא ניתן להשלים את השינויים שלך.',
	'wikibase-error-remove-generic' => 'אירעה שגיאה בעת ניסיון לבצע הסרה ובגלל זה לא ניתן להשלים את השינויים שלך.',
	'wikibase-error-save-connection' => 'אירעה שגיאת התחברות בעת ניסיון לבצע שמירה ובגלל זה לא ניתן להשלים את השינויים שלך. נא לבדוק את חיבור האינטרנט שלך.',
	'wikibase-error-remove-connection' => 'אירעה שגיאה בעת ניסיון לבצע הסרה ובגלל זה לא ניתן להשלים את השינויים שלך. נא לבדוק את חיבור האינטרנט שלך.',
	'wikibase-error-save-timeout' => 'יש לנו קשיים טכניים ובגלל זה לא ניתן להשלים את השמירה שלך.',
	'wikibase-error-remove-timeout' => 'יש לנו קשיים טכניים ובגלל זה לא ניתן להשלים את ההסרה שלך.',
	'wikibase-error-autocomplete-connection' => 'לא ניתן לבצע שאילתה מתוך ה־API של האתר. נא לנסות שוב מאוחר יותר.',
	'wikibase-error-autocomplete-response' => 'השרת ענה: $1',
	'wikibase-error-ui-client-error' => 'החיבור לדף הלקוח נכשל. נא לנסות שוב מאוחר יותר.',
	'wikibase-error-ui-no-external-page' => 'הערך שהוזן לא נמצא באתר המתאים.',
	'wikibase-error-ui-cant-edit' => 'אין לך הרשאה לבצע את הפעולה הזאת.',
	'wikibase-error-ui-no-permissions' => 'אין לך מספיק הרשאות לבצע את הפעולה הזאת.',
	'wikibase-error-ui-link-exists' => 'אין לך אפשרות לקשר לדף הזה כי פריט אחר כבר מקשר אליו.',
	'wikibase-error-ui-session-failure' => 'השיחה שלך פגה. נא להיכנס שוב.',
	'wikibase-error-ui-edit-conflict' => 'אירעה התנגשות עריכה. נא לרענן את הדף ולשמור מחדש.',
	'wikibase-quantitydetails-amount' => 'כמות',
	'wikibase-quantitydetails-upperbound' => 'גבול עליון',
	'wikibase-quantitydetails-lowerbound' => 'גבול תחתון',
	'wikibase-quantitydetails-unit' => 'יחידה',
	'wikibase-timedetails-time' => 'זמן',
	'wikibase-timedetails-isotime' => 'חותם זמן של ISO',
	'wikibase-timedetails-timezone' => 'אזור זמן',
	'wikibase-timedetails-calendar' => 'לוח שנה',
	'wikibase-timedetails-precision' => 'דיוק',
	'wikibase-timedetails-before' => 'לפני',
	'wikibase-timedetails-after' => 'אחרי',
	'wikibase-globedetails-longitude' => 'קו אורך',
	'wikibase-globedetails-latitude' => 'קו־רוחב',
	'wikibase-globedetails-precision' => 'דיוק',
	'wikibase-globedetails-globe' => 'כדור',
	'wikibase-replicationnote' => 'יש לשים לב לכך שייקח מספר דקות עד שהשינויים יוצגו בכל אתרי הוויקי',
	'wikibase-sitelinks-wikipedia' => 'דפי ויקיפדיה שמקושרים לפריט הזה',
	'wikibase-sitelinks-sitename-columnheading' => 'שפה',
	'wikibase-sitelinks-sitename-columnheading-special' => 'אתר',
	'wikibase-sitelinks-siteid-columnheading' => 'קוד',
	'wikibase-sitelinks-link-columnheading' => 'דף מקושר',
	'wikibase-tooltip-error-details' => 'פרטים',
	'wikibase-undeserializable-value' => 'הערך אינו תקין ואינו יכול להיות מוצג.',
	'wikibase-validator-bad-type' => '$2 במקום $1',
	'wikibase-validator-too-long' => 'חייב להיות באורך של {{PLURAL:$1|תו אחד|$1 תווים}} לכל היותר',
	'wikibase-validator-too-short' => 'חייב להיות באורך של {{PLURAL:$1|תו אחד|$1 תווים}} לכל הפחות',
	'wikibase-validator-too-high' => 'מחוץ לטווח, צריך להיות מעל $1',
	'wikibase-validator-too-low' => 'מחוץ לטווח, צריך להיות מתחת ל{{GRAMMAR:תחילית|$1}}',
	'wikibase-validator-malformed-value' => 'קלט בלתי־תקין: $1',
	'wikibase-validator-bad-entity-id' => 'מזהה בלתי־תקין: $1',
	'wikibase-validator-bad-entity-type' => 'סוג ישות בלתי־צפוי $1',
	'wikibase-validator-no-such-entity' => '$1 לא נמצא',
	'wikibase-validator-no-such-property' => 'המאפיין $1 לא נמצא',
	'wikibase-validator-bad-value' => 'ערך בלתי־תקין: $1',
	'wikibase-validator-bad-value-type' => 'סוג הערך $1 אינו נכון, זה היה אמור להיות $2',
	'wikibase-validator-bad-url' => 'כתובת URL בלתי־תקינה: $1',
	'wikibase-validator-bad-url-scheme' => 'סכמת URL לא נתמכת: $1',
	'wikibase-validator-bad-http-url' => 'כתובת URL של HTTP בלתי־תקינה: $1',
	'wikibase-validator-bad-mailto-url' => 'כתובת URL של mailto בלתי־תקינה: $1',
	'wikibase-validator-unknown-unit' => 'יחידה בלתי־ידועה: $1',
	'datatypes-type-wikibase-item' => 'פריט',
	'datatypes-type-commonsMedia' => 'קובץ מדיה בוויקישיתוף',
	'version-wikibase' => 'Wikibase',
);

/** Croatian (hrvatski)
 * @author MaGa
 */
$messages['hr'] = array(
	'wikibase-replicationnote' => 'Molimo Vas, vodite računa da može proći nekoliko minuta dok izmjene ne budu vidljive na svim wikijima.',
);

/** Upper Sorbian (hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'wikibase-lib-desc' => 'Steji powšitkownu funkcionalnosć za rozšěrjeni Wikibase a Wikibase Client k dispoziciji',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'kajkosć',
	'wikibase-entity-query' => 'naprašowanje',
	'wikibase-diffview-reference' => 'referenca',
	'wikibase-diffview-rank' => 'rjad',
	'wikibase-diffview-qualifier' => 'kwalifikator',
	'wikibase-error-unexpected' => 'Njewočakowany zmylk je wustupił.',
	'wikibase-error-save-generic' => 'Při składowanju je zmylk wustupił, a tohodla njedachu so změny přewjesć.',
	'wikibase-error-remove-generic' => 'Při wotstronjenu je zmylk wustupił, a tohodla njedachu so twoje změny přewjesć.',
	'wikibase-error-save-connection' => 'Zwiskowy zmylk je při składowanju wustupił a twoje změny njedadźa so tohodla přewjesć. Prošu přepruwuj swój internetowy zwisk.',
	'wikibase-error-remove-connection' => 'Zwiskowy zmylk je při wotstronjenju wustupił a tohodla njedadźa so twoje změny přewjesć. Prošu přepruwuj swój internetowy zwisk.',
	'wikibase-error-save-timeout' => 'Mamy techniske ćežkosće a tohodla njeda so ničo składować.',
	'wikibase-error-remove-timeout' => 'Mamy techniske ćežkosće a tohodla njeda so ničo wotstronić.',
	'wikibase-error-autocomplete-connection' => 'API sydła njeda so naprašować. Prošu spytaj pozdźišo hišće raz.',
	'wikibase-error-autocomplete-response' => 'Serwer wotmołwi: $1',
	'wikibase-error-ui-client-error' => 'Zwisk k eksternej webstronje je so njeporadźił. Prošu spytaj pozdźišo hišće raz.',
	'wikibase-error-ui-no-external-page' => 'Podaty nastawk njeda so na wotpowědowacym sydle namakać.',
	'wikibase-error-ui-cant-edit' => 'Njesměš tutu akciju wuwjesć.',
	'wikibase-error-ui-no-permissions' => 'Nimaš dosć prawow, zo by tutu akciju wuwjedł.',
	'wikibase-error-ui-link-exists' => 'Njemóžeš k tutej stronje wotkazować, dokelž druhi element hižo k njej wotkazuje.',
	'wikibase-error-ui-session-failure' => 'Twoje posedźenje je spadnyło. Prošu přizjew so hišće raz.',
	'wikibase-error-ui-edit-conflict' => 'Je wobdźěłowanski konflikt wustupił. Prošu začituj a składuj znowa.',
	'wikibase-replicationnote' => 'Prošu dźiwaj na to, zo móže wjacore mjeńšiny trać, doniž změny na wšěch wikijach widźomne njejsu.',
	'wikibase-sitelinks-sitename-columnheading' => 'Rěč',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Wotkazana strona',
	'wikibase-tooltip-error-details' => 'Podrobnosće',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Medijowa dataja na Wikimedia Commons',
);

/** Hungarian (magyar)
 * @author Tgr
 */
$messages['hu'] = array(
	'wikibase-lib-desc' => 'A Wikibase és a Wikibase kliens kiterjesztések közös funkcióit tartalmazza',
	'wikibase-entity-item' => 'tétel',
	'wikibase-entity-property' => 'tulajdonság',
	'wikibase-entity-query' => 'lekérdezés',
	'wikibase-error-save-generic' => 'Hiba lépett fel a mentés közben, ezért a változtatásaidat nem sikerült átvezetni.',
	'wikibase-error-remove-generic' => 'Hiba lépett fel a törlés közben, ezért a változtatásaidat nem sikerült befejezni.',
	'wikibase-error-save-connection' => 'Kapcsolódási hiba lépett fel a mentés közben, ezért a változtatásaidat nem sikerült befejezni. Ellenőrizd az internetkapcsolatodat.',
	'wikibase-error-remove-connection' => 'Kapcsolódási hiba lépett fel a törlés közben, ezért a változtatásaidat nem sikerült befejezni. Ellenőrizd az internetkapcsolatodat.',
	'wikibase-error-save-timeout' => 'Műszaki problémáink vannak, ezért a mentést nem sikerült befejezni.',
	'wikibase-error-remove-timeout' => 'Műszaki problémáink vannak, ezért a törlést nem sikerült befejezni.',
	'wikibase-error-autocomplete-connection' => 'Nem sikerült lekérdezni a Wikipédia API-t. Kérlek, próbálkozz újra később.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'A szerver válasza: $1',
	'wikibase-error-ui-client-error' => 'Nem sikerült kapcsolódni a kliens laphoz. Kérlek, próbáld meg újra később.',
	'wikibase-error-ui-no-external-page' => 'A megadott cikk nem található a megadott wikin.',
	'wikibase-error-ui-cant-edit' => 'Nem hajthatod végre ezt a műveletet.',
	'wikibase-error-ui-no-permissions' => 'Nem vagy jogosult a művelet végrehajtására.',
	'wikibase-error-ui-link-exists' => 'Nem kapcsolhatod a fogalmat ehhez a laphoz, mert egy másik fogalom már hozzá van kapcsolva.',
	'wikibase-error-ui-session-failure' => 'Lejárt a munkameneted. Kérlek, jelentkezz be újra.',
	'wikibase-error-ui-edit-conflict' => 'Szerkesztési ütközés történt. Kérlek, töltsd újra a lapot, és mentsd el újra.',
	'wikibase-sitelinks-sitename-columnheading' => 'Nyelv',
	'wikibase-sitelinks-siteid-columnheading' => 'Kód',
	'wikibase-sitelinks-link-columnheading' => 'Kapcsolt szócikk', # Fuzzy
	'wikibase-tooltip-error-details' => 'Részletek',
	'datatypes-type-wikibase-item' => 'Tétel',
	'datatypes-type-commonsMedia' => 'Commons médiafájl',
);

/** Armenian (Հայերեն)
 * @author Xelgen
 */
$messages['hy'] = array(
	'datatypes-type-commonsMedia' => 'Տեսաձայնային նիշք Վիքիպահեստում',
);

/** Interlingua (interlingua)
 * @author McDutchie
 */
$messages['ia'] = array(
	'wikibase-lib-desc' => 'Contine functionalitate commun pro le extensiones Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'proprietate',
	'wikibase-entity-query' => 'consulta',
	'wikibase-deletedentity-item' => 'Elemento delite',
	'wikibase-deletedentity-property' => 'Proprietate delite',
	'wikibase-deletedentity-query' => 'Consulta delite',
	'wikibase-diffview-reference' => 'referentia',
	'wikibase-diffview-rank' => 'rango',
	'wikibase-diffview-rank-preferred' => 'Rango preferite',
	'wikibase-diffview-rank-normal' => 'Rango normal',
	'wikibase-diffview-rank-deprecated' => 'Rango depreciate',
	'wikibase-diffview-qualifier' => 'qualificator',
	'wikibase-diffview-label' => 'etiquetta',
	'wikibase-diffview-alias' => 'aliases',
	'wikibase-diffview-description' => 'description',
	'wikibase-diffview-link' => 'ligamines',
	'wikibase-error-unexpected' => 'Un error inexpectate ha occurrite.',
	'wikibase-error-save-generic' => 'Un error occurreva durante le salveguarda. A causa de isto, le cambiamentos non poteva esser completate.',
	'wikibase-error-remove-generic' => 'Un error occurreva durante le remotion. A causa de isto, le cambiamentos non poteva esser completate.',
	'wikibase-error-save-connection' => 'Un error de connexion occurreva durante le salveguarda. A causa de isto, le cambiamentos non poteva esser completate. Per favor verifica tu connexion a internet.',
	'wikibase-error-remove-connection' => 'Un error de connexion occurreva durante le remotion. A causa de isto, le cambiamentos non poteva esser completate. Per favor verifica tu connexion a internet.',
	'wikibase-error-save-timeout' => 'Nos ha incontrate difficultates technic. A causa de isto, tu commando "save" (salveguardar) non poteva esser completate.',
	'wikibase-error-remove-timeout' => 'Nos ha incontrate difficultates technic. A causa de isto, tu commando "remove" (remover) non poteva esser completate.',
	'wikibase-error-autocomplete-connection' => 'Non poteva consultar le API del sito. Per favor reproba plus tarde.',
	'wikibase-error-autocomplete-response' => 'Le servitor respondeva: $1',
	'wikibase-error-ui-client-error' => 'Le connexion al pagina cliente ha fallite. Per favor reproba plus tarde.',
	'wikibase-error-ui-no-external-page' => 'Le articulo specificate non poteva esser trovate in le sito correspondente.',
	'wikibase-error-ui-cant-edit' => 'Tu non es autorisate a exequer iste action.',
	'wikibase-error-ui-no-permissions' => 'Tu non ha derectos sufficiente pro exequer iste action.',
	'wikibase-error-ui-link-exists' => 'Tu non pote ligar a iste pagina perque un altere elemento jam es ligate a illo.',
	'wikibase-error-ui-session-failure' => 'Le session ha expirate. Per favor aperi session de novo.',
	'wikibase-error-ui-edit-conflict' => 'Il ha un conflicto inter modificationes. Per favor, copia e colla vostre modification in un altere documento, recarga iste pagina, reinsere vostre modification, e resalveguarda le pagina.',
	'wikibase-quantitydetails-amount' => 'Quantitate',
	'wikibase-quantitydetails-upperbound' => 'Limite superior',
	'wikibase-quantitydetails-lowerbound' => 'Limite inferior',
	'wikibase-quantitydetails-unit' => 'Unitate',
	'wikibase-replicationnote' => 'Nota que il pote tardar plure minutas ante que le modificationes es visibile in tote le wikis.',
	'wikibase-sitelinks-wikipedia' => 'Paginas de Wikipedia ligate a iste elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingua',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sito',
	'wikibase-sitelinks-siteid-columnheading' => 'Codice',
	'wikibase-sitelinks-link-columnheading' => 'Pagina ligate',
	'wikibase-tooltip-error-details' => 'Detalios',
	'wikibase-undeserializable-value' => 'Le valor es invalide e non pote esser visualisate.',
	'wikibase-validator-bad-type' => '$2 in loco de $1',
	'wikibase-validator-too-long' => 'Non pote esser plus longe que {{PLURAL:$1|$1 character|$1 characteres}}',
	'wikibase-validator-too-short' => 'Debe esser longe al minus {{PLURAL:$1|un character|$1 characteres}}',
	'wikibase-validator-too-high' => 'Foras de limite. Non pote esser superior a $1',
	'wikibase-validator-too-low' => 'Foras de limite. Non pote esser inferior a $1',
	'wikibase-validator-malformed-value' => 'Entrata mal formate: $1',
	'wikibase-validator-bad-entity-id' => 'ID mal formate: $1',
	'wikibase-validator-bad-entity-type' => 'Typo de entitate incorrecte: $1',
	'wikibase-validator-no-such-entity' => '$1 non trovate',
	'wikibase-validator-no-such-property' => 'Proprietate $1 non trovate',
	'wikibase-validator-bad-value' => 'Valor invalide: $1',
	'wikibase-validator-bad-value-type' => 'Typo de valor $1 incorrecte; expectava $2',
	'wikibase-validator-bad-url' => 'URL mal formate: $1',
);

/** Indonesian (Bahasa Indonesia)
 * @author Farras
 * @author Iwan Novirion
 * @author පසිඳු කාවින්ද
 */
$messages['id'] = array(
	'wikibase-lib-desc' => 'Menangani fungsi umum untuk Wikibase dan ekstensi klien Wikibase',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'properti',
	'wikibase-entity-query' => 'permintaan',
	'wikibase-diffview-reference' => 'referensi',
	'wikibase-diffview-rank' => 'peringkat',
	'wikibase-diffview-qualifier' => 'kualifikasi',
	'wikibase-diffview-label' => 'label',
	'wikibase-diffview-alias' => 'nama lain',
	'wikibase-diffview-description' => 'deskripsi',
	'wikibase-diffview-link' => 'pranala',
	'wikibase-error-unexpected' => 'Terjadi kesalahan tak terduga.',
	'wikibase-error-save-generic' => 'Masalah terjadi saat mencoba untuk melakukan Simpan dan karenanya perubahan Anda tidak dapat diselesaikan.',
	'wikibase-error-remove-generic' => 'Masalah terjadi saat mencoba untuk melakukan Hapus dan karenanya perubahan Anda tidak dapat diselesaikan.',
	'wikibase-error-save-connection' => 'Koneksi bermasalah ketika mencoba melakukan Simpan, dan karenanya perubahan tidak dapat diselesaikan. Periksa koneksi Internet Anda.',
	'wikibase-error-remove-connection' => 'Koneksi bermasalah ketika mencoba melakukan Hapus, dan karenanya perubahan tidak dapat diselesaikan. Periksa koneksi Internet Anda.',
	'wikibase-error-save-timeout' => 'Kita sedang mengalami masalah teknis, dan karenanya proses yang sedang Anda "simpan" tidak dapat diselesaikan.',
	'wikibase-error-remove-timeout' => 'Kita sedang mengalami masalah teknis, dan karenanya proses yang sedang Anda "hapus" tidak dapat diselesaikan.',
	'wikibase-error-autocomplete-connection' => 'Tidak bisa melakukan permintaan API Wikipedia. Harap coba lagi kemudian.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Respon server: $1',
	'wikibase-error-ui-client-error' => 'Koneksi ke halaman klien gagal. Harap coba lagi kemudian.',
	'wikibase-error-ui-no-external-page' => 'Artikel yang dicari tidak ditemukan pada wiki bersangkutan.',
	'wikibase-error-ui-cant-edit' => 'Anda tidak dibolehkan melakukan tindakan ini.',
	'wikibase-error-ui-no-permissions' => 'Anda tidak memiliki hak untuk melakukan tindakan ini.',
	'wikibase-error-ui-link-exists' => 'Anda tidak dapat menautkan ke halaman ini karena item lain sudah tertaut padanya.',
	'wikibase-error-ui-session-failure' => 'Sesi Anda telah berakhir. Silakan masuk log lagi.',
	'wikibase-error-ui-edit-conflict' => 'Ada konflik penyuntingan. Silakan muat ulang dan simpan kembali.',
	'wikibase-replicationnote' => 'Harap diperhatikan bahwa memerlukan beberapa menit sampai perubahan terlihat pada semua wiki',
	'wikibase-sitelinks-sitename-columnheading' => 'Bahasa',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Artikel tertaut', # Fuzzy
	'wikibase-tooltip-error-details' => 'Rincian',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Berkas media Commons',
	'version-wikibase' => 'Wikibase',
);

/** Iloko (Ilokano)
 * @author Lam-ang
 */
$messages['ilo'] = array(
	'wikibase-lib-desc' => 'Agtengngel kadagiti sapasap a pamay-an para kadagiti Wikibase ken Wikibase a kliente a pagpaatiddog',
	'wikibase-entity-item' => 'banag',
	'wikibase-entity-property' => 'tagikua',
	'wikibase-entity-query' => 'panagbiruk',
	'wikibase-deletedentity-item' => 'Inikkat ti banag',
	'wikibase-deletedentity-property' => 'Inikkat ti tagikua',
	'wikibase-deletedentity-query' => 'Inikkat ti biniruk',
	'wikibase-diffview-reference' => 'nagibasaran',
	'wikibase-diffview-rank' => 'ranggo',
	'wikibase-diffview-rank-preferred' => 'Kinaykayat a ranggo',
	'wikibase-diffview-rank-normal' => 'Kadawyan a ranggo',
	'wikibase-diffview-rank-deprecated' => 'Naikkaten a ranggo',
	'wikibase-diffview-qualifier' => 'kababalin',
	'wikibase-diffview-label' => 'etiketa',
	'wikibase-diffview-alias' => 'sabali a nagnagan',
	'wikibase-diffview-description' => 'deskripsion',
	'wikibase-diffview-link' => 'dagiti silpo',
	'wikibase-error-unexpected' => 'Adda rimsua a maysa a saan a nanamnama a biddut.',
	'wikibase-error-save-generic' => 'Ada biddut a napasamak bayat nga agar-aramidka ti panagidulin iti daytoy, saan a malpas dagiti panagibalbaliwmo.',
	'wikibase-error-remove-generic' => 'Adda biddut a napasamak bayat nga agar-aramidka ti panagikkat ti daytoy, saan a malpas dagiti panagibalbaliwmo.',
	'wikibase-error-save-connection' => 'Adda biddut napasamak ti panakaikapet bayat nga agar-aramid ti panagidulin, ken gapu ti daytoy dagiti panagibalwbaliwmo ket saan a malpas. Pangngaasi a kitaem ti panakaikapetmo ti internet.',
	'wikibase-error-remove-connection' => 'Adda biddut napasamak ti panakaikapet bayat nga agar-aramid ti panagikkat, ken gapu ti daytoy dagiti panagibalwbaliwmo ket saan a malpas. Pangngaasi a kitaem ti panakaikapetmo ti internet.',
	'wikibase-error-save-timeout' => 'Makasansanay kami kadagiti teknikal a parikut, ken gapu ti daytoy ti "indulinmo" ket saan a malpas.',
	'wikibase-error-remove-timeout' => 'Makasansanay kami kadagiti teknikal a parikut, ken gapu ti daytoy ti "panagikkatmo" ket saan a malpas.',
	'wikibase-error-autocomplete-connection' => 'Saan a nakausisa ti sitio ti API. Pangngaasi a padasem manen no madamdama.',
	'wikibase-error-autocomplete-response' => 'Simmungbat ti server: $1',
	'wikibase-error-ui-client-error' => 'Ti panakaikapet ti kliente a panid ket napaay. Pangngaasi a padasem manen no madamdama.',
	'wikibase-error-ui-no-external-page' => 'Ti naitudo nga artikulo ket saan a mabirukan idiay maipada a sitio.',
	'wikibase-error-ui-cant-edit' => 'Saanmo a mabalin ti agaramid ti daytoy a tignay.',
	'wikibase-error-ui-no-permissions' => 'Awan ti umanay a karbengam nga agaramid ti daytoy a tignay.',
	'wikibase-error-ui-link-exists' => 'Saanka a makasilpo ti daytoy a panid gaputa adda ti maysa a banagen a nakasilpo ti daytoy.',
	'wikibase-error-ui-session-failure' => 'Ti gimongam ket nagpason. Pangngaasi a sumrekka manen.',
	'wikibase-error-ui-edit-conflict' => 'Adda kasinnupiat a panagurnos. Pangngaasi nga ikarga ken idulin manen.',
	'wikibase-quantitydetails-amount' => 'Pakadagupan',
	'wikibase-quantitydetails-upperbound' => 'Akin-ngato a patingga',
	'wikibase-quantitydetails-lowerbound' => 'Akin-baba a patingga',
	'wikibase-quantitydetails-unit' => 'Unit',
	'wikibase-timedetails-time' => 'Oras',
	'wikibase-timedetails-isotime' => 'petsa ti ISO',
	'wikibase-timedetails-timezone' => 'Sona ti oras',
	'wikibase-timedetails-calendar' => 'Kalendario',
	'wikibase-timedetails-precision' => 'Presision',
	'wikibase-timedetails-before' => 'Sakbay',
	'wikibase-timedetails-after' => 'Kalpasan',
	'wikibase-globedetails-longitude' => 'Longitud',
	'wikibase-globedetails-latitude' => 'Latitud',
	'wikibase-globedetails-precision' => 'Presision',
	'wikibase-globedetails-globe' => 'Globo',
	'wikibase-replicationnote' => 'Pangngaasi nga ammuem a mabalin nga agpaut ti adu a minutos aginggana dagiti panagbalbaliw ket makita kadagiti amin a wiki',
	'wikibase-sitelinks-wikipedia' => 'Pampanid ti Wikipedia a naisilpo iti daytoy a banag',
	'wikibase-sitelinks-sitename-columnheading' => 'Pagsasao',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sitio',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodigo',
	'wikibase-sitelinks-link-columnheading' => 'Naisilpo a panid',
	'wikibase-tooltip-error-details' => 'Dagiti salaysay',
	'wikibase-undeserializable-value' => 'Saan a husto ti pateg ken saan a mabalin a maiparang.',
	'wikibase-validator-bad-type' => '$2 embes nga iti $1',
	'wikibase-validator-too-long' => 'Nasken a saan nga ad-adu ngem {{PLURAL:$1|maysa a karakter|$1 a karkarakter}} ti kaatiddog',
	'wikibase-validator-too-short' => 'Nasken a saan a basbassit ngem {{PLURAL:$1|maysa a karakter|$1 a karkarakter}} ti kaatiddog',
	'wikibase-validator-too-high' => 'Saan a masakop, nasken a saan a nangatngato ngem $1',
	'wikibase-validator-too-low' => 'Saan a masakop, nasken a saan a nababbaba ngem $1',
	'wikibase-validator-malformed-value' => 'Nadadael nga ikabil: $1',
	'wikibase-validator-bad-entity-id' => 'Nadadael nga ID:$1',
	'wikibase-validator-bad-entity-type' => 'Di nanamnama a kita ti entidad ti $1',
	'wikibase-validator-no-such-entity' => 'Ti $1 ket saan a nabirukan',
	'wikibase-validator-no-such-property' => 'Saan a nabirukan ti $1 a tagikua',
	'wikibase-validator-bad-value' => 'Saan a mabalin a pateg: $1',
	'wikibase-validator-bad-value-type' => 'Madi a kita ti pateg ti $1, nanamnama ti $2',
	'wikibase-validator-bad-url' => 'Nadadael nga URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Saan a nasuportaran a panggep ti URL: $1',
	'wikibase-validator-bad-http-url' => 'Nadadael a HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Nadadael a mailto URL: $1',
	'wikibase-validator-unknown-unit' => 'Di ammo nga unit: $1',
	'datatypes-type-wikibase-item' => 'Banag',
	'datatypes-type-commonsMedia' => 'Midia a papeles ti Commons',
	'version-wikibase' => 'Wikibase',
);

/** Icelandic (íslenska)
 * @author Snævar
 */
$messages['is'] = array(
	'wikibase-lib-desc' => 'Inniheldur almenna virkni fyrir Wikibase og Wikibase Client.',
	'wikibase-entity-item' => 'hlut',
	'wikibase-entity-property' => 'eiginleika',
	'wikibase-entity-query' => 'fyrirspurn',
	'wikibase-deletedentity-item' => 'Eyddur hlutur',
	'wikibase-deletedentity-property' => 'Eyddur eiginleiki',
	'wikibase-diffview-rank' => 'Sætaröðun',
	'wikibase-diffview-rank-preferred' => 'Æskilegt sæti',
	'wikibase-diffview-rank-normal' => 'Hefðbundið sæti',
	'wikibase-diffview-rank-deprecated' => 'Óæskilegt sæti',
	'wikibase-diffview-label' => 'merkimiði',
	'wikibase-diffview-alias' => 'samheiti',
	'wikibase-diffview-description' => 'lýsing',
	'wikibase-diffview-link' => 'tenglar',
	'wikibase-error-unexpected' => 'Óvænt villa átti sér stað.',
	'wikibase-error-save-generic' => 'Villa átti sér stað þegar þú reyndir að framkvæma vistun og því mistókst að vista breytingarnar þínar.',
	'wikibase-error-remove-generic' => 'Villa átti sér stað þegar þú reyndir að fjarlægja hlut og því mistókst að ljúka breytingum þínum.',
	'wikibase-error-save-connection' => 'Tengingar villa átti sér stað þegar reynt var að framkvæma vistun og því mistókst að ljúka breytingunum þínum. Athugaðu hvort þú sért tengd/ur netinu.',
	'wikibase-error-remove-connection' => 'Tengingar villa átti sér stað þegar þú reyndir að framkvæma fjarlægingu og því mistókst að ljúka breytingum þínum. Vinsamlegast athugaðu hvort þú sért tengd/ur netinu.',
	'wikibase-error-save-timeout' => 'Við höfum orðið fyrir tæknilegum örðugleikum og því mistókst að ljúka vistun.',
	'wikibase-error-remove-timeout' => 'Við höfum orðið fyrir tæknilegum örðugleikum og því mistókst að ljúka fjarlægingu.',
	'wikibase-error-autocomplete-connection' => 'Mistókst að senda fyrirspurn til síðunnar. Vinsamlegast reyndu aftur síðar.',
	'wikibase-error-autocomplete-response' => 'Vefþjónninn svaraði: $1',
	'wikibase-error-ui-client-error' => 'Tenging við biðlarann mistókst. Vinsamlegast reyndu aftur síðar.',
	'wikibase-error-ui-no-external-page' => 'Greinin sem tilgreind var fannst ekki á vefsíðunni.',
	'wikibase-error-ui-cant-edit' => 'Þú getur ekki gert þessa aðgerð.',
	'wikibase-error-ui-no-permissions' => 'Þú hefur ekki tilætluð réttindi til þess að framkvæma þessa aðgerð.',
	'wikibase-error-ui-link-exists' => 'Þú getur ekki tengt í þessa síðu því annar hlutur tengir nú þegar í hana.',
	'wikibase-error-ui-session-failure' => 'Setan þín rann út. Vinsamlegast skráðu þig inn aftur.',
	'wikibase-error-ui-edit-conflict' => 'Breytingarárekstur. Vinsamlegast endurhladdu síðunni og vistaðu aftur.',
	'wikibase-quantitydetails-unit' => 'Eining',
	'wikibase-replicationnote' => 'Athugaðu að það tekur nokkrar mínútur þangað til breytingarnar eru sýnilegar á öllum wiki verkefnum.',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia síður sem tengjast þessum hlut',
	'wikibase-sitelinks-sitename-columnheading' => 'Tungumál',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Vefsíða',
	'wikibase-sitelinks-siteid-columnheading' => 'Kóði',
	'wikibase-sitelinks-link-columnheading' => 'Tengd síða',
	'wikibase-tooltip-error-details' => 'Nánar',
	'wikibase-undeserializable-value' => 'Gildið er ógilt og ekki er hægt að birta það.',
	'wikibase-validator-bad-type' => '$2 í staðinn fyrir $1',
	'wikibase-validator-too-long' => 'Má ekki vera lengri en $1 {{PLURAL:$1|stafur|stafir}}.',
	'wikibase-validator-too-short' => 'Verður að vera að minnsta kosti $1 {{PLURAL:$1|stafur|stafir}} að lengd',
	'wikibase-validator-malformed-value' => 'Gallað inntak: $1',
	'wikibase-validator-bad-entity-id' => 'Gallað auðkenni: $1',
	'wikibase-validator-no-such-entity' => '$1 fannst ekki',
	'wikibase-validator-bad-value' => 'Ógilt gildi: $1',
	'wikibase-validator-bad-url' => 'Gölluð vefslóð: $1',
	'datatypes-type-wikibase-item' => 'Hlutur',
	'datatypes-type-commonsMedia' => 'Commons margmiðlunarskrá',
);

/** Italian (italiano)
 * @author Beta16
 * @author Raoli
 */
$messages['it'] = array(
	'wikibase-lib-desc' => 'Contiene le funzionalità comuni per le estensioni Wikibase e Wikibase Client.',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'proprietà',
	'wikibase-entity-query' => 'interrogazione',
	'wikibase-deletedentity-item' => 'Elemento cancellato',
	'wikibase-deletedentity-property' => 'Proprietà cancellata',
	'wikibase-deletedentity-query' => 'Interrogazione cancellata',
	'wikibase-diffview-reference' => 'riferimento',
	'wikibase-diffview-rank' => 'classificazione',
	'wikibase-diffview-rank-preferred' => 'Classificato preferito',
	'wikibase-diffview-rank-normal' => 'Classificato normale',
	'wikibase-diffview-rank-deprecated' => 'Classificato sconsigliato',
	'wikibase-diffview-qualifier' => 'qualificatore',
	'wikibase-diffview-label' => 'etichetta',
	'wikibase-diffview-alias' => 'alias',
	'wikibase-diffview-description' => 'descrizione',
	'wikibase-diffview-link' => 'collegamenti',
	'wikibase-error-unexpected' => 'Si è verificato un errore imprevisto.',
	'wikibase-error-save-generic' => 'Si è verificato un errore durante il tentativo di salvataggio, perciò le tue modifiche potrebbero non essere state completamente memorizzate.',
	'wikibase-error-remove-generic' => 'Si è verificato un errore durante il tentativo di rimozione, perciò le tue modifiche potrebbero non essere state completamente memorizzate.',
	'wikibase-error-save-connection' => 'Si è verificato un errore di connessione durante il tentativo di salvataggio, perciò le tue modifiche potrebbero non essere state completamente memorizzate. Per favore, controlla la tua connessione ad internet.',
	'wikibase-error-remove-connection' => 'Si è verificato un errore di connessione durante il tentativo di rimozione, perciò le tue modifiche potrebbero non essere state completamente memorizzate. Per favore, controlla la tua connessione ad internet.',
	'wikibase-error-save-timeout' => 'Stiamo riscontrando difficoltà tecniche, perciò il tuo salvataggio potrebbe non essere stato completato.',
	'wikibase-error-remove-timeout' => 'Stiamo riscontrando difficoltà tecniche, perciò la tua rimozione potrebbe non essere stata completata.',
	'wikibase-error-autocomplete-connection' => 'Non è possibile interrogare le API del sito. Riprova più tardi.',
	'wikibase-error-autocomplete-response' => 'Risposta del server: $1',
	'wikibase-error-ui-client-error' => 'La connessione alla pagina client non è riuscita. Riprova più tardi.',
	'wikibase-error-ui-no-external-page' => 'La voce specificata non è stata trovata sul sito corrispondente.',
	'wikibase-error-ui-cant-edit' => 'Non sei autorizzato ad eseguire questa azione.',
	'wikibase-error-ui-no-permissions' => 'Non hai i diritti sufficienti per eseguire questa azione.',
	'wikibase-error-ui-link-exists' => 'Non puoi inserire un collegamento a questa pagina perché un altro elemento già collega ad essa.',
	'wikibase-error-ui-session-failure' => 'La sessione è scaduta. Accedi nuovamente.',
	'wikibase-error-ui-edit-conflict' => 'Si è verificato un conflitto di edizione. Si prega di ricaricare e salvare di nuovo.',
	'wikibase-quantitydetails-amount' => 'Quantità',
	'wikibase-quantitydetails-upperbound' => 'Limite superiore',
	'wikibase-quantitydetails-lowerbound' => 'Limite inferiore',
	'wikibase-quantitydetails-unit' => 'Unità',
	'wikibase-timedetails-timezone' => 'Fuso orario',
	'wikibase-timedetails-calendar' => 'Calendario',
	'wikibase-timedetails-precision' => 'Precisione',
	'wikibase-globedetails-longitude' => 'Longitudine',
	'wikibase-globedetails-latitude' => 'Latitudine',
	'wikibase-globedetails-precision' => 'Precisione',
	'wikibase-replicationnote' => 'Potrebbero essere necessari diversi minuti prima che le modifiche siano visibili su tutti i wiki',
	'wikibase-sitelinks-wikipedia' => 'Pagine di Wikipedia collegate a questo elemento',
	'wikibase-sitelinks-sitename-columnheading' => 'Lingua',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sito',
	'wikibase-sitelinks-siteid-columnheading' => 'Codice',
	'wikibase-sitelinks-link-columnheading' => 'Pagina collegata',
	'wikibase-tooltip-error-details' => 'Dettagli',
	'wikibase-undeserializable-value' => 'Il valore non è valido e non può essere visualizzato.',
	'wikibase-validator-bad-type' => '$2 anziché $1',
	'wikibase-validator-too-long' => 'Non deve essere più lunga di {{PLURAL:$1|un carattere|$1 caratteri}}',
	'wikibase-validator-too-short' => 'Deve essere lunga almeno {{PLURAL:$1|un carattere|$1 caratteri}}',
	'wikibase-validator-too-high' => 'Fuori intervallo, non deve essere superiore a $1',
	'wikibase-validator-too-low' => 'Fuori intervallo, non deve essere inferiore a $1',
	'wikibase-validator-malformed-value' => 'Input non valido: $1',
	'wikibase-validator-bad-entity-id' => 'ID non valido: $1',
	'wikibase-validator-bad-entity-type' => 'Tipo di entità non previsto $1',
	'wikibase-validator-no-such-entity' => '$1 non trovato',
	'wikibase-validator-no-such-property' => 'Proprietà $1 non trovata',
	'wikibase-validator-bad-value' => 'Valore non valido: $1',
	'wikibase-validator-bad-value-type' => 'Tipo di valore errato $1, atteso $2',
	'wikibase-validator-bad-url' => 'URL non valido: $1',
	'wikibase-validator-bad-url-scheme' => 'Schema URL non supportato: $1',
	'wikibase-validator-bad-http-url' => 'URL HTTP non valido: $1',
	'wikibase-validator-bad-mailto-url' => 'URL mailto non valido: $1',
	'wikibase-validator-unknown-unit' => 'Unità sconosciuta: $1',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'File multimediale su Commons',
	'version-wikibase' => 'Wikibase',
);

/** Japanese (日本語)
 * @author Fryed-peach
 * @author Shirayuki
 */
$messages['ja'] = array(
	'wikibase-lib-desc' => 'ウィキベースとウィキベースクライアント拡張機能で共通の機能を保持する',
	'wikibase-entity-item' => '項目',
	'wikibase-entity-property' => 'プロパティ',
	'wikibase-entity-query' => 'クエリ',
	'wikibase-deletedentity-item' => '削除された項目',
	'wikibase-deletedentity-property' => '削除されたプロパティ',
	'wikibase-deletedentity-query' => '削除されたクエリ',
	'wikibase-diffview-reference' => '情報源',
	'wikibase-diffview-rank' => 'ランク',
	'wikibase-diffview-qualifier' => '修飾子',
	'wikibase-diffview-label' => 'ラベル',
	'wikibase-diffview-alias' => '別名',
	'wikibase-diffview-description' => '説明',
	'wikibase-diffview-link' => 'リンク',
	'wikibase-error-unexpected' => '予期しないエラーが発生しました。',
	'wikibase-error-save-generic' => '保存を実行する際にエラーが発生したため、変更を反映させることができませんでした。',
	'wikibase-error-remove-generic' => '除去を実行する際にエラーが発生したため、変更を反映させることができませんでした。',
	'wikibase-error-save-connection' => '保存を実行する際に接続エラーが発生したため、変更を反映させることができませんでした。自身のインターネット接続を確認してください。',
	'wikibase-error-remove-connection' => '除去を実行する際に接続エラーが発生したため、変更を反映させることができませんでした。自身のインターネット接続を確認してください。',
	'wikibase-error-save-timeout' => '技術的な障害が発生しているため、「保存」を完了できませんでした。',
	'wikibase-error-remove-timeout' => '技術的な障害が発生しているため、「除去」を完了できませんでした。',
	'wikibase-error-autocomplete-connection' => 'サイトの API のクエリを実行できませんでした。しばらくしてからもう一度お試しください。',
	'wikibase-error-autocomplete-response' => 'サーバーの応答: $1',
	'wikibase-error-ui-client-error' => 'クライアントページへの接続に失敗しました。後で再度実行してください。',
	'wikibase-error-ui-no-external-page' => '指定した記事は、対応するサイト内で見つかりませんでした。',
	'wikibase-error-ui-cant-edit' => 'この操作を行うことは許可されていません。',
	'wikibase-error-ui-no-permissions' => 'あなたにはこの操作を実行する権限がありません。',
	'wikibase-error-ui-link-exists' => '別の項目から既にリンクしているため、このページにはリンクできません。',
	'wikibase-error-ui-session-failure' => 'セッションの期限が切れました。再度ログインしてください。',
	'wikibase-error-ui-edit-conflict' => '編集が競合しました。再読込して再度保存してください。',
	'wikibase-quantitydetails-amount' => '量',
	'wikibase-quantitydetails-upperbound' => '上限値',
	'wikibase-quantitydetails-lowerbound' => '下限値',
	'wikibase-quantitydetails-unit' => '単位',
	'wikibase-timedetails-timezone' => 'タイムゾーン',
	'wikibase-globedetails-longitude' => '経度',
	'wikibase-globedetails-latitude' => '緯度',
	'wikibase-replicationnote' => '変更内容をすべてのウィキに反映させるのに時間がかかる場合があることにご注意ください。',
	'wikibase-sitelinks-wikipedia' => 'この項目にリンクしているウィキペディアのページ',
	'wikibase-sitelinks-sitename-columnheading' => '言語',
	'wikibase-sitelinks-sitename-columnheading-special' => 'サイト',
	'wikibase-sitelinks-siteid-columnheading' => 'コード',
	'wikibase-sitelinks-link-columnheading' => 'リンクされているページ',
	'wikibase-tooltip-error-details' => '詳細',
	'wikibase-undeserializable-value' => '値が無効であるため表示できません。',
	'wikibase-validator-bad-type' => '$1 ではなく $2',
	'wikibase-validator-too-long' => '{{PLURAL:$1|$1 文字}}以下の長さにしてください',
	'wikibase-validator-too-short' => '{{PLURAL:$1|$1 文字}}以上の長さにしてください',
	'wikibase-validator-too-high' => '範囲外です。$1 以下の値にしてください。',
	'wikibase-validator-too-low' => '範囲外です。$1 以上の値にしてください。',
	'wikibase-validator-malformed-value' => '誤った形式の入力: $1',
	'wikibase-validator-bad-entity-id' => '誤った形式の ID: $1',
	'wikibase-validator-bad-entity-type' => '予期しない実体型 $1',
	'wikibase-validator-no-such-entity' => '$1 が見つかりません',
	'wikibase-validator-no-such-property' => 'プロパティ $1 が見つかりません',
	'wikibase-validator-bad-value' => '値の誤り: $1',
	'wikibase-validator-bad-value-type' => '値の型 $1 は間違いです。$2 が正しい型です。',
	'wikibase-validator-bad-url' => '誤った形式の URL: $1',
	'wikibase-validator-bad-url-scheme' => '未対応の URL スキーム: $1',
	'wikibase-validator-bad-http-url' => '誤った形式の HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => '誤った形式の mailto URL: $1',
	'wikibase-validator-unknown-unit' => '不明な単位: $1',
	'datatypes-type-wikibase-item' => '項目',
	'datatypes-type-commonsMedia' => 'コモンズのメディアファイル',
	'version-wikibase' => 'ウィキベース',
);

/** Georgian (ქართული)
 * @author David1010
 * @author Tokoko
 */
$messages['ka'] = array(
	'wikibase-lib-desc' => 'ვიკიბაზისა და ვიკიბაზის კლიენტის გაფართოებების საერთო ფუნქციები',
	'wikibase-entity-item' => 'ელემენტი',
	'wikibase-entity-property' => 'თვისება',
	'wikibase-entity-query' => 'მოთხოვნა',
	'wikibase-deletedentity-item' => 'წაშლილი ელემენტი',
	'wikibase-diffview-reference' => 'მინიშნება',
	'wikibase-diffview-rank' => 'ადგილი',
	'wikibase-diffview-qualifier' => 'შესარჩევი',
	'wikibase-diffview-alias' => 'ფსევდონიმები',
	'wikibase-diffview-description' => 'აღწერა',
	'wikibase-diffview-link' => 'ბმულები',
	'wikibase-error-unexpected' => 'მოხდა გაუთვალისწინებელი შეცდომა.',
	'wikibase-error-save-generic' => 'შენახვის მცდელობისას მოხდა შეცდომა, ამიტომ თქვენი ცვლილებები ვერ შესრულდება.',
	'wikibase-error-remove-generic' => 'წაშლის მცდელობისას მოხდა შეცდომა, ამიტომ თქვენი ცვლილებები ვერ შესრულდება.',
	'wikibase-error-save-connection' => 'შენახვის მცდელობისას მოხდა დაკავშირების შეცდომა, ამიტომ თქვენი ცვლილებები ვერ შესრულდება. გთხოვთ, შეამოწმოთ თქვენი კავშირი ინტერნეტთან.',
	'wikibase-error-remove-connection' => 'წაშლის მცდელობისას მოხდა დაკავშირების შეცდომა, ამიტომ თქვენი ცვლილებები ვერ შესრულდება. გთხოვთ, შეამოწმოთ თქვენი კავშირი ინტერნეტთან.',
	'wikibase-error-save-timeout' => 'ჩვენ განვიცდით ტექნიკურ სირთულეებს, ამიტომ თქვენი ცვლილებები ვერ შესრულდება.',
	'wikibase-error-remove-timeout' => 'ჩვენ განვიცდით ტექნიკურ სირთულეებს, ამიტომ თქვენი წაშლა ვერ შესრულდება.',
	'wikibase-error-autocomplete-connection' => 'საიტის API-ს მოთხოვნა ვერ მოხერხდა. გთხოვთ, მოგვიანებით კიდევ სცადოთ.',
	'wikibase-error-autocomplete-response' => 'სერვერის პასუხი: $1',
	'wikibase-error-ui-client-error' => 'კლიენტის გვერდთან დაკავშირების შეცდომა. გთხოვთ, სცადოთ მოგვიანებით.',
	'wikibase-error-ui-no-external-page' => 'შესაბამის საიტზე მითითებული სტატიის მოძებნა ვერ მოხერხდა.',
	'wikibase-error-ui-cant-edit' => 'თქვენ არ შეგიძლიათ ამ მოქმედების შესრულება.',
	'wikibase-error-ui-no-permissions' => 'თქვენ არ გაქვთ საკმარი უფლებები ამ მოქმედების შესასრულებლად.',
	'wikibase-error-ui-session-failure' => 'თქვენი სესიის დრო ამოიწურა. გთხოვთ, თავიდან შეხვიდეთ სისტემაში.',
	'wikibase-error-ui-edit-conflict' => 'რედაქტირების კონფლიქტი. გადატვირთეთ და თავიდან შეინახეთ.',
	'wikibase-replicationnote' => 'გთხოვთ, მიაქციოთ ყურადღება, რომ შეიძლება გავიდეს რამდენიმე წუთი, სანამ ცვლილებები ხილული გახდება ყველა ვიკი-პროექტში',
	'wikibase-sitelinks-sitename-columnheading' => 'ენა',
	'wikibase-sitelinks-sitename-columnheading-special' => 'საიტი',
	'wikibase-sitelinks-siteid-columnheading' => 'კოდი',
	'wikibase-sitelinks-link-columnheading' => 'დაკავშირებული გვერდი',
	'wikibase-tooltip-error-details' => 'დეტალები',
	'wikibase-validator-no-such-entity' => '$1 არ მოიძებნა',
	'datatypes-type-wikibase-item' => 'ელემენტი',
	'datatypes-type-commonsMedia' => 'მედიაფაილი ვიკისაწყობში',
);

/** Kazakh (Cyrillic script) (қазақша (кирил)‎)
 * @author Arystanbek
 */
$messages['kk-cyrl'] = array(
	'wikibase-entity-item' => 'элемент',
	'wikibase-entity-property' => 'Сипат',
	'wikibase-entity-query' => 'сұрау',
	'wikibase-deletedentity-item' => 'Жойылған элемент',
	'wikibase-deletedentity-property' => 'Жойылған сипат',
	'wikibase-deletedentity-query' => 'Жойылған сұрау',
	'wikibase-diffview-reference' => 'дереккөз',
	'wikibase-diffview-rank' => 'Рет',
	'wikibase-diffview-qualifier' => 'көрсеткіш',
	'wikibase-diffview-label' => 'деңгей',
	'wikibase-diffview-alias' => 'лақаптар',
	'wikibase-diffview-description' => 'сипаттамасы',
	'wikibase-diffview-link' => 'сілтемелер',
	'wikibase-error-ui-edit-conflict' => 'Осында өңдеу қақтығысы. Қайта жүктеңіз және қайтадан сақтаңыз.',
	'wikibase-sitelinks-wikipedia' => 'Бұл элементке Уикипедия беттері сілтенген',
	'wikibase-sitelinks-sitename-columnheading' => 'Тіл',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Сілтенген мақала', # Fuzzy
	'wikibase-tooltip-error-details' => 'Бөлшектер',
	'wikibase-validator-bad-entity-id' => 'Көріксіз ID: $1',
	'wikibase-validator-no-such-entity' => '$1 табылмады',
	'datatypes-type-wikibase-item' => 'элемент',
	'datatypes-type-commonsMedia' => 'Ортаққор медиа файлы',
	'version-wikibase' => 'Уикиқор',
);

/** Korean (한국어)
 * @author Hym411
 * @author Kwj2772
 * @author Priviet
 * @author 관인생략
 * @author 아라
 */
$messages['ko'] = array(
	'wikibase-lib-desc' => '위키베이스와 위키베이스 클라이언트 확장 기능을 위한 공통 기능을 얻습니다',
	'wikibase-entity-item' => '항목',
	'wikibase-entity-property' => '속성',
	'wikibase-entity-query' => '쿼리',
	'wikibase-deletedentity-item' => '삭제된 항목',
	'wikibase-deletedentity-property' => '삭제된 속성',
	'wikibase-deletedentity-query' => '삭제된 쿼리',
	'wikibase-diffview-reference' => '참고',
	'wikibase-diffview-rank' => '등급',
	'wikibase-diffview-rank-preferred' => '선호 등급',
	'wikibase-diffview-rank-normal' => '일반 등급',
	'wikibase-diffview-rank-deprecated' => '비사용 등급',
	'wikibase-diffview-qualifier' => '한정어',
	'wikibase-diffview-label' => '레이블',
	'wikibase-diffview-alias' => '별칭',
	'wikibase-diffview-description' => '설명',
	'wikibase-diffview-link' => '링크',
	'wikibase-error-unexpected' => '예기치 않은 오류가 발생했습니다.',
	'wikibase-error-save-generic' => '저장을 수행하는 동안 오류가 발생했기 때문에 바뀜을 완료할 수 없습니다.',
	'wikibase-error-remove-generic' => '삭제를 수행하는 동안 오류가 발생했기 때문에 바뀜을 완료할 수 없습니다.',
	'wikibase-error-save-connection' => '저장을 수행하는 동안 연결 오류가 발생했기 때문에 바뀜을 완료할 수 없습니다. 인터넷 연결을 확인하세요.',
	'wikibase-error-remove-connection' => '삭제를 수행하는 동안 연결 오류가 발생했기 때문에 바뀜을 완료할 수 없습니다. 인터넷 연결을 확인하세요.',
	'wikibase-error-save-timeout' => '기술적인 문제가 있기 때문에 이 "저장"이 완료되지 않았습니다.',
	'wikibase-error-remove-timeout' => '기술적인 문제가 있기 때문에 이 "제거"가 완료되지 않았습니다.',
	'wikibase-error-autocomplete-connection' => '사이트 API를 쿼리할 수 없습니다. 나중에 다시 시도하세요.',
	'wikibase-error-autocomplete-response' => '서버 응답: $1',
	'wikibase-error-ui-client-error' => '클라이언트 문서에 연결에 실패했습니다. 나중에 다시 시도하세요.',
	'wikibase-error-ui-no-external-page' => '지정한 문서는 해당 사이트에서 찾을 수 없습니다.',
	'wikibase-error-ui-cant-edit' => '이 작업을 수행하는 것이 허용되지 않습니다.',
	'wikibase-error-ui-no-permissions' => '이 작업을 수행할 수 있는 충분한 권한이 없습니다.',
	'wikibase-error-ui-link-exists' => '다른 항목을 이미 링크했기 때문에 이 문서에 링크할 수 없습니다.',
	'wikibase-error-ui-session-failure' => '세션이 만료되었습니다. 다시 로그인하세요.',
	'wikibase-error-ui-edit-conflict' => '편집 충돌이 발생했습니다. 다시 불러오고 나서 다시 저장하세요.',
	'wikibase-quantitydetails-amount' => '금액',
	'wikibase-quantitydetails-upperbound' => '상한',
	'wikibase-quantitydetails-lowerbound' => '하한',
	'wikibase-quantitydetails-unit' => '단위',
	'wikibase-timedetails-time' => '시간',
	'wikibase-timedetails-isotime' => 'ISO 타임스탬프',
	'wikibase-timedetails-timezone' => '시간대',
	'wikibase-timedetails-calendar' => '달력',
	'wikibase-timedetails-precision' => '정밀도',
	'wikibase-timedetails-before' => '이전',
	'wikibase-timedetails-after' => '이후',
	'wikibase-globedetails-longitude' => '경도',
	'wikibase-globedetails-latitude' => '위도',
	'wikibase-globedetails-precision' => '정밀도',
	'wikibase-globedetails-globe' => '글로브',
	'wikibase-replicationnote' => '바뀐 내용이 모든 위키에 보이는데 시간이 걸릴 수 있음을 주의하세요.',
	'wikibase-sitelinks-wikipedia' => '이 항목을 가리키는 위키백과 문서',
	'wikibase-sitelinks-sitename-columnheading' => '언어',
	'wikibase-sitelinks-sitename-columnheading-special' => '사이트',
	'wikibase-sitelinks-siteid-columnheading' => '코드',
	'wikibase-sitelinks-link-columnheading' => '링크된 문서',
	'wikibase-tooltip-error-details' => '자세한 사항',
	'wikibase-undeserializable-value' => '유효하지 않은 값이므로 표시할 수 없습니다.',
	'wikibase-validator-bad-type' => '$1 대신 $2',
	'wikibase-validator-too-long' => '{{PLURAL:$1|한 문자|$1 문자}}보다 더 길지 않아야 합니다',
	'wikibase-validator-too-short' => '{{PLURAL:$1|한 문자|$1 문자}} 이상이어야 합니다',
	'wikibase-validator-too-high' => '범위는 $1보다 클 수 없습니다.',
	'wikibase-validator-too-low' => '범위는 $1보다 작을 수 없습니다.',
	'wikibase-validator-malformed-value' => '잘못된 형식의 입력: $1',
	'wikibase-validator-bad-entity-id' => '잘못된 형식의 ID: $1',
	'wikibase-validator-bad-entity-type' => '예기치 않은 $1 개체 유형',
	'wikibase-validator-no-such-entity' => '$1(을)를 찾을 수 없습니다',
	'wikibase-validator-no-such-property' => '$1 속성을 찾을 수 없습니다',
	'wikibase-validator-bad-value' => '잘못된 값: $1',
	'wikibase-validator-bad-value-type' => '$1 값 유형이 잘못됨, $2(으)로 예상됨',
	'wikibase-validator-bad-url' => '잘못된 형식의 URL: $1',
	'wikibase-validator-bad-url-scheme' => '지원하지 않는 URL 계획: $1',
	'wikibase-validator-bad-http-url' => '잘못된 형식의 HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => '잘못된 형식의 mailto URL: $1',
	'wikibase-validator-unknown-unit' => '알수 없는 장치: $1',
	'datatypes-type-wikibase-item' => '항목',
	'datatypes-type-commonsMedia' => '공용 미디어 파일',
	'version-wikibase' => '위키베이스',
);

/** Colognian (Ripoarisch)
 * @author Purodha
 */
$messages['ksh'] = array(
	'wikibase-lib-desc' => 'Jemeinsamme Fungxjuhne för di Projramm-Zohsäz <i lang="en">Wikibase</i> un <i lang="en">Wikibase Client</i>.',
	'wikibase-entity-item' => 'dä Jääjeschtand',
	'wikibase-entity-property' => 'di Eijeschaff',
	'wikibase-entity-query' => 'Frooch',
	'wikibase-sitelinks-sitename-columnheading' => 'Schprooch',
	'wikibase-sitelinks-siteid-columnheading' => 'Köözel',
	'wikibase-tooltip-error-details' => 'Einzelheite',
	'datatypes-type-wikibase-item' => 'Jääjeschtand',
	'datatypes-type-commonsMedia' => 'Meedijedattei vun Wikkimeedija Commons',
);

/** Kurdish (Latin script) (Kurdî (latînî)‎)
 * @author George Animal
 */
$messages['ku-latn'] = array(
	'wikibase-entity-item' => 'obje',
	'wikibase-sitelinks-sitename-columnheading' => 'Ziman',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Gotara girêdayî', # Fuzzy
	'wikibase-tooltip-error-details' => 'Detay',
);

/** Kyrgyz (Кыргызча)
 * @author Growingup
 */
$messages['ky'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'Тил',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
);

/** Ladino (Ladino)
 * @author Menachem.Moreira
 */
$messages['lad'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'Lengua',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 * @author Soued031
 */
$messages['lb'] = array(
	'wikibase-entity-item' => 'Element',
	'wikibase-entity-property' => 'Eegeschaft',
	'wikibase-entity-query' => 'Ufro',
	'wikibase-deletedentity-item' => 'Geläschten Element',
	'wikibase-deletedentity-property' => 'Geläschten Eegeschaft',
	'wikibase-deletedentity-query' => 'Geläschten Offro',
	'wikibase-diffview-reference' => 'Referenz',
	'wikibase-diffview-rank' => 'Classement',
	'wikibase-diffview-label' => 'Etikett',
	'wikibase-diffview-alias' => 'Aliasen',
	'wikibase-diffview-description' => 'Beschreiwung',
	'wikibase-diffview-link' => 'Linken',
	'wikibase-error-unexpected' => 'En onerwaarte Feeler ass geschitt.',
	'wikibase-error-save-generic' => 'Beim Späicheren ass e Feeler geschitt an dofir konnten Är Ännerungen net ofgeschloss ginn.',
	'wikibase-error-save-timeout' => 'Mir hunn technesch Schwieregkeeten an dofir konnt Är Ännerung net "gespäichert" ginn.',
	'wikibase-error-remove-timeout' => 'Mir hunn technesch Schwieregkeeten an dofir konnt Är "Läschung" net "gespäichert" ginn.',
	'wikibase-error-autocomplete-connection' => 'Den API-Site konnt net ofgefrot ginn. Probéiert w.e.g. méi spéit nach eng Kéier.',
	'wikibase-error-autocomplete-response' => 'Äntwert vum Server: $1',
	'wikibase-error-ui-no-external-page' => 'De spezifizéierten Artikel konnt op dem korrespondéierte Site net fonnt ginn.',
	'wikibase-error-ui-cant-edit' => 'Dir däerft dës Aktioun net maachen.',
	'wikibase-error-ui-no-permissions' => 'Dir hutt net genuch Rechter fir dës Aktioun ze maachen.',
	'wikibase-error-ui-link-exists' => 'Dir kënnt kee Link mat dëser Säit maachen well schonn een anert Element hei hinner linkt.',
	'wikibase-error-ui-session-failure' => 'Är Sessioun ass ofgelaf. Loggt Iech w.e.g. nees an.',
	'wikibase-error-ui-edit-conflict' => "Et gëtt en Editiounskonflikt. Luet d'Säit nees a späichert nach eng Kéier.",
	'wikibase-quantitydetails-unit' => 'Eenheet',
	'wikibase-timedetails-time' => 'Zäit',
	'wikibase-timedetails-timezone' => 'Zäitzon',
	'wikibase-timedetails-calendar' => 'Kalenner',
	'wikibase-timedetails-precision' => 'Präzisioun',
	'wikibase-timedetails-before' => 'Virdrun',
	'wikibase-timedetails-after' => 'Duerno',
	'wikibase-globedetails-precision' => 'Präzisioun',
	'wikibase-globedetails-globe' => 'Globus',
	'wikibase-replicationnote' => "Denkt w.e.g. dorun datt et e puer Minutten dauere ka bis d'Ännerungen op alle Wikien ze gesi sinn.",
	'wikibase-sitelinks-wikipedia' => 'Wikipediasäiten déi mat dësem Element verlinkt sinn',
	'wikibase-sitelinks-sitename-columnheading' => 'Sprooch',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Verlinkt Säit',
	'wikibase-tooltip-error-details' => 'Detailer',
	'wikibase-undeserializable-value' => 'De Wäert ass net valabel a kann net gewise ginn.',
	'wikibase-validator-bad-type' => '$2 amplaz vu(n) $1',
	'wikibase-validator-no-such-entity' => '$1 net fonnt',
	'wikibase-validator-no-such-property' => 'Eegeschaft $1 net fonnt',
	'wikibase-validator-bad-value' => 'net valabele wäert: $1',
	'wikibase-validator-bad-url' => 'URL mat Feeler: $1',
	'wikibase-validator-unknown-unit' => 'Onbekannten Eenheet: $1',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Media-Fichier op Commons',
	'version-wikibase' => 'Wikibase',
);

/** لوری (لوری)
 * @author Bonevarluri
 */
$messages['lrc'] = array(
	'wikibase-error-ui-session-failure' => 'نشستگه شما تموم بیه. لطفن دواره بیایت وامین.',
	'wikibase-sitelinks-sitename-columnheading' => 'زون',
	'wikibase-sitelinks-sitename-columnheading-special' => 'سیلجا',
	'datatypes-type-commonsMedia' => 'فایل پرنمون رسانه',
);

/** Lithuanian (lietuvių)
 * @author Eitvys200
 * @author Hugo.arg
 */
$messages['lt'] = array(
	'wikibase-entity-item' => 'įrašas',
	'wikibase-entity-property' => 'savybė',
	'wikibase-entity-query' => 'užklausa',
	'wikibase-deletedentity-item' => 'Pašalintas įrašas',
	'wikibase-deletedentity-property' => 'Pašalinta savybė',
	'wikibase-deletedentity-query' => 'Pašalinta užklausa',
	'wikibase-diffview-reference' => 'išnaša',
	'wikibase-diffview-rank' => 'rangas',
	'wikibase-diffview-qualifier' => 'kvalifikatorius',
	'wikibase-diffview-label' => 'žymė',
	'wikibase-diffview-alias' => 'sinonimai',
	'wikibase-diffview-description' => 'aprašas',
	'wikibase-diffview-link' => 'nuorodos',
	'wikibase-error-unexpected' => 'Įvyko netikėta klaida.',
	'wikibase-error-save-generic' => 'Bandant atlikti išsaugojimą, įvyko klaida, todėl jūsų keitimai negali būti užbaigti.',
	'wikibase-error-remove-generic' => 'Bandant atlikti pašalinimą, įvyko klaida, todėl jūsų keitimai negali būti užbaigti.',
	'wikibase-error-save-connection' => 'Bandant atlikti išsaugojimą, įvyko sujungimo klaida, todėl jūsų keitimai negali būti užbaigti. Patikrinkite savo interneto ryšį.',
	'wikibase-error-remove-connection' => 'Bandant atlikti pašalinimą, įvyko sujungimo klaida, todėl jūsų keitimai negali būti užbaigti. Patikrinkite savo interneto ryšį.',
	'wikibase-error-save-timeout' => 'Sistemoje yra techninių nesklaidumų, todėl jūsų išsaugojimas negali būti įvykdytas.',
	'wikibase-error-remove-timeout' => 'Sistemoje yra techninių nesklaidumų, todėl jūsų pašalinimas negali būti įvykdytas.',
	'wikibase-error-autocomplete-response' => 'Serverio atsakas: $1',
	'wikibase-error-ui-no-external-page' => 'Nurodytas straipsnis nerastas atitinkamoje svetainėje.',
	'wikibase-error-ui-cant-edit' => 'Šio veiksmo jums atlikti neleidžiama.',
	'wikibase-error-ui-no-permissions' => 'Šio veiksmo atlikimui neturite reikiamų teisių.',
	'wikibase-error-ui-link-exists' => 'Negalite sukurti nuorodos į šį puslapį, nes su juo jau susietas kitas įrašas.',
	'wikibase-error-ui-session-failure' => 'Jūsų redagavimo laikotarpis išseko. Prisijunkite iš naujo.',
	'wikibase-error-ui-edit-conflict' => 'Įvyko redagavimo konfliktas. Perkraukite ir vėl išsaugokite.',
	'wikibase-replicationnote' => 'Atkreipkite dėmesį, kad gali praeiti keletas minučių kol keitimai bus matomi visose wiki.',
	'wikibase-sitelinks-wikipedia' => 'Vikipedijos puslapiai, susieti su šiuo įrašu',
	'wikibase-sitelinks-sitename-columnheading' => 'Kalba',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodas',
	'wikibase-sitelinks-link-columnheading' => 'Susietas straipsnis', # Fuzzy
	'wikibase-tooltip-error-details' => 'Detalės',
	'wikibase-validator-bad-type' => '$2 vietoje $1',
	'wikibase-validator-too-long' => 'Turi būti ne daugiau nei {{PLURAL:$1|vieno ženklo|$1 ženklų}} ilgio',
	'wikibase-validator-too-short' => 'Turi būti bent {{PLURAL:$1|vieno ženklo|$1 ženklų}} ilgio',
	'wikibase-validator-malformed-value' => 'Neteisingas įvedimas: $1',
	'wikibase-validator-bad-entity-id' => 'Neteisingas ID: $1',
	'wikibase-validator-bad-entity-type' => 'Netikėtas darinio tipas $1',
	'wikibase-validator-no-such-entity' => '$1 nerasta',
	'datatypes-type-wikibase-item' => 'Įrašas',
	'datatypes-type-commonsMedia' => 'Vikitekos byla',
	'version-wikibase' => 'Vikibazė',
);

/** Latvian (latviešu)
 * @author Papuass
 */
$messages['lv'] = array(
	'wikibase-entity-item' => 'ieraksts',
	'wikibase-entity-property' => 'īpašība',
	'wikibase-entity-query' => 'vaicājums',
	'wikibase-deletedentity-item' => 'Dzēsts ieraksts',
	'wikibase-deletedentity-property' => 'Dzēsta īpašība',
	'wikibase-deletedentity-query' => 'Dzēsts vaicājums',
	'wikibase-diffview-reference' => 'atsauce',
	'wikibase-diffview-rank' => 'rangs',
	'wikibase-diffview-qualifier' => 'ierobežotājs',
	'wikibase-diffview-label' => 'nosaukums',
	'wikibase-diffview-alias' => 'citi nosaukumi',
	'wikibase-diffview-description' => 'apraksts',
	'wikibase-diffview-link' => 'saites',
	'wikibase-error-unexpected' => 'Radās neparedzēta kļūda.',
	'wikibase-error-autocomplete-response' => 'Servera atbilde: $1',
	'wikibase-sitelinks-sitename-columnheading' => 'Valoda',
	'wikibase-sitelinks-siteid-columnheading' => 'Kods',
	'wikibase-sitelinks-link-columnheading' => 'Saistītā lapa',
	'wikibase-validator-bad-value' => 'Neatļauta vērtība: $1',
	'datatypes-type-wikibase-item' => 'Ieraksts',
	'datatypes-type-commonsMedia' => 'Commons multivides fails',
);

/** Macedonian (македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'wikibase-lib-desc' => 'Содржи почести функции за додатоците Викибаза и Клиент на Викибазата.',
	'wikibase-entity-item' => 'предмет',
	'wikibase-entity-property' => 'својство',
	'wikibase-entity-query' => 'барање',
	'wikibase-deletedentity-item' => 'Избришан предмет',
	'wikibase-deletedentity-property' => 'Избришано својство',
	'wikibase-deletedentity-query' => 'Избришано барање',
	'wikibase-diffview-reference' => 'навод',
	'wikibase-diffview-rank' => 'ранг',
	'wikibase-diffview-rank-preferred' => 'Претпочитано',
	'wikibase-diffview-rank-normal' => 'Нормално',
	'wikibase-diffview-rank-deprecated' => 'Застарено',
	'wikibase-diffview-qualifier' => 'определница',
	'wikibase-diffview-label' => 'етикета',
	'wikibase-diffview-alias' => 'алијаси',
	'wikibase-diffview-description' => 'опис',
	'wikibase-diffview-link' => 'врски',
	'wikibase-error-unexpected' => 'Се појави неочекувана грешка.',
	'wikibase-error-save-generic' => 'Наидов на грешка. Не можам да ги зачувам направените промени.',
	'wikibase-error-remove-generic' => 'Наидов на грешка при отстранувањето, па затоа постапката не е извршена.',
	'wikibase-error-save-connection' => 'Не можев да ги зачувам промените бидејќи се појави грешка во линијата. Проверете си ја семрежната врска.',
	'wikibase-error-remove-connection' => 'Не можев да го извршам отстранувањето бидејќи се појави грешка во линијата. Проверете си ја семрежната врска.',
	'wikibase-error-save-timeout' => 'Се соочуваме со технички потешкотии. Затоа, не можев да ги зачувам вашите промени.',
	'wikibase-error-remove-timeout' => 'Се соочуваме со технички потешкотии. Затоа, не можев да го извршам отстранувањето.',
	'wikibase-error-autocomplete-connection' => 'Не можев да го добијам прилогот на мрежното место. Обидете се подоцна.',
	'wikibase-error-autocomplete-response' => 'Одговор на опслужувачот: $1',
	'wikibase-error-ui-client-error' => 'Врската со клиентската страница е прекината. Обидете се подоцна.',
	'wikibase-error-ui-no-external-page' => 'Укажаната статија не е најдена на соодветното вики.',
	'wikibase-error-ui-cant-edit' => 'Не сте овластени да ја извршите оваа постапка.',
	'wikibase-error-ui-no-permissions' => 'Ги немате потребните права за да го извршите ова дејство.',
	'wikibase-error-ui-link-exists' => 'Не можете да ставите врска за оваа страница бидејќи веќе има друг предмет што води до неа.',
	'wikibase-error-ui-session-failure' => 'Сесијата истече. Најавете се повторно.',
	'wikibase-error-ui-edit-conflict' => 'Се јави спротиставеност во уредувањата. Превчитајте и зачувајте повторно.',
	'wikibase-quantitydetails-amount' => 'Износ',
	'wikibase-quantitydetails-upperbound' => 'Горна граница',
	'wikibase-quantitydetails-lowerbound' => 'Долна граница',
	'wikibase-quantitydetails-unit' => 'Единица',
	'wikibase-timedetails-time' => 'Време',
	'wikibase-timedetails-isotime' => 'Време и датум во ISO',
	'wikibase-timedetails-timezone' => 'Часовен појас:',
	'wikibase-timedetails-calendar' => 'Календар',
	'wikibase-timedetails-precision' => 'Уточнетост:',
	'wikibase-timedetails-before' => 'Пред',
	'wikibase-timedetails-after' => 'По',
	'wikibase-globedetails-longitude' => 'Геог. должина',
	'wikibase-globedetails-latitude' => 'Геог. ширина',
	'wikibase-globedetails-precision' => 'Уточнетост:',
	'wikibase-globedetails-globe' => 'Глобус',
	'wikibase-replicationnote' => 'Имајте предвид дека се потребни неколку минути за промените да станат видливи на сите викија',
	'wikibase-sitelinks-wikipedia' => 'Страници на Википедија сврзани со предметот',
	'wikibase-sitelinks-sitename-columnheading' => 'Јазик',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Вики',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Сврзана страница',
	'wikibase-tooltip-error-details' => 'Подробно',
	'wikibase-undeserializable-value' => 'Вредноста е неважечка и затоа не може да се прикаже.',
	'wikibase-validator-bad-type' => '$2 наместо $1',
	'wikibase-validator-too-long' => 'Не може да има повеќе од {{PLURAL:$1|еден знак|$1 знаци}}',
	'wikibase-validator-too-short' => 'Мора да има барем {{PLURAL:$1|еден знак|$1 знаци}}',
	'wikibase-validator-too-high' => 'Отстапивте од допуштениот опсег. Не може да биде поголемо од $1.',
	'wikibase-validator-too-low' => 'Отстапивте од допуштениот опсег. Не може да биде помало од $1.',
	'wikibase-validator-malformed-value' => 'Погрешно обликуван внос: $1',
	'wikibase-validator-bad-entity-id' => 'Погрешно обликувана назнака: $1',
	'wikibase-validator-bad-entity-type' => 'Неочекуван тип на својство: $1',
	'wikibase-validator-no-such-entity' => 'Не го пронајдов $1',
	'wikibase-validator-no-such-property' => 'Својството $1 не е пронајдено',
	'wikibase-validator-bad-value' => 'Недопуштена вредност: $1',
	'wikibase-validator-bad-value-type' => 'Погрешен тип на вредност $1; се очкеува $2',
	'wikibase-validator-bad-url' => 'Неисправно обликувана URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Неподдржана URL: $1',
	'wikibase-validator-bad-http-url' => 'Неисправно обликувана HTTP-URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Неисправно обликувана mailto-URL: $1',
	'wikibase-validator-unknown-unit' => 'Непозната единица: $1',
	'datatypes-type-wikibase-item' => 'Предмет',
	'datatypes-type-commonsMedia' => 'Податотека од Ризницата',
	'version-wikibase' => 'Викибаза',
);

/** Malayalam (മലയാളം)
 * @author Praveenp
 * @author Vssun
 */
$messages['ml'] = array(
	'wikibase-lib-desc' => 'വിക്കിബേസിനും വിക്കിബേസ് ക്ലയന്റ് അനുബന്ധങ്ങൾക്കുമുള്ള പൊതു പ്രവർത്തനരീതി',
	'wikibase-entity-item' => 'ഇനം',
	'wikibase-entity-property' => 'ഗുണം',
	'wikibase-entity-query' => 'ക്വറി',
	'wikibase-deletedentity-item' => 'മായ്ക്കപ്പെട്ട ഇനം',
	'wikibase-deletedentity-property' => 'മായ്ക്കപ്പെട്ട ഗുണം',
	'wikibase-deletedentity-query' => 'മായ്ക്കപ്പെട്ട ക്വറി',
	'wikibase-diffview-reference' => 'അവലംബം',
	'wikibase-diffview-rank' => 'റാങ്ക്',
	'wikibase-diffview-rank-preferred' => 'ഉദ്ദേശിക്കുന്ന റാങ്ക്',
	'wikibase-diffview-rank-normal' => 'സാധാരണ റാങ്ക്',
	'wikibase-diffview-rank-deprecated' => 'ഒഴിവാക്കിയ റാങ്ക്',
	'wikibase-diffview-qualifier' => 'യോഗ്യതാപരിശോധിനി',
	'wikibase-diffview-label' => 'തലക്കുറി',
	'wikibase-diffview-alias' => 'അപരനാമങ്ങൾ',
	'wikibase-diffview-description' => 'വിവരണം',
	'wikibase-diffview-link' => 'കണ്ണികൾ',
	'wikibase-error-unexpected' => 'അപ്രതീക്ഷിതമായ പിഴവ് ഉണ്ടായി.',
	'wikibase-error-save-generic' => 'സേവ് ചെയ്യാൻ ശ്രമിച്ചപ്പോൾ ഒരു പിഴവുണ്ടായതിനാൽ താങ്കൾ വരുത്തിയ മാറ്റങ്ങൾ പൂർണ്ണമാക്കാനായിട്ടില്ല.',
	'wikibase-error-remove-generic' => 'നീക്കം ചെയ്യാൻ ശ്രമിച്ചപ്പോൾ ഒരു പിഴവുണ്ടായതിനാൽ താങ്കൾ വരുത്തിയ മാറ്റങ്ങൾ പൂർണ്ണമാക്കാനായിട്ടില്ല.',
	'wikibase-error-save-connection' => 'സേവ് ചെയ്യാൻ ശ്രമിക്കുന്നതിനിടെ ബന്ധത്തിൽ പിഴവുണ്ടായതിനാൽ, താങ്കളുടെ മാറ്റങ്ങൾ പൂർണ്ണമാക്കാനായിട്ടില്ല. ദയവായി താങ്കളുടെ ഇന്റർനെറ്റ് ബന്ധം പരിശോധിക്കുക.',
	'wikibase-error-remove-connection' => 'നീക്കം ചെയ്യാൻ ശ്രമിക്കുന്നതിനിടെ ബന്ധത്തിൽ പിഴവുണ്ടായതിനാൽ, താങ്കളുടെ മാറ്റങ്ങൾ പൂർണ്ണമാക്കാനായിട്ടില്ല. ദയവായി താങ്കളുടെ ഇന്റർനെറ്റ് ബന്ധം പരിശോധിക്കുക.',
	'wikibase-error-save-timeout' => 'ഞങ്ങൾ സാങ്കേതിക പ്രശ്നങ്ങൾ നേരിടുന്നതിനാൽ, താങ്കളുടെ "സേവ്" പ്രക്രിയ പൂർത്തിയാക്കാനായിട്ടില്ല.',
	'wikibase-error-remove-timeout' => 'ഞങ്ങൾ സാങ്കേതിക പ്രശ്നങ്ങൾ നേരിടുന്നതിനാൽ, താങ്കൾ ആവശ്യപ്പെട്ട "നീക്കം ചെയ്യൽ" പ്രക്രിയ പൂർത്തിയാക്കാനായിട്ടില്ല.',
	'wikibase-error-autocomplete-connection' => 'സൈറ്റ് എ.പി.ഐ. പരിശോധിക്കാൻ കഴിയുന്നില്ല. ദയവായി പിന്നീട് വീണ്ടും ശ്രമിക്കുക.',
	'wikibase-error-autocomplete-response' => 'സെർവർ പ്രതികരണം: $1',
	'wikibase-error-ui-client-error' => 'ക്ലയന്റ് താളിലേയ്ക്കുള്ള ബന്ധം പരാജയപ്പെട്ടു. ദയവായി പിന്നീട് വീണ്ടും ശ്രമിക്കുക.',
	'wikibase-error-ui-no-external-page' => 'ബന്ധപ്പെട്ട സൈറ്റിൽ, വ്യക്തമാക്കിയ ലേഖനം കണ്ടെത്താനായില്ല.',
	'wikibase-error-ui-cant-edit' => 'ഈ പ്രവൃത്തി ചെയ്യാൻ താങ്കൾക്ക് അനുവാദമില്ല.',
	'wikibase-error-ui-no-permissions' => 'ഈ പ്രവൃത്തി ചെയ്യാൻ ആവശ്യമായ അവകാശങ്ങൾ താങ്കൾക്കില്ല.',
	'wikibase-error-ui-link-exists' => 'ഈ താളുമായി മറ്റൊരു ഇനം മുമ്പേ തന്നെ ബന്ധപ്പെടുത്തിയിരിക്കുന്നതിനാൽ ഇത് കണ്ണി ചേർക്കാൻ താങ്കൾക്കാവില്ല.',
	'wikibase-error-ui-session-failure' => 'താങ്കളുടെ സെഷൻ കാലഹരണപ്പെട്ടിരിക്കുന്നു. ദയവായി വീണ്ടും പ്രവേശിക്കുക.',
	'wikibase-error-ui-edit-conflict' => 'തിരുത്തൽ സമരസപ്പെടായ്ക ഉണ്ടായിരിക്കുന്നു. റീലോഡ് ചെയ്ത ശേഷം വീണ്ടും സേവ് ചെയ്യുക.',
	'wikibase-quantitydetails-amount' => 'തുക',
	'wikibase-quantitydetails-upperbound' => 'ഉയർന്ന പരിധി',
	'wikibase-quantitydetails-lowerbound' => 'താഴ്ന് പരിധി',
	'wikibase-quantitydetails-unit' => 'ഏകകം',
	'wikibase-timedetails-time' => 'സമയം',
	'wikibase-timedetails-isotime' => 'ഐ.എസ്.ഒ. സമയമുദ്ര',
	'wikibase-timedetails-timezone' => 'സമയ മേഖല',
	'wikibase-timedetails-calendar' => 'കാലഗണനാരീതി',
	'wikibase-timedetails-precision' => 'കൃത്യത',
	'wikibase-timedetails-before' => 'മുമ്പ്',
	'wikibase-timedetails-after' => 'ശേഷം',
	'wikibase-globedetails-longitude' => 'രേഖാംശം',
	'wikibase-globedetails-latitude' => 'അക്ഷാംശം',
	'wikibase-globedetails-precision' => 'കൃത്യത',
	'wikibase-globedetails-globe' => 'ഭൂഗോളം',
	'wikibase-replicationnote' => 'മാറ്റങ്ങൾ എല്ലാ വിക്കികളിലും പ്രത്യക്ഷപ്പെടാൻ കുറച്ച് മിനിറ്റുകൾ എടുത്തേക്കും എന്നത് പ്രത്യേകം ശ്രദ്ധിക്കുക',
	'wikibase-sitelinks-wikipedia' => 'ഈ ഇനവുമായി കണ്ണിചേർത്തിരിക്കുന്ന വിക്കിപീഡിയ താളുകൾ',
	'wikibase-sitelinks-sitename-columnheading' => 'ഭാഷ',
	'wikibase-sitelinks-sitename-columnheading-special' => 'സൈറ്റ്',
	'wikibase-sitelinks-siteid-columnheading' => 'കോഡ്',
	'wikibase-sitelinks-link-columnheading' => 'കണ്ണിചേർത്തിട്ടുള്ള താൾ',
	'wikibase-tooltip-error-details' => 'വിശദാംശങ്ങൾ',
	'wikibase-undeserializable-value' => 'വില അസാധുവായതിനാൽ പ്രദർശിപ്പിക്കാനാവില്ല.',
	'wikibase-validator-bad-type' => '$1 എന്നതിനു പകരം $2',
	'wikibase-validator-too-long' => '{{PLURAL:$1|ഒരക്ഷരത്തിലും|$1 അക്ഷരങ്ങളിലും}} കൂടുതൽ നീളമുള്ളതാവാൻ പാടില്ല',
	'wikibase-validator-too-short' => 'കുറഞ്ഞത് {{PLURAL:$1|ഒരക്ഷരമെങ്കിലും|$1 അക്ഷരങ്ങളെങ്കിലും}} നീളമുള്ളതായിരിക്കണം',
	'wikibase-validator-too-high' => 'പരിധിയ്ക്ക് പുറത്താണ്, $1 എന്നതിലും മുകളിലാവാൻ പാടില്ല',
	'wikibase-validator-too-low' => 'പരിധിയ്ക്ക് പുറത്താണ്, $1 എന്നതിലും താഴെയാവാൻ പാടില്ല',
	'wikibase-validator-malformed-value' => 'തെറ്റായവിധത്തിലുള്ള ഇൻപുട്ട്: $1',
	'wikibase-validator-bad-entity-id' => 'തെറ്റായവിധത്തിലുള്ള ഐ.ഡി.: $1',
	'wikibase-validator-bad-entity-type' => 'അപ്രതീക്ഷിതമായ ഇന തരം $1',
	'wikibase-validator-no-such-entity' => '$1 കണ്ടെത്താനായില്ല',
	'wikibase-validator-no-such-property' => '$1 എന്ന ഗുണം കണ്ടെത്താനായില്ല',
	'wikibase-validator-bad-value' => 'അസാധുവായ വില: $1',
	'wikibase-validator-bad-value-type' => 'വിലയുടെ തരം $1 മോശമാണ്, $2 ആണ് പ്രതീക്ഷിച്ചത്',
	'wikibase-validator-bad-url' => 'തെറ്റായവിധത്തിലുള്ള യു.ആർ.എൽ.: $1',
	'wikibase-validator-bad-url-scheme' => 'പിന്തുണയില്ലാത്ത യു.ആർ.എൽ. സമ്പ്രദായം: $1',
	'wikibase-validator-bad-http-url' => 'തെറ്റായവിധത്തിലുള്ള എച്ച്.റ്റി.റ്റി.പി. യു.ആർ.എൽ.: $1',
	'wikibase-validator-bad-mailto-url' => 'തെറ്റായവിധത്തിലുള്ള മെയിൽറ്റു യു.ആർ.എൽ.: $1',
	'wikibase-validator-unknown-unit' => 'അപരിചിതമായ ഏകകം: $1',
	'datatypes-type-wikibase-item' => 'ഇനം',
	'datatypes-type-commonsMedia' => 'കോമൺസിൽ നിന്നുള്ള മീഡിയ പ്രമാണം',
	'version-wikibase' => 'വിക്കിബേസ്',
);

/** Mongolian (монгол)
 * @author Mongol
 */
$messages['mn'] = array(
	'wikibase-timedetails-time' => 'Хугацаа',
	'wikibase-timedetails-isotime' => 'ISO хугацааны тамга',
	'wikibase-timedetails-timezone' => 'Цагийн бүс',
	'wikibase-timedetails-calendar' => 'Календарь',
	'wikibase-timedetails-precision' => 'Нарийвчлал',
	'wikibase-timedetails-before' => 'Өмнө',
	'wikibase-timedetails-after' => 'Дараа',
	'wikibase-globedetails-longitude' => 'Уртраг',
	'wikibase-globedetails-latitude' => 'Өргөрөг',
	'wikibase-globedetails-precision' => 'Нарийвчлал',
	'wikibase-globedetails-globe' => 'Бөмбөрцөг',
);

/** Marathi (मराठी)
 * @author V.narsikar
 * @author संतोष दहिवळ
 */
$messages['mr'] = array(
	'wikibase-entity-item' => 'कलम',
	'wikibase-entity-property' => 'गुणधर्म',
	'wikibase-entity-query' => 'पॄच्छा',
	'wikibase-deletedentity-item' => 'वगळलेले कलम',
	'wikibase-deletedentity-property' => 'वगळलेला गुणधर्म',
	'wikibase-deletedentity-query' => 'वगळलेली पृच्छा',
	'wikibase-diffview-reference' => 'संदर्भ',
	'wikibase-diffview-rank' => 'पद',
	'wikibase-diffview-rank-preferred' => 'आवडलेले पद',
	'wikibase-diffview-rank-normal' => 'सामान्य पद',
	'wikibase-diffview-rank-deprecated' => 'नापसंत श्रेणी',
	'wikibase-diffview-label' => 'लेबल',
	'wikibase-diffview-alias' => 'पर्याय',
	'wikibase-diffview-description' => 'वर्णन',
	'wikibase-diffview-link' => 'दुवे',
	'wikibase-error-unexpected' => 'अनपेक्षित त्रूटी घडली.',
	'wikibase-error-save-generic' => 'जतन करण्याच्या क्रियेत त्रूटी घडली व त्याकारणाने,आपले बदल पूर्ण करता आले नाहीत.',
	'wikibase-error-remove-generic' => 'वगळण्याच्या क्रियेत त्रूटी घडली व त्याकारणाने,आपले बदल पूर्ण करता आले नाहीत.',
	'wikibase-error-save-connection' => 'जतन करण्याच्या क्रियेत अनुबंध(कनेक्शन) त्रूटी घडली व त्याकारणाने,आपले बदल पूर्ण करता आले नाहीत.कृपया आपला आंतरजाल अनुबंध तपासा.',
	'wikibase-error-remove-connection' => 'वगळण्याच्या क्रियेत अनुबंध(कनेक्शन) त्रूटी घडली व त्याकारणाने,आपले बदल पूर्ण करता आले नाहीत.कृपया आपला आंतरजाल अनुबंध तपासा.',
	'wikibase-error-save-timeout' => 'आम्ही तांत्रिक समस्यांचा सामना करीत आहोत. त्याकारणाने, आपले "जतन करा" पूर्ण करता आले नाही.',
	'wikibase-error-remove-timeout' => 'आम्ही तांत्रिक समस्यांचा सामना करीत आहोत. त्याकारणाने, आपले "वगळा" पूर्ण करता आले नाही.',
	'wikibase-error-autocomplete-connection' => 'संकेतस्थळाच्या API ला पृच्छा करू शकलो नाही. कृपया नंतर पुन्हा प्रयत्न करा.',
	'wikibase-error-autocomplete-response' => 'विदागाराने प्रतिसाद दिला:$1',
	'wikibase-error-ui-client-error' => 'ग्राहक पानाशी अनुबंध(कनेक्शन) अयशस्वी. कृपया नंतर पुन्हा प्रयत्न करा.',
	'wikibase-error-ui-no-external-page' => 'सुसंगत संकेतस्थळावर नमूद लेख सापडला नाही.',
	'wikibase-error-ui-cant-edit' => 'आपणास ही क्रिया करण्याची परवानगी नाही.',
	'wikibase-error-ui-no-permissions' => 'आपणापाशी ही क्रिया करण्याचे पुरेसे अधिकार नाहीत.',
	'wikibase-error-ui-link-exists' => 'आपण हे पान जोडू शकत नाही कारण दुसरे कलम त्याचेशी पूर्वीच जुळलेले आहे.',
	'wikibase-error-ui-session-failure' => 'आपले सत्र संपले. कृपया पुन्हा सनोंद प्रवेश करा.',
	'wikibase-error-ui-edit-conflict' => 'संपादन विसंवाद. कृपया पुनर्भारण करून जतन करा.',
	'wikibase-quantitydetails-amount' => 'रक्कम',
	'wikibase-quantitydetails-upperbound' => 'उच्चतम मर्यादा',
	'wikibase-quantitydetails-lowerbound' => 'निम्नतम मर्यादा',
	'wikibase-quantitydetails-unit' => 'एकक',
	'wikibase-replicationnote' => 'कृपया याची नोंद घ्या कि सर्व विकिंवर हे बदल दिसण्यासाठी अनेक मिनीटे लागू शकतील.',
	'wikibase-sitelinks-wikipedia' => 'या कलमास जोडलेली विकिपीडियाची पाने',
	'wikibase-sitelinks-sitename-columnheading' => 'भाषा',
	'wikibase-sitelinks-sitename-columnheading-special' => 'संकेतस्थळ',
	'wikibase-sitelinks-siteid-columnheading' => 'संकेत',
	'wikibase-sitelinks-link-columnheading' => 'जोडलेले पान',
	'wikibase-tooltip-error-details' => 'तपशील',
	'wikibase-undeserializable-value' => 'किंमत अवैध आहे म्हणून दाखविल्या जाउ शकत नाही.',
	'wikibase-validator-bad-type' => '$1 ऐवजी $2',
	'wikibase-validator-too-long' => '{{PLURAL:$1|वर्णापेक्षा|$1 वर्णांपेक्षा}} जास्त लांबी नको',
	'wikibase-validator-too-short' => 'किमान {{PLURAL:$1|वर्णापेक्षा|$1 वर्णांपेक्षा}} जास्त लांबी हवी',
	'wikibase-validator-too-high' => 'आवाक्याबाहेर.$1 पेक्षा जास्त नको.',
	'wikibase-validator-too-low' => 'आवाक्याबाहेर.$1 पेक्षा कमी नको.',
	'wikibase-validator-malformed-value' => 'विकृत अंतःक्षेप:$1',
	'wikibase-validator-bad-entity-id' => 'विकृत ओळखण:$1',
	'wikibase-validator-bad-entity-type' => 'अनपेक्षित अस्तित्व असलेला प्रकार $1',
	'wikibase-validator-no-such-entity' => '$1 सापडले नाही',
	'wikibase-validator-no-such-property' => 'गुणधर्म $1 सापडला नाही',
	'wikibase-validator-bad-value' => 'अवैध किंमत:$1',
	'wikibase-validator-bad-value-type' => 'वाईट मुल्य प्रकार $1, अपेक्षित $2',
	'wikibase-validator-bad-url' => 'विकृत यूआरएल: $1',
	'wikibase-validator-unknown-unit' => 'अनोळखी एकक: $1',
	'datatypes-type-wikibase-item' => 'कलम',
	'datatypes-type-commonsMedia' => 'कॉमन्स मिडिया संचिका',
	'version-wikibase' => 'विकिबेस',
);

/** Malay (Bahasa Melayu)
 * @author Anakmalaysia
 * @author Shirayuki
 */
$messages['ms'] = array(
	'wikibase-lib-desc' => 'Memegang kefungsian sepunya untuk sambungan Wikibase dan Wikibase Client',
	'wikibase-entity-item' => 'perkara',
	'wikibase-entity-property' => 'sifat',
	'wikibase-entity-query' => 'pertanyaan',
	'wikibase-deletedentity-item' => 'Perkara yang terhapus',
	'wikibase-deletedentity-property' => 'Sifat yang terhapus',
	'wikibase-deletedentity-query' => 'Pertanyaan yang terhapus',
	'wikibase-diffview-reference' => 'rujukan',
	'wikibase-diffview-rank' => 'kedudukan',
	'wikibase-diffview-qualifier' => 'penerang',
	'wikibase-diffview-label' => 'label',
	'wikibase-diffview-alias' => 'alias',
	'wikibase-diffview-description' => 'keterangan',
	'wikibase-diffview-link' => 'pautan',
	'wikibase-error-unexpected' => 'Berlakunya ralat luar jangkaan.',
	'wikibase-error-save-generic' => 'Suatu ralat telah berlaku apabila cuba melakukan penyimpanan; oleh itu, pengubahan anda tidak dapat disiapkan.',
	'wikibase-error-remove-generic' => 'Suatu ralat telah berlaku apabila cuba melakukan pembuangan; oleh itu, pengubahan anda tidak dapat disiapkan.',
	'wikibase-error-save-connection' => 'Ralat penyambungan telah berlaku apabila cuba melakukan penyimpanan; oleh itu, pengubahan anda tidak dapat disiapkan. Sila semak sambungan Internet anda.',
	'wikibase-error-remove-connection' => 'Ralat penyambungan telah berlaku apabila cuba melakukan penyimpanan; oleh itu, pengubahan anda tidak dapat disiapkan. Sila semak sambungan Internet anda.',
	'wikibase-error-save-timeout' => 'Kami sedang mengalami kesulitan teknikal, oleh itu "simpanan" anda tidak dapat dilengkapkan.',
	'wikibase-error-remove-timeout' => 'Kami sedang mengalami kesulitan teknikal, oleh itu "pembuangan" anda tidak dapat dilengkapkan.',
	'wikibase-error-autocomplete-connection' => 'API tapak tidak dapat ditanya. Sila cuba lagi kemudian.',
	'wikibase-error-autocomplete-response' => 'Pelayan membalas: $1',
	'wikibase-error-ui-client-error' => 'Sambungan dengan halaman pelanggan gagal. Sila cuba lagi kemudian.',
	'wikibase-error-ui-no-external-page' => 'Rencana yang dinyatakan tidak dapat dijumpai di halaman yang berpadanan.',
	'wikibase-error-ui-cant-edit' => 'Anda tidak dibenarkan melakukan tindakan ini.',
	'wikibase-error-ui-no-permissions' => 'Anda tidak cukup hak untuk melakukan tindakan ini.',
	'wikibase-error-ui-link-exists' => 'Anda tidak boleh membuat pautan ke halaman ini kerana satu lagi perkara sudah berpaut dengannya.',
	'wikibase-error-ui-session-failure' => 'Sesi anda sudah berakhir. Sila log masuk semula.',
	'wikibase-error-ui-edit-conflict' => 'Terdapat percanggahan suntingan. Sila muat semula dan simpan semula.',
	'wikibase-replicationnote' => 'Sila ambil perhatian bahawa masa beberapa minit mungkin perlu diambil sehingga semua perubahan kelihatan di semua wiki',
	'wikibase-sitelinks-wikipedia' => 'Halaman Wikipedia yang berpaut dengan perkara ini',
	'wikibase-sitelinks-sitename-columnheading' => 'Bahasa',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Halaman terpaut',
	'wikibase-tooltip-error-details' => 'Butiran',
	'wikibase-validator-bad-type' => '$2, bukan $1',
	'wikibase-validator-too-long' => 'Mesti tidak melebihi $1 aksara',
	'wikibase-validator-too-short' => 'Mesti sekurang-kurangnya $1 aksara',
	'wikibase-validator-malformed-value' => 'Input cacat: $1',
	'wikibase-validator-bad-entity-id' => 'ID cacat: $1',
	'wikibase-validator-bad-entity-type' => 'Jenis entiti $1 tidak dijangka',
	'wikibase-validator-no-such-entity' => '$1 tidak dijumpai',
	'wikibase-validator-no-such-property' => 'Sifat $1 tidak dijumpai',
	'wikibase-validator-bad-value' => 'Nilai tak sah: $1',
	'wikibase-validator-bad-value-type' => 'Jenis nilai $1 tidak elok, $2 diharapkan',
	'datatypes-type-wikibase-item' => 'Perkara',
	'datatypes-type-commonsMedia' => 'Fail media Commons',
	'version-wikibase' => 'Wikibase',
);

/** Neapolitan (Napulitano)
 * @author C.R.
 * @author Chelin
 */
$messages['nap'] = array(
	'wikibase-timedetails-time' => 'Tiempo',
	'wikibase-timedetails-isotime' => 'Ora e data ISO',
	'wikibase-timedetails-timezone' => 'Fuso orario',
	'wikibase-timedetails-calendar' => 'Calannario',
	'wikibase-timedetails-precision' => 'Precisiona',
	'wikibase-timedetails-before' => 'Apprimma',
	'wikibase-timedetails-after' => 'Aroppo',
	'wikibase-globedetails-longitude' => 'Longitudine',
	'wikibase-globedetails-latitude' => 'Latitudine',
	'wikibase-globedetails-precision' => 'Precisiona',
	'wikibase-globedetails-globe' => 'Globbo',
);

/** Norwegian Bokmål (norsk bokmål)
 * @author Danmichaelo
 * @author Event
 * @author Jeblad
 */
$messages['nb'] = array(
	'wikibase-lib-desc' => 'Felles funksjonalitet for Wikibase, det strukturerte datalageret',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'egenskap',
	'wikibase-entity-query' => 'spørring',
	'wikibase-deletedentity-item' => 'Slettet element',
	'wikibase-deletedentity-property' => 'Slettet egenskap',
	'wikibase-deletedentity-query' => 'Slettet spørring',
	'wikibase-diffview-reference' => 'referanse',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-rank-preferred' => 'Foretrukket rang',
	'wikibase-diffview-rank-normal' => 'Normal rang',
	'wikibase-diffview-rank-deprecated' => 'Foreldet rang',
	'wikibase-diffview-qualifier' => 'kvalifikator',
	'wikibase-diffview-label' => 'etikett',
	'wikibase-diffview-alias' => 'kallenavn',
	'wikibase-diffview-description' => 'beskrivelse',
	'wikibase-diffview-link' => 'lenker',
	'wikibase-error-unexpected' => 'Det oppsto en uventet feil.',
	'wikibase-error-save-generic' => 'Endringene dine kunne ikke lagres på grunn av en feil.',
	'wikibase-error-remove-generic' => 'En feil oppstod under forsøket på å fjerne oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres.',
	'wikibase-error-save-connection' => 'En feil oppstod under forsøket på å lagre oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres. Sjekk din tilknytting til internett.',
	'wikibase-error-remove-connection' => 'En feil oppstod under forsøket på å fjerne oppføringen, og på grunn av dette så kunne ikke endringen gjennomføres. Sjekk din tilknytting til internett.',
	'wikibase-error-save-timeout' => 'Vi har tekniske problemer, og på grunn av dette så kan vi ikke gjennomføre lagring av oppføringen.',
	'wikibase-error-remove-timeout' => 'Vi har tekniske problemer, og på grunn av dette så kan vi ikke gjennomføre fjerning av oppføringen.',
	'wikibase-error-autocomplete-connection' => 'Kunne ikke spørre mot nettstedets API. Prøv igjen senere.',
	'wikibase-error-autocomplete-response' => 'Tjeneren svarte: $1',
	'wikibase-error-ui-client-error' => 'Kontakten med klientsiden feilet. Forsøk på nytt senere.',
	'wikibase-error-ui-no-external-page' => 'Den angitte artikkelen ble ikke funnet på det tilhørende nettstedet.',
	'wikibase-error-ui-cant-edit' => 'Du har ikke lov til å utføre denne handlingen.',
	'wikibase-error-ui-no-permissions' => 'Du har ikke tilstrekkelige rettigheter til å utføre denne handlingen.',
	'wikibase-error-ui-link-exists' => 'Du kan ikke lenke til denne siden fordi et annet element lenker allerede til den.',
	'wikibase-error-ui-session-failure' => 'Din arbeidsøkt er avsluttet, logg inn på nytt om du vil fortsette.',
	'wikibase-error-ui-edit-conflict' => 'Det er påvist en redigeringskonflikt. Kopier dine endringer, last siden på nytt, endre og lagre på nytt.',
	'wikibase-quantitydetails-amount' => 'Mengde',
	'wikibase-quantitydetails-upperbound' => 'Øvre grense',
	'wikibase-quantitydetails-lowerbound' => 'Nedre grense',
	'wikibase-quantitydetails-unit' => 'Enhet',
	'wikibase-replicationnote' => 'Vær oppmerksom på at det kan ta flere minutter før endringene er synlig på alle wikier',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia-sider lenket til dette elementet',
	'wikibase-sitelinks-sitename-columnheading' => 'Språk',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Nettsted',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Lenket side',
	'wikibase-tooltip-error-details' => 'Detaljer',
	'wikibase-undeserializable-value' => 'Verdien er ugyldig og kan ikke vises.',
	'wikibase-validator-bad-type' => '$2 istedenfor $1',
	'wikibase-validator-too-long' => 'Kan ikke være mer enn {{PLURAL:$1|en karakter|$1 karakterer}} lang',
	'wikibase-validator-too-short' => 'Må være minst {{PLURAL:$1|en karakter|$1 karakterer}} lang',
	'wikibase-validator-too-high' => 'Utenfor intervallet, kan ikke være høyere enn $1',
	'wikibase-validator-too-low' => 'Utenfor intervallet, kan ikke være lavere enn $1',
	'wikibase-validator-malformed-value' => 'Feilformatert inndata: $1',
	'wikibase-validator-bad-entity-id' => 'Feilformatert ID: $1',
	'wikibase-validator-bad-entity-type' => 'Uventet entitetstype $1',
	'wikibase-validator-no-such-entity' => '$1 ble ikke funnet',
	'wikibase-validator-no-such-property' => 'Egenskap $1 ikke funnet',
	'wikibase-validator-bad-value' => 'Ulovlig verdi: $1',
	'wikibase-validator-bad-value-type' => 'Feil verditype $1, forventet $2',
	'wikibase-validator-bad-url' => 'Feilformatert URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Ustøttet URL-skjema: $1',
	'wikibase-validator-bad-http-url' => 'Feilformatert HTTP-URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Ugyldig mailto-URL: $1',
	'wikibase-validator-unknown-unit' => 'Ukjent enhet: $1',
	'datatypes-type-wikibase-item' => 'element',
	'datatypes-type-commonsMedia' => 'mediafil fra Commons',
	'version-wikibase' => 'Wikibase',
);

/** Low Saxon (Netherlands) (Nedersaksies)
 * @author Servien
 */
$messages['nds-nl'] = array(
	'wikibase-replicationnote' => "t Kan n paor minuten duren veurdat de wiezigingen op alle wiki's zichtbaor bin.",
	'wikibase-sitelinks-sitename-columnheading' => 'Taal',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-tooltip-error-details' => 'Details',
	'version-wikibase' => 'Wikibase',
);

/** Dutch (Nederlands)
 * @author Basvb
 * @author SPQRobin
 * @author Saruman
 * @author Siebrand
 */
$messages['nl'] = array(
	'wikibase-lib-desc' => 'Bevat gemeenschappelijke functies voor de uitbreidingen Wikibase en Wikibase Client',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'eigenschap',
	'wikibase-entity-query' => 'zoekopdracht',
	'wikibase-deletedentity-item' => 'Verwijderd item',
	'wikibase-deletedentity-property' => 'Verwijderde eigenschap',
	'wikibase-deletedentity-query' => 'Verwijderde zoekopdracht',
	'wikibase-diffview-reference' => 'referentie',
	'wikibase-diffview-rank' => 'positie',
	'wikibase-diffview-rank-preferred' => 'Voorkeursrang',
	'wikibase-diffview-rank-normal' => 'Normale rang',
	'wikibase-diffview-rank-deprecated' => 'Afgekeurde rang',
	'wikibase-diffview-qualifier' => 'kwalificatie',
	'wikibase-diffview-label' => 'label',
	'wikibase-diffview-alias' => 'aliassen',
	'wikibase-diffview-description' => 'beschrijving',
	'wikibase-diffview-link' => 'koppelingen',
	'wikibase-error-unexpected' => 'Er is een onverwachte fout opgetreden.',
	'wikibase-error-save-generic' => 'Er is een fout opgetreden tijdens het opslaan van uw wijzigingen. Uw wijzigingen konden niet worden opgeslagen.',
	'wikibase-error-remove-generic' => 'Er is een fout opgetreden tijdens het verwijderen. Uw wijzigingen konden niet worden opgeslagen.',
	'wikibase-error-save-connection' => 'Er is een fout in de verbinding opgetreden tijdens het opslaan. Uw wijzigingen konden niet worden opgeslagen. Controleer uw internetverbinding.',
	'wikibase-error-remove-connection' => 'Er is een fout in de verbinding opgetreden tijdens het verwijderen. Uw wijzigingen konden niet worden opgeslagen. Controleer uw internetverbinding.',
	'wikibase-error-save-timeout' => 'Wij ondervinden technische problemen. Uw wijzigingen kunnen niet worden opgeslagen.',
	'wikibase-error-remove-timeout' => 'ij ondervinden technische problemen. Uw wijzigingen kunnen niet worden opgeslagen.',
	'wikibase-error-autocomplete-connection' => 'Het was niet mogelijk de site-API te bereiken. Probeer het later opnieuw.',
	'wikibase-error-autocomplete-response' => 'Antwoord van server: $1',
	'wikibase-error-ui-client-error' => 'De verbinding met de externe pagina kon niet gemaakt worden. Probeer het later nog eens.',
	'wikibase-error-ui-no-external-page' => 'De opgegeven pagina kon niet worden gevonden op de overeenkomende site.',
	'wikibase-error-ui-cant-edit' => 'U mag deze handeling niet uitvoeren.',
	'wikibase-error-ui-no-permissions' => 'U hebt geen rechten om deze handeling uit te voeren.',
	'wikibase-error-ui-link-exists' => 'U kunt geen koppeling naar deze pagina maken omdat een ander item er al aan gekoppeld is.',
	'wikibase-error-ui-session-failure' => 'Uw sessie is verlopen. Meld u opnieuw aan.',
	'wikibase-error-ui-edit-conflict' => 'Er is een bewerkingsconflict opgetreden. Laad de pagina opnieuw en sla uw wijzigingen opnieuw op.',
	'wikibase-quantitydetails-amount' => 'Hoeveelheid',
	'wikibase-quantitydetails-upperbound' => 'Bovengrens',
	'wikibase-quantitydetails-lowerbound' => 'Ondergrens',
	'wikibase-quantitydetails-unit' => 'Eenheid',
	'wikibase-timedetails-time' => 'Tijd',
	'wikibase-timedetails-timezone' => 'Tijdzone',
	'wikibase-timedetails-calendar' => 'Kalender',
	'wikibase-replicationnote' => "Het kan een aantal minuten duren voor alle wijzigingen op alle wiki's zichtbaar zijn",
	'wikibase-sitelinks-wikipedia' => "Pagina's op Wikipedia die gekoppeld zijn aan dit item",
	'wikibase-sitelinks-sitename-columnheading' => 'Taal',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site',
	'wikibase-sitelinks-siteid-columnheading' => 'Code',
	'wikibase-sitelinks-link-columnheading' => 'Gekoppelde pagina',
	'wikibase-tooltip-error-details' => 'Details',
	'wikibase-undeserializable-value' => 'De waarde is ongeldig en kan niet weergegeven worden.',
	'wikibase-validator-bad-type' => '$2 in plaats van $1',
	'wikibase-validator-too-long' => 'Moet niet meer dan {{PLURAL:$1|één teken $1 tekens}} lang zijn',
	'wikibase-validator-too-short' => 'Moet tenminste {{PLURAL:$1|één teken $1 tekens}} lang zijn',
	'wikibase-validator-too-high' => 'Buiten het bereik, moet niet hoger zijn dan $1',
	'wikibase-validator-too-low' => 'Buiten het bereik, moet niet lager zijn dan $1',
	'wikibase-validator-malformed-value' => 'Ongeldige invoer: $1',
	'wikibase-validator-bad-entity-id' => 'Ongeldig ID: $1',
	'wikibase-validator-bad-entity-type' => 'Onverwacht entiteitstype $1',
	'wikibase-validator-no-such-entity' => '$1 is niet gevonden',
	'wikibase-validator-no-such-property' => 'Eigenschap $1 niet gevonden',
	'wikibase-validator-bad-value' => 'Ongeldige waarde: $1',
	'wikibase-validator-bad-value-type' => 'Onjuist waardetype $1, verwacht was $2',
	'wikibase-validator-bad-url' => 'Ongeldige URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Niet-ondersteund URL-schema: $1',
	'wikibase-validator-bad-http-url' => 'Ongeldige HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Ongeldige mailto-URL: $1',
	'wikibase-validator-unknown-unit' => 'Onbekende eenheid: $1',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Mediabestand van Commons',
	'version-wikibase' => 'Wikibase',
);

/** Norwegian Nynorsk (norsk nynorsk)
 * @author Jeblad
 * @author Njardarlogar
 * @author Roarjo
 */
$messages['nn'] = array(
	'wikibase-lib-desc' => 'Har felles funksjonalitet for Wikibase- og Wikibase Client-utvidingane',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'eigenskap',
	'wikibase-entity-query' => 'spørjing',
	'wikibase-deletedentity-item' => 'Sletta element',
	'wikibase-deletedentity-property' => 'Sletta eigenskap',
	'wikibase-deletedentity-query' => 'Sletta spørjing',
	'wikibase-diffview-reference' => 'kjelde',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-qualifier' => 'kvalifikator',
	'wikibase-diffview-label' => 'merkelapp',
	'wikibase-diffview-alias' => 'tilleggsnamn',
	'wikibase-diffview-description' => 'skildring',
	'wikibase-diffview-link' => 'lenkjer',
	'wikibase-error-unexpected' => 'Det oppstod ein uventa feil.',
	'wikibase-error-save-generic' => 'Ein feil oppstod under lagring, og grunna dette kunne ikkje endringande dine fullførast.',
	'wikibase-error-remove-generic' => 'Ein feil oppstod under fjerning, og grunna dette kunne ikkje endringande dine fullførast.',
	'wikibase-error-save-connection' => 'Ein koplingsfeil oppstod under lagring, og grunna dette kunne ikkje endringande dine fullførast. Undersøk Internett-tilkoplinga di.',
	'wikibase-error-remove-connection' => 'Ein koplingsfeil oppstod under fjerning, og grunna dette kunne ikkje endringande dine fullførast. Undersøk Internett-tilkoplinga di.',
	'wikibase-error-save-timeout' => 'Me har tekniske vanskar, og grunna dette kunne ikkje lagringa di fullførast.',
	'wikibase-error-remove-timeout' => 'Me har tekniske vanskar, og grunna dette kunne ikkje fjerninga di fullførast.',
	'wikibase-error-autocomplete-connection' => 'Kunne ikkje spørja API-en til nettstaden. Freist om att seinare.',
	'wikibase-error-autocomplete-response' => 'Tenaren svarte: $1',
	'wikibase-error-ui-client-error' => 'Kontakten med klientsida feila. Freista på nytt seinare.',
	'wikibase-error-ui-no-external-page' => 'Den oppgjevne artikkelen vart ikkje funnen på den tilhøyrande nettstaden.',
	'wikibase-error-ui-cant-edit' => 'Du har ikkje lov til å utføre denne handlinga.',
	'wikibase-error-ui-no-permissions' => 'Du har ikkje tilstrekkelege rettar til å utføre denne handlinga.',
	'wikibase-error-ui-link-exists' => 'Du kan ikkje lenkja til denne sida av di eit anna element alt lenkjer til henne.',
	'wikibase-error-ui-session-failure' => 'Arbeidsøkta di er utgjengen. Du lyt logga inn på nytt.',
	'wikibase-error-ui-edit-conflict' => 'Det er ein endringskonflikt på gang. Lasta sida på nytt og lagra på nytt.',
	'wikibase-timedetails-before' => 'Før',
	'wikibase-timedetails-after' => 'Etter',
	'wikibase-replicationnote' => 'Ver merksam på at det kan ta fleire minutt før endringane vert synlege på alle wikiane.',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia-sider knytte til elementet',
	'wikibase-sitelinks-sitename-columnheading' => 'Språk',
	'wikibase-sitelinks-siteid-columnheading' => 'Kode',
	'wikibase-sitelinks-link-columnheading' => 'Lenkja side',
	'wikibase-tooltip-error-details' => 'Detaljar',
	'wikibase-validator-bad-type' => '$2 i staden for $1',
	'wikibase-validator-bad-entity-type' => 'Uventa einingstype $1',
	'wikibase-validator-no-such-entity' => 'fann ikkje $1',
	'wikibase-validator-bad-value' => 'Ulovleg verdi: $1',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Mediefil frå Commons',
	'version-wikibase' => 'Wikibase',
);

/** Polish (polski)
 * @author BeginaFelicysym
 * @author Chrumps
 * @author Kpjas
 * @author Lazowik
 * @author Maćko
 * @author Rzuwig
 * @author WTM
 */
$messages['pl'] = array(
	'wikibase-lib-desc' => 'Zawiera wspólne funkcje dla rozszerzeń Wikibase i Wikibase Client',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'właściwość',
	'wikibase-entity-query' => 'zapytanie',
	'wikibase-deletedentity-item' => 'Usunięty element',
	'wikibase-deletedentity-property' => 'Usunięta właściwość',
	'wikibase-diffview-reference' => 'przypis',
	'wikibase-diffview-rank' => 'ranga',
	'wikibase-diffview-rank-preferred' => 'Ranga preferowana',
	'wikibase-diffview-rank-normal' => 'Ranga zwykła',
	'wikibase-diffview-rank-deprecated' => 'Ranga nieaktualna',
	'wikibase-diffview-qualifier' => 'kwalifikator',
	'wikibase-diffview-label' => 'etykieta',
	'wikibase-diffview-alias' => 'aliasy',
	'wikibase-diffview-description' => 'opis',
	'wikibase-diffview-link' => 'linki',
	'wikibase-error-unexpected' => 'Wystąpił nieoczekiwany błąd.',
	'wikibase-error-save-generic' => 'Wystąpił błąd podczas próby zapisu i z tego powodu zmiany nie zostały zapisane.',
	'wikibase-error-remove-generic' => 'Wystąpił błąd podczas próby usunięcia i z tego powodu zmiany nie zostały zapisane.',
	'wikibase-error-save-connection' => 'Wystąpił błąd połączenia podczas próby zapisu i z tego powodu zmiany nie zostały zapisane. Sprawdź swoje połączenie z Internetem.',
	'wikibase-error-remove-connection' => 'Wystąpił błąd połączenia podczas próby usunięcia i z tego powodu zmiany nie zostały zapisane. Sprawdź swoje połączenie z Internetem.',
	'wikibase-error-save-timeout' => 'Mamy problemy techniczne i z tego powodu próba zapisu nie powiodła się.',
	'wikibase-error-remove-timeout' => 'Mamy problemy techniczne i z tego powodu próba usunięcia nie powiodła się.',
	'wikibase-error-autocomplete-connection' => 'Nie można połączyć się z API witryny. Spróbuj ponownie później.',
	'wikibase-error-autocomplete-response' => 'Serwer odpowiedział: $1',
	'wikibase-error-ui-client-error' => 'Połączenie z klientem nie powiodło się. Spróbuj ponownie później.',
	'wikibase-error-ui-no-external-page' => 'Nie można odnaleźć artykułu na odpowiadającej witrynie.',
	'wikibase-error-ui-cant-edit' => 'Nie możesz wykonać tego działania.',
	'wikibase-error-ui-no-permissions' => 'Nie masz wystarczających uprawnień aby wykonać to działanie.',
	'wikibase-error-ui-link-exists' => 'Nie możesz podać tej strony, gdyż inny wpis już na nią wskazuje.',
	'wikibase-error-ui-session-failure' => 'Twoja sesja wygasła. Zaloguj się ponownie.',
	'wikibase-error-ui-edit-conflict' => 'Wystąpił konflikt edycji. Załaduj raz jeszcze i zapisz.',
	'wikibase-quantitydetails-unit' => 'Jednostka',
	'wikibase-replicationnote' => 'Zwróć uwagę, że może upłynąć kilka minut, zanim zmiany staną się widoczne na wszystkich wiki',
	'wikibase-sitelinks-wikipedia' => 'Strony Wikipedii powiązane z tym elementem',
	'wikibase-sitelinks-sitename-columnheading' => 'Język',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Powiązana strona',
	'wikibase-tooltip-error-details' => 'Szczegóły',
	'wikibase-undeserializable-value' => 'Wartość jest nieprawidłowa i nie może zostać wyświetlona.',
	'wikibase-validator-bad-type' => '$2, powinno być: $1',
	'wikibase-validator-too-long' => 'Nie może być dłuższe niż {{PLURAL:$1| jeden znak|$1 znaki|$1 znaków}}',
	'wikibase-validator-too-short' => 'Musi zawierać co najmniej {{PLURAL:$1| jeden znak|$1 znaki|$1 znaków}}.',
	'wikibase-validator-no-such-entity' => '$1 nie znaleziono',
	'wikibase-validator-bad-value' => 'Niepoprawna wartość: $1',
	'wikibase-validator-bad-url' => 'Nieprawidłowy URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Nieobsługiwany schemat adresu URL: $1',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Plik multimedialny na Commons',
	'version-wikibase' => 'Wikibase',
);

/** Piedmontese (Piemontèis)
 * @author Borichèt
 * @author Dragonòt
 * @author පසිඳු කාවින්ද
 */
$messages['pms'] = array(
	'wikibase-lib-desc' => "A conten dle funsionalità comun-e a j'estension Wikibase e Wikibase Client",
	'wikibase-entity-item' => 'Element',
	'wikibase-entity-property' => 'propietà',
	'wikibase-entity-query' => 'anterogassion',
	'wikibase-deletedentity-item' => 'Element ëscancelà',
	'wikibase-deletedentity-property' => 'Propietà scancelà',
	'wikibase-deletedentity-query' => 'Arcesta scancelà',
	'wikibase-diffview-reference' => 'rëspondensa',
	'wikibase-diffview-rank' => 'rangh',
	'wikibase-diffview-rank-preferred' => 'Rangh preferì',
	'wikibase-diffview-rank-normal' => 'Rangh normal',
	'wikibase-diffview-rank-deprecated' => 'Rangh frust',
	'wikibase-diffview-qualifier' => 'qualificator',
	'wikibase-diffview-label' => 'tichëtta',
	'wikibase-diffview-alias' => 'sobrichet',
	'wikibase-diffview-description' => 'descrission',
	'wikibase-diffview-link' => 'liure',
	'wikibase-error-unexpected' => "A l'é ancapitaje n'eror inaspetà.",
	'wikibase-error-save-generic' => "A l'é capitaje n'eror an provand a argistré e, për sòn, soe modìfiche a peulo nen esse completà.",
	'wikibase-error-remove-generic' => "A l'é capitaje n'eror an provand a scancelé e, për sòn, soe modìfiche a l'han nen podù esse completà.",
	'wikibase-error-save-connection' => "A l'é capitaje n'eror ëd conession an provand a argistré, e për sòn soe modìfiche a l'han pa podù esse completà. Për piasì, ch'a contròla soa conession sla Ragnà.",
	'wikibase-error-remove-connection' => "A l'é capitaje n'eror ëd conession an provand a scancelé, e për sòn soe modìfiche a l'han nen podù esse completà. Për piasì, ch'a contròla soa conession sla Ragnà.",
	'wikibase-error-save-timeout' => "I rancontroma dle dificoltà técniche, e për sòn soa arcesta d'argistrassion a peul nen esse completà.",
	'wikibase-error-remove-timeout' => "I rancontroma dle dificoltà técniche, e për sòn soa scancelassion a l'ha nen podù esse completà.",
	'wikibase-error-autocomplete-connection' => "Impossìbil anteroghé l'API dël sit. Për piasì, ch'a preuva torna pi tard.",
	'wikibase-error-autocomplete-response' => "Ël servent a l'ha rëspondù: $1",
	'wikibase-error-ui-client-error' => "La conession a la pàgina dël client a l'ha falì. Për piasì, ch'a preuva torna pi tard.",
	'wikibase-error-ui-no-external-page' => 'La vos specificà a peul pa esse trovà dzor ël sit corispondent.',
	'wikibase-error-ui-cant-edit' => "It peule pa fé st'assion-sì.",
	'wikibase-error-ui-no-permissions' => "A l'ha pa a basta 'd drit për fé st'assion.",
	'wikibase-error-ui-link-exists' => "A peul pa buté na liura a sta pàgina përchè n'àutr element a l'é già colegà.",
	'wikibase-error-ui-session-failure' => "Soa session a l'é finìa. Për piasì, ch'a intra torna ant ël sistema.",
	'wikibase-error-ui-edit-conflict' => "A-i é un conflit ëd modìfiche. Për piasì, ch'a caria e ch'a salva torna.",
	'wikibase-quantitydetails-amount' => 'Total',
	'wikibase-quantitydetails-upperbound' => 'Lìmit superior',
	'wikibase-quantitydetails-lowerbound' => 'Lìmit anferior',
	'wikibase-quantitydetails-unit' => 'Unità',
	'wikibase-replicationnote' => "Ch'a ten-a da ment ch'a peulò vorèje vàire minute prima che le modìfiche a sio visìbil su tute le wiki.",
	'wikibase-sitelinks-wikipedia' => 'Pàgine ëd Wikipedia lijà a cost element',
	'wikibase-sitelinks-sitename-columnheading' => 'Lenga',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sit',
	'wikibase-sitelinks-siteid-columnheading' => 'Còdes',
	'wikibase-sitelinks-link-columnheading' => 'Pàgina colegà',
	'wikibase-tooltip-error-details' => 'Detaj',
	'wikibase-undeserializable-value' => "Ël valor a l'é nen bon e a peul nen esse mostrà.",
	'wikibase-validator-bad-type' => '$2 nopà che $1',
	'wikibase-validator-too-long' => 'A dev nen esse pi longh che {{PLURAL:$1|un caràter|$1 caràter}}',
	'wikibase-validator-too-short' => 'A dev esse longh almanch {{PLURAL:$1|un|$1}} caràter',
	'wikibase-validator-too-high' => 'Fòra dij lìmit, a dev esse nen pi grand che $1',
	'wikibase-validator-too-low' => 'Fòra dij lìmit, a dev esse nen pi cit che $1',
	'wikibase-validator-malformed-value' => 'Imission ant un formà nen bon: $1',
	'wikibase-validator-bad-entity-id' => 'ID ant un formà nen bon: $1',
	'wikibase-validator-bad-entity-type' => "Sòrt d'entità $1 nen ëspetà",
	'wikibase-validator-no-such-entity' => '$1 nen trovà',
	'wikibase-validator-no-such-property' => 'Propietà $1 nen trovà',
	'wikibase-validator-bad-value' => 'Valor vietà: $1',
	'wikibase-validator-bad-value-type' => 'Sòrt ëd valor nen bon-a $1, spetà $2',
	'wikibase-validator-bad-url' => "Adrëssa dl'Aragnà malformà: $1",
	'wikibase-validator-bad-url-scheme' => "Schema d'adrëssa dl'Aragnà nen mantnù: $1",
	'wikibase-validator-bad-http-url' => 'Liura HTTP malformà: $1',
	'wikibase-validator-bad-mailto-url' => 'Liura mailto malformà: $1',
	'wikibase-validator-unknown-unit' => 'Unità sconossùa: $1',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Archivi ëd mojen ëd Commons',
	'version-wikibase' => 'Wikibase',
);

/** Pashto (پښتو)
 * @author Ahmed-Najib-Biabani-Ibrahimkhel
 */
$messages['ps'] = array(
	'wikibase-sitelinks-sitename-columnheading' => 'ژبه',
	'wikibase-tooltip-error-details' => 'تفصيلات',
);

/** Portuguese (português)
 * @author Hamilton Abreu
 * @author Helder.wiki
 * @author Imperadeiro98
 * @author Luckas
 * @author Malafaya
 * @author SandroHc
 * @author Vitorvicentevalente
 * @author Waldir
 */
$messages['pt'] = array(
	'wikibase-lib-desc' => 'Contém funcionalidades comuns para as extensões Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'propriedade',
	'wikibase-entity-query' => 'consulta',
	'wikibase-deletedentity-item' => 'Item eliminado',
	'wikibase-deletedentity-property' => 'Propriedade eliminada',
	'wikibase-deletedentity-query' => 'Consulta eliminada',
	'wikibase-diffview-reference' => 'referência',
	'wikibase-diffview-rank' => 'posição',
	'wikibase-diffview-rank-preferred' => 'Classificação preferencial',
	'wikibase-diffview-rank-normal' => 'Classificação normal',
	'wikibase-diffview-rank-deprecated' => 'Classificação obsoleta',
	'wikibase-diffview-qualifier' => 'qualificador',
	'wikibase-diffview-label' => 'etiqueta',
	'wikibase-diffview-alias' => 'Nomes alternativos',
	'wikibase-diffview-description' => 'descrição',
	'wikibase-diffview-link' => 'ligações',
	'wikibase-error-unexpected' => 'Ocorreu um erro inesperado',
	'wikibase-error-save-generic' => 'Ocorreu um erro na gravação. Não foi possível efectuar as alterações.',
	'wikibase-error-remove-generic' => 'Ocorreu um erro na remoção. Não foi possível efectuar as alterações.',
	'wikibase-error-save-connection' => 'Ocorreu um erro de ligação ao tentar gravar e as alterações não foram efectuadas. Verifique a sua ligação à Internet, por favor.',
	'wikibase-error-remove-connection' => 'Ocorreu um erro de ligação ao tentar remover e as alterações não foram efectuadas. Verifique a sua ligação à Internet, por favor.',
	'wikibase-error-save-timeout' => 'Estamos a ter dificuldades técnicas e não foi possível concluir a gravação.',
	'wikibase-error-remove-timeout' => 'Estamos a ter dificuldades técnicas e não foi possível concluir a remoção.',
	'wikibase-error-autocomplete-connection' => 'Não foi possível consultar a API do sítio. Por favor, tente novamente mais tarde.',
	'wikibase-error-autocomplete-response' => 'O servidor respondeu: $1',
	'wikibase-error-ui-client-error' => 'Falha na conexão com a página de cliente. Por favor, tente novamente mais tarde.',
	'wikibase-error-ui-no-external-page' => 'O artigo especificado não pôde ser encontrado no respectivo sítio',
	'wikibase-error-ui-cant-edit' => 'Não está autorizado a executar esta acção.',
	'wikibase-error-ui-no-permissions' => 'Não possui os privilégios necessários para executar esta operação.',
	'wikibase-error-ui-link-exists' => 'Não pode realizar a ligação a esta página porque já existe outro item com ligação para a mesma.',
	'wikibase-error-ui-session-failure' => 'A sua sessão expirou. Entre novamente na sua conta.',
	'wikibase-error-ui-edit-conflict' => 'Conflito de edição. Por favor, recarregue a página e guarde novamente os dados.',
	'wikibase-quantitydetails-unit' => 'Unidade',
	'wikibase-timedetails-time' => 'Tempo',
	'wikibase-timedetails-timezone' => 'Fuso horário',
	'wikibase-timedetails-calendar' => 'Calendário',
	'wikibase-timedetails-precision' => 'Precisão',
	'wikibase-timedetails-before' => 'Antes de',
	'wikibase-timedetails-after' => 'Depois de',
	'wikibase-globedetails-longitude' => 'Longitude',
	'wikibase-globedetails-latitude' => 'Latitude',
	'wikibase-globedetails-precision' => 'Precisão',
	'wikibase-globedetails-globe' => 'Globo',
	'wikibase-replicationnote' => 'Por favor, saiba que pode levar vários minutos até que as mudanças são visíveis em todos as wikis.',
	'wikibase-sitelinks-wikipedia' => 'Páginas na Wikipédia com ligação a este item',
	'wikibase-sitelinks-sitename-columnheading' => 'Língua',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Sítio',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Página associada',
	'wikibase-tooltip-error-details' => 'Detalhes',
	'wikibase-undeserializable-value' => 'Este valor é inválido e não pode ser exibido.',
	'wikibase-validator-bad-type' => '$2 invés de $1',
	'wikibase-validator-no-such-entity' => '$1 não encontrado',
	'wikibase-validator-no-such-property' => 'Propriedade $1 não encontrada',
	'wikibase-validator-unknown-unit' => 'Unidade desconhecida: $1',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Ficheiro de média do Commons',
	'version-wikibase' => 'Wikibase',
);

/** Brazilian Portuguese (português do Brasil)
 * @author Cainamarques
 * @author Jaideraf
 * @author Luckas
 * @author 555
 */
$messages['pt-br'] = array(
	'wikibase-lib-desc' => 'Mantém a funcionalidade comum para as extensões Wikibase e Wikibase Client',
	'wikibase-entity-item' => 'item',
	'wikibase-entity-property' => 'propriedade',
	'wikibase-entity-query' => 'consulta',
	'wikibase-diffview-reference' => 'referência',
	'wikibase-diffview-rank' => 'rank',
	'wikibase-diffview-qualifier' => 'qualificador',
	'wikibase-diffview-description' => 'descrição',
	'wikibase-diffview-link' => 'links',
	'wikibase-error-unexpected' => 'Ocorreu um erro inesperado.',
	'wikibase-error-save-generic' => 'Ocorreu um erro ao tentar salvar e, por isso, as alterações não puderam ser completadas.',
	'wikibase-error-remove-generic' => 'Ocorreu um erro ao tentar remover e, por isso, as alterações não puderam ser completadas.',
	'wikibase-error-save-connection' => 'Ocorreu um erro de conexão ao tentar salvar e, por isso, as alterações não puderam ser completadas. Por favor, verifique sua conexão com a Internet.',
	'wikibase-error-remove-connection' => 'Ocorreu um erro de conexão ao tentar remover e, por isso, as alterações não puderam ser completadas. Por favor, verifique sua conexão com a Internet.',
	'wikibase-error-save-timeout' => 'Nós estamos tendo dificuldades técnicas e, por isso, sua ação de "salvar" pode não ter sido completada.',
	'wikibase-error-remove-timeout' => 'Nós estamos tendo dificuldades técnicas e, por isso, sua ação de "remover" pode não ter sido completada.',
	'wikibase-error-autocomplete-connection' => 'Não foi possível consultar a API do site. Por favor, tente novamente mais tarde.',
	'wikibase-error-autocomplete-response' => 'O servidor respondeu: $1',
	'wikibase-error-ui-client-error' => 'Falha na conexão para a página do cliente. Por favor, tente novamente mais tarde.',
	'wikibase-error-ui-no-external-page' => 'O artigo especificado não pôde ser encontrado no site correspondente.',
	'wikibase-error-ui-cant-edit' => 'Você não está autorizado para executar esta ação.',
	'wikibase-error-ui-no-permissions' => 'Você não tem privilégios suficientes para executar esta ação.',
	'wikibase-error-ui-link-exists' => 'Você não pode vincular a esta página porque outro item já possui link para ele.',
	'wikibase-error-ui-session-failure' => 'Sua sessão expirou. Por favor, efetue login novamente.',
	'wikibase-error-ui-edit-conflict' => 'Há um conflito de edição. Por favor, recarregue a página e salve novamente.',
	'wikibase-replicationnote' => 'Por favor, note que é possível que leve vários minutos até que as mudanças sejam visíveis em todos os wikis',
	'wikibase-sitelinks-sitename-columnheading' => 'Idioma',
	'wikibase-sitelinks-siteid-columnheading' => 'Código',
	'wikibase-sitelinks-link-columnheading' => 'Página ligada',
	'wikibase-tooltip-error-details' => 'Detalhes',
	'datatypes-type-wikibase-item' => 'Item',
	'datatypes-type-commonsMedia' => 'Arquivo de mídia do Commons',
);

/** Quechua (Runa Simi)
 * @author AlimanRuna
 */
$messages['qu'] = array(
	'wikibase-replicationnote' => 'Ama qunqaychu, huk minutukunam unayanqaman hukchasqaykikuna tukuy wikikunapi rikunalla kanankama.',
	'wikibase-sitelinks-sitename-columnheading' => 'Rimay',
);

/** Romanian (română)
 * @author Minisarm
 * @author Stelistcristi
 */
$messages['ro'] = array(
	'wikibase-lib-desc' => 'Grupează funcționalități comune pentru extensiile Wikibase și Client Wikibase',
	'wikibase-entity-item' => 'element',
	'wikibase-entity-property' => 'proprietate',
	'wikibase-entity-query' => 'interogare',
	'wikibase-deletedentity-item' => 'Element șters',
	'wikibase-deletedentity-property' => 'Proprietate ștearsă',
	'wikibase-deletedentity-query' => 'Interogare ștearsă',
	'wikibase-diffview-reference' => 'referință',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-rank-preferred' => 'Rang preferat',
	'wikibase-diffview-rank-normal' => 'Rang normal',
	'wikibase-diffview-rank-deprecated' => 'Rang perimat',
	'wikibase-diffview-qualifier' => 'calificativ',
	'wikibase-diffview-label' => 'etichetă',
	'wikibase-diffview-alias' => 'aliasuri',
	'wikibase-diffview-description' => 'descriere',
	'wikibase-diffview-link' => 'legături',
	'wikibase-error-unexpected' => 'A apărut o eroare neașteptată.',
	'wikibase-error-save-generic' => 'A intervenit o eroare în timpul salvării și din această cauză modificările dumneavoastră nu au putut fi finalizate.',
	'wikibase-error-remove-generic' => 'A intervenit o eroare în timpul eliminării și din această cauză modificările dumneavoastră nu au putut fi finalizate.',
	'wikibase-error-autocomplete-connection' => 'Nu s-a putut interoga API-ul site-ului. Reîncercați mai târziu.',
	'wikibase-error-autocomplete-response' => 'Serverul a răspuns: $1',
	'wikibase-error-ui-client-error' => 'Conexiunea cu pagina clientului a eșuat. Reîncercați mai târziu.',
	'wikibase-error-ui-no-external-page' => 'Articolul specificat nu a putut fi găsit pe site-ul respectiv.',
	'wikibase-error-ui-cant-edit' => 'Nu vă este permisă efectuarea acestei acțiuni.',
	'wikibase-error-ui-no-permissions' => 'Nu aveți suficiente drepturi să efectuați această acțiune.',
	'wikibase-error-ui-session-failure' => 'Sesiunea dumneavoastră a expirat. Vă rugăm să vă reautentificați.',
	'wikibase-error-ui-edit-conflict' => 'Există un conflict de modificare. Reîncărcați și salvați din nou.',
	'wikibase-quantitydetails-amount' => 'Cantitate',
	'wikibase-quantitydetails-upperbound' => 'Limită superioară',
	'wikibase-quantitydetails-lowerbound' => 'Limită inferioară',
	'wikibase-quantitydetails-unit' => 'Unitate',
	'wikibase-replicationnote' => 'Rețineți că pot trece câteva minute până când modificările sunt vizibile pe toate wikiurile.',
	'wikibase-sitelinks-wikipedia' => 'Pagini de la Wikipedia care trimit către acest element',
	'wikibase-sitelinks-sitename-columnheading' => 'Limbă',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site',
	'wikibase-sitelinks-siteid-columnheading' => 'Cod',
	'wikibase-sitelinks-link-columnheading' => 'Pagină legată',
	'wikibase-tooltip-error-details' => 'Detalii',
	'wikibase-undeserializable-value' => 'Valoarea nu este validă și nu poate fi afișată.',
	'wikibase-validator-bad-type' => '$2 în loc de $1',
	'wikibase-validator-too-long' => 'Nu trebuie să depășească lungimea de {{PLURAL:$1|un caracter|$1 caractere|$1 de caractere}}',
	'wikibase-validator-too-short' => 'Trebuie să aibă o lungime de cel puțin {{PLURAL:$1|un caracter|$1 caractere|$1 de caractere}}',
	'wikibase-validator-too-high' => 'În afara intervalului; nu trebuie să depășească $1',
	'wikibase-validator-too-low' => 'În afara intervalului; nu trebuie să fie sub $1',
	'wikibase-validator-malformed-value' => 'Date de intrare incorecte: $1',
	'wikibase-validator-bad-entity-id' => 'ID incorect: $1',
	'wikibase-validator-bad-entity-type' => 'Tip de entitate $1 neașteptat',
	'wikibase-validator-no-such-entity' => '$1 negăsit',
	'wikibase-validator-no-such-property' => 'Proprietatea $1 nu a fost găsită',
	'wikibase-validator-bad-value' => 'Valoare interzisă: $1',
	'wikibase-validator-bad-value-type' => 'Tip de valoare $1 greșită; se aștepta $2',
	'wikibase-validator-bad-url' => 'URL incorect: $1',
	'wikibase-validator-bad-url-scheme' => 'Schemă de URL neacceptată: $1',
	'wikibase-validator-bad-http-url' => 'URL HTTP incorect: $1',
	'wikibase-validator-bad-mailto-url' => 'URL mailto incorect: $1',
	'wikibase-validator-unknown-unit' => 'Unitate necunoscută: $1',
	'datatypes-type-wikibase-item' => 'Element',
	'datatypes-type-commonsMedia' => 'Fișier multimedia de la Commons',
	'version-wikibase' => 'Wikibase',
);

/** tarandíne (tarandíne)
 * @author Joetaras
 */
$messages['roa-tara'] = array(
	'wikibase-lib-desc' => "Tìne le funzionalità comune pe Uicchibase e le estenziune d'u Client de UicchiBase",
	'wikibase-entity-item' => 'vôsce',
	'wikibase-entity-property' => 'probbietà',
	'wikibase-entity-query' => 'inderrogazione',
	'wikibase-deletedentity-item' => 'Vôsce scangellate',
	'wikibase-deletedentity-property' => 'Probbietà scangellate',
	'wikibase-deletedentity-query' => 'Inderrogazione scangellate',
	'wikibase-diffview-reference' => 'referimende',
	'wikibase-diffview-rank' => 'posizione',
	'wikibase-diffview-qualifier' => 'qualificatore',
	'wikibase-diffview-label' => 'etichette',
	'wikibase-diffview-alias' => 'soprannome',
	'wikibase-diffview-description' => 'descrizione',
	'wikibase-diffview-link' => 'collegaminde',
	'wikibase-error-unexpected' => "S'ha verificate 'n'errore inaspettate.",
	'wikibase-error-save-generic' => "Ha assute 'n'errore mendre ca pruvave a reggistrà e pe stu fatte, le cangiaminde tune non g'onne state combletate.",
	'wikibase-error-remove-generic' => "Ha assute 'n'errore mendre ca pruvave a scangellà e pe stu fatte, le cangiaminde tune non g'onne state combletate.",
	'wikibase-error-save-connection' => "Ha assute 'n'errore de connessione mendre ca pruvave a reggistrà e pe stu fatte le cangiaminde tune non ge sò comblete. Pe piacere condrolle 'a connessione Indernette toje.",
	'wikibase-error-remove-connection' => "Ha assute 'n'errore de connessione mendre ca pruvave a scangellà e pe stu fatte le cangiaminde tune non ge sò comblete. Pe piacere condrolle 'a connessione Indernette toje.",
	'wikibase-error-save-timeout' => 'Ste avime probbleme tecnice e pe stu fatte \'a "reggistraziona" toje pò essere ca non g\'avène combletate.',
	'wikibase-error-remove-timeout' => 'Ste avime probbleme tecnice e pe stu fatte \'a "scangellaziona" toje pò essere ca non g\'avène combletate.',
	'wikibase-error-autocomplete-connection' => "Non ge pozze 'nderrogà le API de d'u site. Pe piacere pruève cchiù tarde.",
	'wikibase-error-autocomplete-response' => "'U server ave resposte: $1",
	'wikibase-error-ui-client-error' => "'A connessione a 'a pàgene d'u cliende ha fallite. Pe piacere pruève arrete.",
	'wikibase-error-ui-no-external-page' => "'A vôsce specificate non ge pò essere acchiate sus a 'u site corrispondende.",
	'wikibase-error-ui-cant-edit' => "Non ge tìne le permesse pe combletà st'azione.",
	'wikibase-error-ui-no-permissions' => "Non ge tìne le permesse pe fà st'azione.",
	'wikibase-error-ui-link-exists' => "Non ge puè collegà a sta pàgene purcé 'n'otra vôsce già jè collegate a jedde.",
	'wikibase-error-ui-session-failure' => "'A sessiona toje ha scadute. Pe piacere tràse arrete.",
	'wikibase-error-ui-edit-conflict' => "Stè 'nu conflitte de versione. Pe piacere recareche e reggistre arrete.",
	'wikibase-quantitydetails-unit' => 'Aunità',
	'wikibase-replicationnote' => "Pe piacere te 'mbormame ca ponne passà diverse minute fine ca le cangiaminde ponne essere 'ndrucate sus a tutte le uicchi",
	'wikibase-sitelinks-wikipedia' => 'Le pàggene Uicchipèdie ca sò collegate a sta vôsce',
	'wikibase-sitelinks-sitename-columnheading' => 'Lènghe',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Site',
	'wikibase-sitelinks-siteid-columnheading' => 'Codece',
	'wikibase-sitelinks-link-columnheading' => 'Pàgene collegate',
	'wikibase-tooltip-error-details' => 'Dettaglie',
	'wikibase-validator-bad-type' => '$2 invece de $1',
	'wikibase-validator-too-long' => "Onna essere luènghe no cchiù de {{PLURAL:$1|'nu carattere|$1 carattere}}",
	'wikibase-validator-too-short' => "Onna essere luènghe almene {{PLURAL:$1|'nu carattere|$1 carattere}}",
	'wikibase-validator-malformed-value' => 'Input male formate: $1',
	'wikibase-validator-bad-entity-id' => 'ID male formate: $1',
	'wikibase-validator-bad-entity-type' => "Tipe de l'endità $1 inaspettate",
	'wikibase-validator-no-such-entity' => '$1 none acchiate',
	'wikibase-validator-no-such-property' => 'Probbieta $1 none acchiate',
	'wikibase-validator-bad-value' => 'Valore illegale: $1',
	'wikibase-validator-bad-value-type' => "Tipe de valore sbagliate $1, s'aspettave $2",
	'wikibase-validator-bad-url' => 'URL male formate: $1',
	'wikibase-validator-bad-url-scheme' => 'Scheme de URL non supportate: $1',
	'wikibase-validator-bad-http-url' => 'URL HTTP male formate: $1',
	'wikibase-validator-bad-mailto-url' => 'URL de email male formate: $1',
	'datatypes-type-wikibase-item' => 'Vôsce',
	'datatypes-type-commonsMedia' => 'File media de Commons',
	'version-wikibase' => 'Uicchibase',
);

/** Russian (русский)
 * @author Amire80
 * @author Kaganer
 * @author Lockal
 * @author Okras
 * @author Ole Yves
 * @author ShinePhantom
 */
$messages['ru'] = array(
	'wikibase-lib-desc' => 'Общие функции расширений Wikibase и Wikibase Client',
	'wikibase-entity-item' => 'элемент',
	'wikibase-entity-property' => 'свойство',
	'wikibase-entity-query' => 'запрос',
	'wikibase-deletedentity-item' => 'Удалённый элемент',
	'wikibase-deletedentity-property' => 'Удалённое свойство',
	'wikibase-deletedentity-query' => 'Удалённый запрос',
	'wikibase-diffview-reference' => 'источник',
	'wikibase-diffview-rank' => 'ранг',
	'wikibase-diffview-rank-preferred' => 'Предпочтительный ранг',
	'wikibase-diffview-rank-normal' => 'Нормальный ранг',
	'wikibase-diffview-rank-deprecated' => 'Нерекомендуемый ранг',
	'wikibase-diffview-qualifier' => 'квалификатор',
	'wikibase-diffview-label' => 'метка',
	'wikibase-diffview-alias' => 'синоним',
	'wikibase-diffview-description' => 'описание',
	'wikibase-diffview-link' => 'ссылки',
	'wikibase-error-unexpected' => 'Произошла непредвиденная ошибка.',
	'wikibase-error-save-generic' => 'Произошла ошибка при попытке выполнить сохранение, поэтому ваши изменения не могут быть завершены.',
	'wikibase-error-remove-generic' => 'Произошла ошибка при попытке выполнить удаление, поэтому ваши изменения не могут быть завершены.',
	'wikibase-error-save-connection' => 'При попытке выполнить сохранение произошла ошибка подключения, поэтому ваши изменения не могут быть завершены. Пожалуйста, проверьте своё подключение к интернету.',
	'wikibase-error-remove-connection' => 'При попытке выполнить удаление произошла ошибка подключения, поэтому ваши изменения не могут быть завершены. Пожалуйста, проверьте своё подключение к интернету.',
	'wikibase-error-save-timeout' => 'Мы испытываем технические трудности, поэтому ваше изменение не может быть завершено.',
	'wikibase-error-remove-timeout' => 'Мы переживаем технические трудности, поэтому ваше удаление не может быть завершено.',
	'wikibase-error-autocomplete-connection' => 'Не удалось запросить API сайта. Пожалуйста, повторите попытку позднее.',
	'wikibase-error-autocomplete-response' => 'Сервер ответил: $1',
	'wikibase-error-ui-client-error' => 'Сбой подключения к странице клиента. Пожалуйста, повторите попытку позднее.',
	'wikibase-error-ui-no-external-page' => 'Не удалось найти указанную статью на соответствующем сайте.',
	'wikibase-error-ui-cant-edit' => 'Вы не можете выполнить это действие.',
	'wikibase-error-ui-no-permissions' => 'У вас не хватает прав для выполнения этого действия.',
	'wikibase-error-ui-link-exists' => 'Вы не можете сослаться на эту страницу, так как другой элемент (объект) уже ссылается на неё.',
	'wikibase-error-ui-session-failure' => 'Время вашей сессии истекло. Пожалуйста, войдите в систему снова.',
	'wikibase-error-ui-edit-conflict' => 'Существует конфликт редактирования. Перезагрузите и сохраните снова.',
	'wikibase-quantitydetails-amount' => 'Сумма',
	'wikibase-quantitydetails-upperbound' => 'Верхняя граница',
	'wikibase-quantitydetails-lowerbound' => 'Нижняя граница',
	'wikibase-quantitydetails-unit' => 'Единица',
	'wikibase-timedetails-time' => 'Время',
	'wikibase-timedetails-isotime' => 'Отметка времени в ISO-формате',
	'wikibase-timedetails-timezone' => 'Часовой пояс',
	'wikibase-timedetails-calendar' => 'Календарь',
	'wikibase-timedetails-precision' => 'Точность',
	'wikibase-timedetails-before' => 'До',
	'wikibase-timedetails-after' => 'После',
	'wikibase-globedetails-longitude' => 'Долгота',
	'wikibase-globedetails-latitude' => 'Широта',
	'wikibase-globedetails-precision' => 'Точность',
	'wikibase-globedetails-globe' => 'Глобус',
	'wikibase-replicationnote' => 'Пожалуйста, обратите внимание, что может пройти несколько минут, пока изменения станут видны во всех вики-проектах',
	'wikibase-sitelinks-wikipedia' => 'Страницы Википедии, связанные с этим элементом',
	'wikibase-sitelinks-sitename-columnheading' => 'Язык',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Сайт',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Связанная страница',
	'wikibase-tooltip-error-details' => 'Подробности',
	'wikibase-undeserializable-value' => 'Значение является недопустимым и не может быть отображено.',
	'wikibase-validator-bad-type' => '$2 вместо $1',
	'wikibase-validator-too-long' => 'Длина должна быть не более чем $1 {{PLURAL:$1|символ|символов|символа}}',
	'wikibase-validator-too-short' => 'Длина должна быть не менее чем $1 {{PLURAL:$1|символ|символов|символа}}',
	'wikibase-validator-too-high' => 'Вне диапазона, должно быть не больше, чем $1',
	'wikibase-validator-too-low' => 'Вне диапазона, должно быть не меньше, чем $1',
	'wikibase-validator-malformed-value' => 'Неверно введены данные: $1',
	'wikibase-validator-bad-entity-id' => 'Неверный идентификатор: $1',
	'wikibase-validator-bad-entity-type' => 'Неверный тип сущности «$1»',
	'wikibase-validator-no-such-entity' => 'Элемент $1 не найден',
	'wikibase-validator-no-such-property' => 'Свойство «$1» не найдено',
	'wikibase-validator-bad-value' => 'Недопустимое значение: $1',
	'wikibase-validator-bad-value-type' => 'Недопустимое значение типа «$1», ожидаемый тип — «$2»',
	'wikibase-validator-bad-url' => 'Искажённый URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Неподдерживаемая схема URL: $1',
	'wikibase-validator-bad-http-url' => 'Искажённый HTTP URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Искажённый URL адреса эл. почты («mailto:»): $1',
	'wikibase-validator-unknown-unit' => 'Неизвестная единица измерения: $1',
	'datatypes-type-wikibase-item' => 'Элемент',
	'datatypes-type-commonsMedia' => 'Медиафайл на Викискладе',
	'version-wikibase' => 'Вики-база',
);

/** Sicilian (sicilianu)
 * @author Gmelfi
 */
$messages['scn'] = array(
	'wikibase-replicationnote' => 'Putìssiru siri nicissari devirsi minuti prima la li canci sunu visìbbili supra tutti li wiki',
);

/** Scots (Scots)
 * @author John Reid
 */
$messages['sco'] = array(
	'wikibase-timedetails-time' => 'Time',
	'wikibase-timedetails-isotime' => 'ISO Timestamp',
	'wikibase-timedetails-timezone' => 'Timezone',
	'wikibase-timedetails-calendar' => 'Calendair',
	'wikibase-timedetails-precision' => 'Preceesion',
	'wikibase-timedetails-before' => 'Afore',
	'wikibase-timedetails-after' => 'Efter',
	'wikibase-globedetails-longitude' => 'Langitude',
	'wikibase-globedetails-latitude' => 'Latitude',
	'wikibase-globedetails-precision' => 'Preceesion',
	'wikibase-globedetails-globe' => 'Globe',
);

/** Samogitian (žemaitėška)
 * @author Hugo.arg
 */
$messages['sgs'] = array(
	'wikibase-replicationnote' => 'Toriekėt galvuo, kū gal praētė kelets mėnotiu pakuol keitėmā pataps regėmė visūsė wiki.',
	'wikibase-sitelinks-link-columnheading' => 'Sojongts straipsnis',
);

/** Sinhala (සිංහල)
 * @author පසිඳු කාවින්ද
 */
$messages['si'] = array(
	'wikibase-entity-item' => 'අයිතමය',
	'wikibase-entity-property' => 'ගුණාංගය',
	'wikibase-entity-query' => 'ප්‍රශ්නය',
	'wikibase-error-autocomplete-response' => 'සර්වරය ප්‍රතිචාර දක්වන ලදී: $1',
	'wikibase-error-ui-client-error' => 'සේවාදායක පිටුව වෙත සම්බන්ධය අසාර්ථකයි. කරුණාකර නැවත උත්සහ කරන්න.',
	'wikibase-error-ui-cant-edit' => 'ඔබට මෙම ක්‍රියාව සිදු කිරීමට ඉඩ ලබා නොදේ.',
	'wikibase-error-ui-no-permissions' => 'ඔබට මෙම ක්‍රියාව සිදු කිරීමට ප්‍රමාණවත් තරම් හිමිකම් නොමැත.',
	'wikibase-error-ui-session-failure' => 'ඔබේ සැසිය ඉකුත් වී ඇත. කරුණාකර නැවත ප්‍රවිෂ්ට වන්න.',
	'wikibase-error-ui-edit-conflict' => 'මෙය සංස්කරණ ගැටුමකි. කරුණාකර ප්‍රතිපූර්ණය කර නැවත සුරකින්න.',
	'wikibase-sitelinks-sitename-columnheading' => 'භාෂාව',
	'wikibase-sitelinks-siteid-columnheading' => 'කේතය',
	'wikibase-sitelinks-link-columnheading' => 'සබැඳිගත ලිපිය', # Fuzzy
	'wikibase-tooltip-error-details' => 'විස්තර',
	'datatypes-type-wikibase-item' => 'අයිතමය',
	'datatypes-type-commonsMedia' => 'කොමන්ස් මාධ්‍ය ගොනුව',
);

/** Slovak (slovenčina)
 * @author Teslaton
 */
$messages['sk'] = array(
	'wikibase-replicationnote' => 'Môže trvať niekoľko minút, než sa zmeny prejavia na všetkých wiki.',
);

/** Slovenian (slovenščina)
 * @author Eleassar
 */
$messages['sl'] = array(
	'wikibase-entity-item' => 'predmet',
	'wikibase-entity-property' => 'lastnost',
	'wikibase-entity-query' => 'poizvedba',
	'wikibase-deletedentity-item' => 'izbrisan predmet',
	'wikibase-deletedentity-property' => 'izbrisana lastnost',
	'wikibase-deletedentity-query' => 'izbrisana poizvedba',
	'wikibase-diffview-reference' => 'sklic',
	'wikibase-diffview-rank' => 'mesto',
	'wikibase-diffview-qualifier' => 'označevalnik',
	'wikibase-diffview-label' => 'oznaka',
	'wikibase-diffview-alias' => 'druga imena',
	'wikibase-diffview-description' => 'opis',
	'wikibase-diffview-link' => 'povezave',
	'wikibase-error-unexpected' => 'Prišlo je do nepričakovane napake.',
	'wikibase-replicationnote' => 'Spremembe se bodo na vseh wikijih morda prikazale šele po več minutah.',
	'wikibase-sitelinks-wikipedia' => 'Strani Wikipedije, povezane na ta predmet.',
	'wikibase-sitelinks-sitename-columnheading' => 'Jezik',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Spletišče',
	'wikibase-sitelinks-siteid-columnheading' => 'Koda',
	'wikibase-sitelinks-link-columnheading' => 'Povezana stran',
	'wikibase-tooltip-error-details' => 'Podrobnosti',
	'wikibase-undeserializable-value' => 'Vrednost je neveljavna in je ni mogoče prikazati.',
	'wikibase-validator-bad-type' => '$2 namesto $1',
	'wikibase-validator-malformed-value' => 'Slabo oblikovan vnos: $1',
	'wikibase-validator-bad-entity-id' => 'Slabo oblikovan ID: $1',
	'wikibase-validator-no-such-entity' => '$1 ni mogoče najti',
	'wikibase-validator-no-such-property' => 'Lastnosti $1 ni mogoče najti',
	'wikibase-validator-bad-value' => 'Nedovoljena vrednost: $1',
	'datatypes-type-wikibase-item' => 'Predmet',
	'datatypes-type-commonsMedia' => 'Predstavnostna datoteka v Zbirki',
	'version-wikibase' => 'Wikibaza',
);

/** Albanian (shqip)
 * @author GretaDoci
 */
$messages['sq'] = array(
	'wikibase-timedetails-time' => 'Koha',
	'wikibase-timedetails-timezone' => 'Zona kohore:',
	'wikibase-timedetails-calendar' => 'Kalendari',
	'wikibase-timedetails-precision' => 'Saktësi',
	'wikibase-timedetails-before' => 'Para',
	'wikibase-timedetails-after' => 'Pas',
	'wikibase-globedetails-longitude' => 'Gjatësia gjeografike',
	'wikibase-globedetails-latitude' => 'Gjerësia gjeografike',
	'wikibase-globedetails-precision' => 'Saktësi',
	'wikibase-globedetails-globe' => 'Globit',
);

/** Serbian (Cyrillic script) (српски (ћирилица)‎)
 * @author Milicevic01
 * @author TheStefan12345
 * @author Милан Јелисавчић
 */
$messages['sr-ec'] = array(
	'wikibase-lib-desc' => 'Садржи заједничке функционалности за Викибазу и проширења за клијента Викибазе',
	'wikibase-entity-item' => 'ставка',
	'wikibase-entity-property' => 'својство',
	'wikibase-entity-query' => 'упит',
	'wikibase-deletedentity-item' => 'Обрисана ставка',
	'wikibase-deletedentity-property' => 'Обрисано својство',
	'wikibase-deletedentity-query' => 'Обрисан упит',
	'wikibase-diffview-reference' => 'референца',
	'wikibase-diffview-rank' => 'ранг',
	'wikibase-diffview-qualifier' => 'квалификатор',
	'wikibase-diffview-label' => 'назив',
	'wikibase-diffview-alias' => 'псеудоними',
	'wikibase-diffview-description' => 'опис',
	'wikibase-diffview-link' => 'везе',
	'wikibase-error-unexpected' => 'Дошло је до неочекиване грешке.',
	'wikibase-error-save-generic' => 'Дошло је до грешке приликом покушаја чувања и због тога, промене не могу бити завршене.',
	'wikibase-error-remove-generic' => 'Дошло је до грешке приликом покушаја да се изврши уклањање и због тога, промене не могу бити завршене.',
	'wikibase-error-save-timeout' => 'Тренутно имамо техничких потешкоћа и због тога ваша измена није сачувана.',
	'wikibase-error-remove-timeout' => 'Тренутно имамо техничких потешкоћа и због тога ваше брисање није извршено.',
	'wikibase-error-autocomplete-response' => 'Одговор сервера: $1',
	'wikibase-error-ui-client-error' => 'Веза са клијентском страницом није успела. Молимо вас покушајте поново касније.',
	'wikibase-error-ui-no-external-page' => 'Наведени чланак није пронађен на одговарајућем сајту.',
	'wikibase-error-ui-cant-edit' => 'Немате дозволу да извршите ову радњу.',
	'wikibase-error-ui-no-permissions' => 'Немате потребна овлашћења да извршите ову радњу.',
	'wikibase-error-ui-link-exists' => 'Не можете да повежете са овом страницом, јер друга ставка већ води до ње.',
	'wikibase-error-ui-session-failure' => 'Ваша сесија је истекла. Молимо пријавите се поново.',
	'wikibase-error-ui-edit-conflict' => 'Дошло је до сукоба измена. Молимо учитајте и сачувајте поново страну.',
	'wikibase-replicationnote' => 'Имајте на уму да је потребно и до неколико минута да промене постану видљиве на свим викијима.',
	'wikibase-sitelinks-wikipedia' => 'Списак страна повезаних са овом ставком',
	'wikibase-sitelinks-sitename-columnheading' => 'Језик',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => 'Повезани чланак',
	'wikibase-tooltip-error-details' => 'Детаљи',
	'wikibase-validator-bad-type' => '$2 уместо $1',
	'wikibase-validator-too-long' => 'Не сме бити дуже од {{PLURAL:$1|једног знака|$1 знакова}}',
	'wikibase-validator-too-short' => 'Мора бити најмање {{PLURAL:$1|један знак|$1 знакова}} дуго',
	'wikibase-validator-malformed-value' => 'Неисправан унос: $1',
	'wikibase-validator-bad-entity-id' => 'Неисправан идентификатор: $1',
	'wikibase-validator-bad-entity-type' => 'Неочекивана врста ентитета $1',
	'wikibase-validator-no-such-entity' => '$1 није пронађено',
	'wikibase-validator-no-such-property' => 'Својство $1 није пронађено',
	'wikibase-validator-bad-value' => 'Недозвољена вредност: $1',
	'datatypes-type-wikibase-item' => 'Ставка',
	'datatypes-type-commonsMedia' => 'Датотека са Оставе',
	'version-wikibase' => 'Викибаза',
);

/** Serbian (Latin script) (srpski (latinica)‎)
 * @author Milicevic01
 */
$messages['sr-el'] = array(
	'wikibase-replicationnote' => 'Imajte na umu da je potrebno i do nekoliko minuta da promene postanu vidljive na svim vikijima.',
);

/** Swedish (svenska)
 * @author Ainali
 * @author Jopparn
 * @author Lokal Profil
 * @author WikiPhoenix
 */
$messages['sv'] = array(
	'wikibase-lib-desc' => 'Håller gemensamma funktioner för Wikibase- och Wikibase Client-tilläggen.',
	'wikibase-entity-item' => 'objekt',
	'wikibase-entity-property' => 'egenskap',
	'wikibase-entity-query' => 'fråga',
	'wikibase-deletedentity-item' => 'Borttagna objekt',
	'wikibase-deletedentity-property' => 'Raderad egenskap',
	'wikibase-deletedentity-query' => 'Raderad fråga',
	'wikibase-diffview-reference' => 'referens',
	'wikibase-diffview-rank' => 'rang',
	'wikibase-diffview-rank-preferred' => 'Föredragen rang',
	'wikibase-diffview-rank-normal' => 'Normal rang',
	'wikibase-diffview-rank-deprecated' => 'Orekommenderad rang',
	'wikibase-diffview-qualifier' => 'kvalifikator',
	'wikibase-diffview-label' => 'etikett',
	'wikibase-diffview-alias' => 'alias',
	'wikibase-diffview-description' => 'beskrivning',
	'wikibase-diffview-link' => 'länkar',
	'wikibase-error-unexpected' => 'Ett oväntat fel uppstod.',
	'wikibase-error-save-generic' => 'Ett fel uppstod under sparningsförsöket och på grund av detta, kunde dina ändringar inte genomföras.',
	'wikibase-error-remove-generic' => 'Ett fel uppstod vid borttagningsförsöket och på grund av detta, kunde dina ändringar inte genomföras.',
	'wikibase-error-save-connection' => 'Ett anslutningsfel uppstod vid sparningsförsöket och på grund av detta, kunde dina ändringar inte genomföras. Kontrollera din internetanslutning.',
	'wikibase-error-remove-connection' => 'Ett anslutningsfel uppstod vid borttagningsförsöket och på grund av detta, kunde dina ändringar inte genomföras. Kontrollera din internetanslutning.',
	'wikibase-error-save-timeout' => 'Vi har tekniska problem, och därför kunde din "spara" inte genomföras.',
	'wikibase-error-remove-timeout' => 'Vi har tekniska problem, och därför kunde din "ta bort" inte genomföras.',
	'wikibase-error-autocomplete-connection' => 'Kunde inte fråga webbplatsens API. Försök igen senare.',
	'wikibase-error-autocomplete-response' => 'Servern svarade: $1',
	'wikibase-error-ui-client-error' => 'Förbindelsen med klientsidan misslyckades. Försök igen senare.',
	'wikibase-error-ui-no-external-page' => 'Den angivna artikeln kunde inte hittas på den motsvarande webbplatsen.',
	'wikibase-error-ui-cant-edit' => 'Du har inte behörighet att utföra denna åtgärd.',
	'wikibase-error-ui-no-permissions' => 'Du har inte tillräcklig behörighet för att utföra denna åtgärd.',
	'wikibase-error-ui-link-exists' => 'Du kan inte länka till den här sidan eftersom ett annat objekt redan länkar till det.',
	'wikibase-error-ui-session-failure' => 'Din session har upphört. Var god logga in igen.',
	'wikibase-error-ui-edit-conflict' => 'Det var en redigeringskonflikt. Vänligen ladda om och spara igen.',
	'wikibase-quantitydetails-amount' => 'Belopp',
	'wikibase-quantitydetails-upperbound' => 'Övre gräns',
	'wikibase-quantitydetails-lowerbound' => 'Undre gräns',
	'wikibase-quantitydetails-unit' => 'Enhet',
	'wikibase-timedetails-time' => 'Tid',
	'wikibase-timedetails-isotime' => 'ISO-tidsstämpel',
	'wikibase-timedetails-timezone' => 'Tidszon',
	'wikibase-timedetails-calendar' => 'Kalender',
	'wikibase-timedetails-precision' => 'Precision',
	'wikibase-timedetails-before' => 'Före',
	'wikibase-timedetails-after' => 'Efter',
	'wikibase-globedetails-longitude' => 'Longitud',
	'wikibase-globedetails-latitude' => 'Latitud',
	'wikibase-globedetails-precision' => 'Precision',
	'wikibase-globedetails-globe' => 'Glob',
	'wikibase-replicationnote' => 'Observera att det kan ta flera minuter tills förändringarna är synliga på alla wikier.',
	'wikibase-sitelinks-wikipedia' => 'Wikipedia-sidor som är länkade till det här objektet',
	'wikibase-sitelinks-sitename-columnheading' => 'Språk',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Webbplats',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Länkad sida',
	'wikibase-tooltip-error-details' => 'Detaljer',
	'wikibase-undeserializable-value' => 'Värdet är ogiltigt och kunde inte visas.',
	'wikibase-validator-bad-type' => '$2 istället för $1',
	'wikibase-validator-too-long' => 'Får inte vara mer än {{PLURAL:$1|ett tecken|$1 tecken}} lång',
	'wikibase-validator-too-short' => 'Måste vara minst {{PLURAL:$1|ett tecken|$1 tecken}} lång',
	'wikibase-validator-too-high' => 'Utanför intervallet, får inte vara högre än $1',
	'wikibase-validator-too-low' => 'Utanför intervallet, får inte vara lägre än $1',
	'wikibase-validator-malformed-value' => 'Felformaterad indata: $1',
	'wikibase-validator-bad-entity-id' => 'Felformaterad ID: $1',
	'wikibase-validator-bad-entity-type' => 'Oväntad entitetstyp $1',
	'wikibase-validator-no-such-entity' => '$1 hittades inte',
	'wikibase-validator-no-such-property' => 'Egenskapen $1 hittades inte',
	'wikibase-validator-bad-value' => 'Otillåtet värde: $1',
	'wikibase-validator-bad-value-type' => 'Dåligt värdetyp $1, förväntade $2',
	'wikibase-validator-bad-url' => 'Felformaterad URL: $1',
	'wikibase-validator-bad-url-scheme' => 'Ej stött URL-schema: $1',
	'wikibase-validator-bad-http-url' => 'Felformaterad HTTP-URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Felformaterad mailto-URL: $1',
	'wikibase-validator-unknown-unit' => 'Okänd enhet: $1',
	'datatypes-type-wikibase-item' => 'Objekt',
	'datatypes-type-commonsMedia' => 'Commons mediafil',
	'version-wikibase' => 'Wikibase',
);

/** Tamil (தமிழ்)
 * @author Shanmugamp7
 * @author மதனாஹரன்
 */
$messages['ta'] = array(
	'wikibase-entity-item' => 'உருப்படி',
	'wikibase-entity-query' => 'வினவல்',
	'wikibase-error-autocomplete-response' => 'வழங்கி பதிலளித்தது: $1',
	'wikibase-sitelinks-sitename-columnheading' => 'மொழி',
	'wikibase-sitelinks-siteid-columnheading' => 'குறியீடு',
	'wikibase-sitelinks-link-columnheading' => 'இணைத்த கட்டுரை', # Fuzzy
	'wikibase-tooltip-error-details' => 'விவரங்கள்',
	'datatypes-type-wikibase-item' => 'உருப்படி',
	'datatypes-type-commonsMedia' => 'பொதுவூடகக் கோப்பு',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'wikibase-diffview-alias' => 'మారుపేర్లు',
	'wikibase-diffview-description' => 'వివరణ',
	'wikibase-diffview-link' => 'లంకెలు',
	'wikibase-sitelinks-sitename-columnheading' => 'భాష',
	'wikibase-tooltip-error-details' => 'వివరాలు',
);

/** Tagalog (Tagalog)
 * @author AnakngAraw
 */
$messages['tl'] = array(
	'wikibase-lib-desc' => 'Naghahawak ng karaniwang panunungkulan para sa mga pandugtong ng Wikibase at Wikibase Client',
	'wikibase-error-save-generic' => 'Naganap ang isang kamalian habang sinusubukang isagawa ang pagsagip at dahil dito, hindi makumpleto ang mga pagbabago.',
	'wikibase-error-remove-generic' => 'Naganap ang isang kamalian habang sinusubukang isagawa ang pagtanggal at dahil dito, hindi makukumpleto ang mga binago mo.',
	'wikibase-error-save-connection' => 'Isang pagkakamali sa pagkakakabit habang sinusubukang isagawa ang pagsagip, at dahil dito hindi makukumpleto ang mga pagbabago mo. Paki siyasatin ang iyong pagkakakabit sa Internet.',
	'wikibase-error-remove-connection' => 'Isang pagkakamali sa pagkakakabit habang sinusubukang isagawa ang pagtanggal, at dahil dito hindi makukumpleto ang mga pagbabago mo. Paki siyasatin ang iyong pagkakakabit sa Internet.',
	'wikibase-error-save-timeout' => 'Nakakaranas kami ng mga suliraning teknikal, at dahil dito hindi mabuo ang iyong "pagsagip".',
	'wikibase-error-remove-timeout' => 'Nakakaranas kami ng mga suliraning teknikal, at dahil dito hindi mabuo ang iyong "pagtanggal".',
	'wikibase-error-autocomplete-connection' => 'Hindi masiyasat ang API ng Wikipedia. Paki subukan ulit mamaya.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Tumugon ang tagapaghain: $1',
	'wikibase-error-ui-client-error' => 'Nabigo ang pagkakakabit sa pahina ng kliyente. Paki subukan ulit mamaya.',
	'wikibase-error-ui-no-external-page' => 'Hindi matagpuan ang tinukoy na artikulo sa ibabaw ng kaukol na pook.',
	'wikibase-error-ui-cant-edit' => 'Hindi ka pinapayagan na maisakatuparan ang galaw na ito.',
	'wikibase-error-ui-no-permissions' => 'Wala kang sapat na mga karapatan upang maisagawa ang galaw na ito.',
	'wikibase-error-ui-link-exists' => 'Hindi ka maaaring kumawing sa pahinang ito dahil mayroon nang ibang bagay na nakakawing dito.',
	'wikibase-error-ui-session-failure' => 'Natapos na ang inilaang panahon sa iyo. Paki muling lumagda papasok.',
	'wikibase-error-ui-edit-conflict' => 'Nagkaroon ng isang pagsasalungatan sa pamamatnugot. Paki muling ikarga at sagiping muli.',
	'wikibase-sitelinks-sitename-columnheading' => 'Wika',
	'wikibase-sitelinks-siteid-columnheading' => 'Kodigo',
	'wikibase-sitelinks-link-columnheading' => 'Artikulong nakakawing', # Fuzzy
	'wikibase-tooltip-error-details' => 'Mga detalye',
);

/** Turkish (Türkçe)
 * @author Incelemeelemani
 * @author Rapsar
 */
$messages['tr'] = array(
	'wikibase-sitelinks-wikipedia' => 'Bu ögeye bağlı Vikipedi sayfaları',
	'wikibase-sitelinks-sitename-columnheading' => 'Dil',
	'wikibase-sitelinks-siteid-columnheading' => 'Kod',
	'wikibase-sitelinks-link-columnheading' => 'Bağlantılı madde', # Fuzzy
	'wikibase-validator-no-such-entity' => '$1 bulunamadı',
	'wikibase-validator-no-such-property' => '$1 özelliği bulunamadı',
);

/** Uyghur (Arabic script) (ئۇيغۇرچە)
 * @author Sahran
 */
$messages['ug-arab'] = array(
	'wikibase-entity-query' => 'سۈرۈشتۈر',
	'wikibase-sitelinks-sitename-columnheading' => 'تىل',
	'wikibase-sitelinks-siteid-columnheading' => 'كود',
	'wikibase-tooltip-error-details' => 'تەپسىلاتى',
	'datatypes-type-wikibase-item' => 'تۈر',
);

/** Ukrainian (українська)
 * @author AS
 * @author Andriykopanytsia
 * @author Base
 * @author RLuts
 * @author Steve.rusyn
 * @author SteveR
 * @author Ата
 */
$messages['uk'] = array(
	'wikibase-lib-desc' => 'Загальні функції розширень Wikibase і Wikibase Client',
	'wikibase-entity-item' => 'елемент',
	'wikibase-entity-property' => 'властивість',
	'wikibase-entity-query' => 'запит',
	'wikibase-deletedentity-item' => 'Видалений елемент',
	'wikibase-deletedentity-property' => 'Вилучена властивість',
	'wikibase-deletedentity-query' => 'Вилучений запит',
	'wikibase-diffview-reference' => 'джерело',
	'wikibase-diffview-rank' => 'ранг',
	'wikibase-diffview-rank-preferred' => 'Пріоритетний ранг',
	'wikibase-diffview-rank-normal' => 'Звичайний ранг',
	'wikibase-diffview-rank-deprecated' => 'Застарілий ранг',
	'wikibase-diffview-qualifier' => 'кваліфікатор',
	'wikibase-diffview-label' => 'позначка',
	'wikibase-diffview-alias' => 'псевдоніми',
	'wikibase-diffview-description' => 'опис',
	'wikibase-diffview-link' => 'посилання',
	'wikibase-error-unexpected' => 'Сталася невідома помилка',
	'wikibase-error-save-generic' => 'Сталася помилка під час спроби виконати збереження, через це Ваші зміни не можуть бути здійснені.',
	'wikibase-error-remove-generic' => 'Сталась помилка під час спроби виконати вилучення, через це Ваші зміни не можуть бути здійснені.',
	'wikibase-error-save-connection' => "Під час спроби здійснити виконати збереження сталась помилка з'єднання, через це Ваші зміни не можуть бути здійснені. Будь ласка, перевірте Ваше з'єднання з Інтернетом.",
	'wikibase-error-remove-connection' => 'При спробі здійснити вилучення сталась помилка підключення, тому Ваші зміни не можуть бути завершені. Будь ласка, перевірте Ваше підключення до Інтернету.',
	'wikibase-error-save-timeout' => 'Ми переживаємо технічні труднощі, і через це "Зберегти" не вдалося завершити.',
	'wikibase-error-remove-timeout' => 'Ми переживаємо технічні труднощі, і через це "видалити" не вдалося завершити.',
	'wikibase-error-autocomplete-connection' => 'Не вдалося запитати API сайту. Будь ласка, спробуйте ще раз пізніше.',
	'wikibase-error-autocomplete-response' => 'Сервер відповів: $1',
	'wikibase-error-ui-client-error' => 'Підключення до сторінки клієнта не вдалося. Будь ласка, спробуйте ще раз пізніше.',
	'wikibase-error-ui-no-external-page' => 'Не вдалося знайти вказану статтю на відповідному сайті.',
	'wikibase-error-ui-cant-edit' => 'Вам не дозволено виконати цю дію.',
	'wikibase-error-ui-no-permissions' => 'У вас не вистачає прав для виконання цієї дії.',
	'wikibase-error-ui-link-exists' => "Не вдається зв'язати цю сторінку, бо інший елемент вже містить посилання на неї.",
	'wikibase-error-ui-session-failure' => 'Ваша сесія закінчилася. Будь ласка, увійдіть в систему знову.',
	'wikibase-error-ui-edit-conflict' => 'Існує конфлікт редагування. Будь ласка, перезавантажтеся і ще раз збережіть.',
	'wikibase-quantitydetails-amount' => 'Сума',
	'wikibase-quantitydetails-upperbound' => 'Верхня межа',
	'wikibase-quantitydetails-lowerbound' => 'Нижня межа',
	'wikibase-quantitydetails-unit' => 'Одиниця',
	'wikibase-timedetails-time' => 'Час',
	'wikibase-timedetails-isotime' => 'ISO позначка часу',
	'wikibase-timedetails-timezone' => 'Часовий пояс',
	'wikibase-timedetails-calendar' => 'Календар',
	'wikibase-timedetails-precision' => 'Точність',
	'wikibase-timedetails-before' => 'До',
	'wikibase-timedetails-after' => 'Після',
	'wikibase-globedetails-longitude' => 'Довгота',
	'wikibase-globedetails-latitude' => 'Широта',
	'wikibase-globedetails-precision' => 'Точність',
	'wikibase-globedetails-globe' => 'Глобус',
	'wikibase-replicationnote' => 'Будь ласка, зверніть увагу, що це може зайняти декілька хвилин, поки зміни будуть помітні на всіх вікі.',
	'wikibase-sitelinks-wikipedia' => 'Список сторінок Вікіпедії, що посилаються на цей елемент',
	'wikibase-sitelinks-sitename-columnheading' => 'Мова',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Сайт',
	'wikibase-sitelinks-siteid-columnheading' => 'Код',
	'wikibase-sitelinks-link-columnheading' => "Пов'язана сторінка",
	'wikibase-tooltip-error-details' => 'Деталі',
	'wikibase-undeserializable-value' => 'Значення неприпустиме і не може бути відображене.',
	'wikibase-validator-bad-type' => '$2 замість $1',
	'wikibase-validator-too-long' => 'Довжина має бути не більша, ніж {{PLURAL:$1|один символ| $1 символи|$1 символів}}',
	'wikibase-validator-too-short' => 'Довжина повинна бути не менша, ніж $1 {{PLURAL:$1|символ|символи|символів}}',
	'wikibase-validator-too-high' => 'Поза діапазоном, повинно бути не вище, ніж в $1',
	'wikibase-validator-too-low' => 'Поза діапазоном, повинно бути не нижче, ніж у $1',
	'wikibase-validator-malformed-value' => 'Неправильний формат вводу:$1',
	'wikibase-validator-bad-entity-id' => 'Неправильний ідентифікатор:$1',
	'wikibase-validator-bad-entity-type' => 'Неочікуваний тип сутності $1',
	'wikibase-validator-no-such-entity' => '$1не знайдено',
	'wikibase-validator-no-such-property' => 'Властивість  $1  не знайдено',
	'wikibase-validator-bad-value' => 'Незаконне значення: $1',
	'wikibase-validator-bad-value-type' => 'Поганий тип значення $1, очікується $2',
	'wikibase-validator-bad-url' => 'Неправильна URL-адреса:$1',
	'wikibase-validator-bad-url-scheme' => 'Непідтримувана схема URL:$1',
	'wikibase-validator-bad-http-url' => 'Неправильний формат HTTP-адреси URL: $1',
	'wikibase-validator-bad-mailto-url' => 'Неправильний формат адреси одержувача:$1',
	'wikibase-validator-unknown-unit' => 'Невідомий пристрій: $1',
	'datatypes-type-wikibase-item' => 'Елемент',
	'datatypes-type-commonsMedia' => 'Медіафайл з Вікісховища',
	'version-wikibase' => 'Вікібаза',
);

/** Urdu (اردو)
 * @author පසිඳු කාවින්ද
 */
$messages['ur'] = array(
	'wikibase-tooltip-error-details' => 'تفصیلات',
);

/** Uzbek (oʻzbekcha)
 * @author Sociologist
 */
$messages['uz'] = array(
	'wikibase-replicationnote' => 'Iltimos, eʼtibor bering, oʻzgarishlar barcha viki-loyihalarda koʻrsatilishi uchun bir necha daqiqa kerak boʻlishi mumkin',
);

/** vèneto (vèneto)
 * @author Candalua
 */
$messages['vec'] = array(
	'wikibase-lib-desc' => 'Contien le funsionalità comuni par le estension Wikibase e Wikibase Client.',
	'wikibase-entity-item' => 'elemento',
	'wikibase-entity-property' => 'proprietà',
	'wikibase-entity-query' => 'richiesta',
	'wikibase-diffview-reference' => 'riferimento',
	'wikibase-diffview-rank' => 'rango',
	'wikibase-diffview-qualifier' => 'qualificador',
	'wikibase-diffview-label' => 'eticheta',
	'wikibase-diffview-alias' => 'alias',
	'wikibase-diffview-description' => 'descrission',
	'wikibase-diffview-link' => 'colegamenti',
	'wikibase-error-unexpected' => 'Xe capità un eror inprevisto.',
	'wikibase-error-save-generic' => "Calcosa xe 'ndà storto sercando de salvar, quindi po darse che le to modifiche le sia 'ndà perse.",
	'wikibase-error-remove-generic' => "Calcosa xe 'ndà storto sercando de far la rimosion, quindi po darse che le to modifiche le sia 'ndà perse.",
	'wikibase-error-save-connection' => "Ghe xe stà un problema de conesion sercando de salvar, quindi po darse che le to modifiche le sia 'ndà perse. Controla se la to conesion a Internet la funsiona.",
	'wikibase-error-remove-connection' => "Ghe xe stà un problema de conesion sercando de far la rimosion, quindi po darse che le to modifiche le sia 'ndà perse. Controla se la to conesion a Internet la funsiona.",
	'wikibase-error-save-timeout' => 'Gavemo dei problemi tènici, quindi no se gà podesto conpletar el to salvatajo.',
	'wikibase-error-remove-timeout' => 'Gavemo dei problemi tènici, quindi no se gà podesto conpletar la to rimosion.',
	'wikibase-error-autocomplete-connection' => 'No se riese a interogar le API de Wikipedia. Proa pi tardi.', # Fuzzy
	'wikibase-error-autocomplete-response' => 'Risposta del server: $1',
	'wikibase-error-ui-client-error' => 'La conesion a la pagina client no la xe riusìa. Proa pi tardi.',
	'wikibase-error-ui-no-external-page' => "L'articolo specificà no'l xe stà catà sul sito corispondente.",
	'wikibase-error-ui-cant-edit' => 'No te si mia autorixà a far sta roba.',
	'wikibase-error-ui-no-permissions' => 'No te ghè diriti suficienti a far sta azion.',
	'wikibase-error-ui-link-exists' => "No te pol colegar a sta pagina parché zà n'altro elemento el colega verso de ela.",
	'wikibase-error-ui-session-failure' => 'La sesion la xe scadùa. Entra da novo.',
	'wikibase-error-ui-edit-conflict' => 'Ghe xe un conflito de edizion. Par piaser ricarica e salva da novo.',
	'wikibase-replicationnote' => 'Podarìa volerghe calche minuto prima che i canbiamenti i se veda su tute le wiki.',
	'wikibase-sitelinks-sitename-columnheading' => 'Lengua',
	'wikibase-sitelinks-siteid-columnheading' => 'Còdese',
	'wikibase-sitelinks-link-columnheading' => 'Voxe ligà', # Fuzzy
	'wikibase-tooltip-error-details' => 'Detaji',
	'datatypes-type-wikibase-item' => 'Elemento',
	'datatypes-type-commonsMedia' => 'File multimediale su Commons',
	'version-wikibase' => 'Wikibase',
);

/** Vietnamese (Tiếng Việt)
 * @author Cheers!
 * @author Minh Nguyen
 * @author පසිඳු කාවින්ද
 */
$messages['vi'] = array(
	'wikibase-lib-desc' => 'Các chức năng chung của các phần mở rộng Wikibase và Trình khách Wikibase',
	'wikibase-entity-item' => 'khoản mục',
	'wikibase-entity-property' => 'thuộc tính',
	'wikibase-entity-query' => 'truy vấn',
	'wikibase-deletedentity-item' => 'Khoản mục đã xóa',
	'wikibase-deletedentity-property' => 'Thuộc tính đã xóa',
	'wikibase-deletedentity-query' => 'Truy vấn đã xóa',
	'wikibase-diffview-reference' => 'nguồn gốc',
	'wikibase-diffview-rank' => 'hạng',
	'wikibase-diffview-rank-preferred' => 'Hạng ưa thích',
	'wikibase-diffview-rank-normal' => 'Hạng bình thường',
	'wikibase-diffview-rank-deprecated' => 'Hạng phản đối',
	'wikibase-diffview-qualifier' => 'từ hạn định',
	'wikibase-diffview-label' => 'nhãn',
	'wikibase-diffview-alias' => 'tên khác',
	'wikibase-diffview-description' => 'miêu tả',
	'wikibase-diffview-link' => 'liên kết',
	'wikibase-error-unexpected' => 'Đã xuất hiện lỗi bất ngờ.',
	'wikibase-error-save-generic' => 'Đã gặp lỗi khi lưu nên không thể thực hiện các thay đổi của bạn.',
	'wikibase-error-remove-generic' => 'Đã gặp lỗi nên không thể thực hiện tác vụ loại bỏ.',
	'wikibase-error-save-connection' => 'Đã gặp lỗi kết nối khi lưu nên không thể thực hiện các thay đổi của bạn. Xin hãy kiểm tra kết nối Internet của bạn.',
	'wikibase-error-remove-connection' => 'Đã gặp lỗi nên không thể thực hiện tác vụ loại bỏ. Xin hãy kiểm tra kết nối Internet của bạn.',
	'wikibase-error-save-timeout' => 'Chúng tôi đang gặp trục trặc kỹ thuật nên không thể thực hiện tác vụ lưu của bạn.',
	'wikibase-error-remove-timeout' => 'Chúng tôi đang gặp trục trặc kỹ thuật nên không thể thực hiện tác vụ loại bỏ của bạn.',
	'wikibase-error-autocomplete-connection' => 'Không thể truy vấn API của dịch vụ. Xin hãy thử lại sau.',
	'wikibase-error-autocomplete-response' => 'Máy chủ đã phản hồi: $1',
	'wikibase-error-ui-client-error' => 'Kết nối đến trang khách bị thất bại. Xin hãy thử lại sau.',
	'wikibase-error-ui-no-external-page' => 'Không tìm thấy bài chỉ định trên dịch vụ tương ứng.',
	'wikibase-error-ui-cant-edit' => 'Bạn không được phép thực hiện thao tác này.',
	'wikibase-error-ui-no-permissions' => 'Bạn không có đủ quyền để thực hiện thao tác này.',
	'wikibase-error-ui-link-exists' => 'Không thể đặt liên kết đến trang này vì một khoản mục khác đã liên kết với nó.',
	'wikibase-error-ui-session-failure' => 'Phiên của bạn đã hết hạn. Xin hãy đăng nhập lại.',
	'wikibase-error-ui-edit-conflict' => 'Một mâu thuẫn sửa đổi đã xảy ra. Xin hãy tải lại và lưu lần nữa.',
	'wikibase-quantitydetails-amount' => 'Số lượng',
	'wikibase-quantitydetails-upperbound' => 'Giới hạn trên',
	'wikibase-quantitydetails-lowerbound' => 'Giới hạn dưới',
	'wikibase-quantitydetails-unit' => 'Đơn vị',
	'wikibase-timedetails-time' => 'Thời điểm',
	'wikibase-timedetails-isotime' => 'Dấu thời gian ISO',
	'wikibase-timedetails-timezone' => 'Múi giờ',
	'wikibase-timedetails-calendar' => 'Lịch',
	'wikibase-timedetails-precision' => 'Độ chính xác',
	'wikibase-timedetails-before' => 'Trước',
	'wikibase-timedetails-after' => 'Sau',
	'wikibase-globedetails-longitude' => 'Kinh độ',
	'wikibase-globedetails-latitude' => 'Vĩ độ',
	'wikibase-globedetails-precision' => 'Độ chính xác',
	'wikibase-globedetails-globe' => 'Địa cầu',
	'wikibase-replicationnote' => 'Xin lưu ý, có thể phải chờ vài phút để cho các wiki trình bày được các thay đổi',
	'wikibase-sitelinks-wikipedia' => 'Trang Wikipedia được liên kết với khoản mục này',
	'wikibase-sitelinks-sitename-columnheading' => 'Ngôn ngữ',
	'wikibase-sitelinks-sitename-columnheading-special' => 'Dịch vụ',
	'wikibase-sitelinks-siteid-columnheading' => 'Mã',
	'wikibase-sitelinks-link-columnheading' => 'Trang liên kết',
	'wikibase-tooltip-error-details' => 'Chi tiết',
	'wikibase-undeserializable-value' => 'Không thể hiển thị giá trị vì nó không hợp lệ.',
	'wikibase-validator-bad-type' => '$2 thay vì $1',
	'wikibase-validator-too-long' => 'Không được dài hơn {{PLURAL:$1|một ký tự|$1 ký tự}}',
	'wikibase-validator-too-short' => 'Phải có ít nhất {{PLURAL:$1|một ký tự|$1 ký tự}}',
	'wikibase-validator-too-high' => 'Ngoài phạm vi, không được hơn $1',
	'wikibase-validator-too-low' => 'Ngoài phạm vi, không được ít hơn $1',
	'wikibase-validator-malformed-value' => 'Giá trị hỏng: $1',
	'wikibase-validator-bad-entity-id' => 'ID hỏng: $1',
	'wikibase-validator-bad-entity-type' => 'Kiểu thực thể bất ngờ $1',
	'wikibase-validator-no-such-entity' => 'Không tìm thấy $1',
	'wikibase-validator-no-such-property' => 'Không tìm thấy thuộc tính $1',
	'wikibase-validator-bad-value' => 'Giá trị không hợp lệ: $1',
	'wikibase-validator-bad-value-type' => 'Kiểu giá trị không hợp lệ $1; đáng lẽ phải là $2',
	'wikibase-validator-bad-url' => 'URL hỏng: $1',
	'wikibase-validator-bad-url-scheme' => 'Giao thức URL không được hỗ trợ: $1',
	'wikibase-validator-bad-http-url' => 'URL HTTP hỏng: $1',
	'wikibase-validator-bad-mailto-url' => 'URL mailto: hỏng: $1',
	'wikibase-validator-unknown-unit' => 'Đơn vị không rõ: $1',
	'datatypes-type-wikibase-item' => 'Khoản mục',
	'datatypes-type-commonsMedia' => 'Tập tin phương tiện Commons',
	'version-wikibase' => 'Wikibase',
);

/** Volapük (Volapük)
 * @author Iketsi
 * @author Malafaya
 */
$messages['vo'] = array(
	'wikibase-timedetails-time' => 'Tim',
	'wikibase-sitelinks-sitename-columnheading' => 'Pük',
	'wikibase-tooltip-error-details' => 'Pats',
	'version-wikibase' => 'Wikibase',
);

/** Yiddish (ייִדיש)
 * @author פוילישער
 * @author පසිඳු කාවින්ද
 */
$messages['yi'] = array(
	'wikibase-entity-item' => 'דאטנאביעקט',
	'wikibase-entity-property' => 'אייגנשאפֿט',
	'wikibase-entity-query' => 'פֿראגע',
	'wikibase-deletedentity-item' => 'אויסגעמעקטער דאטנאביעקט',
	'wikibase-deletedentity-property' => 'אויסגעמעקטע אייגנשאפט',
	'wikibase-deletedentity-query' => 'אויסגעמעקטע פראגע',
	'wikibase-diffview-reference' => 'רעפערענץ',
	'wikibase-diffview-rank' => 'ראנג',
	'wikibase-diffview-qualifier' => 'באדינגונג',
	'wikibase-diffview-label' => 'באצייכענונג',
	'wikibase-diffview-alias' => 'אליאסן',
	'wikibase-diffview-description' => 'באַשרײַבונג',
	'wikibase-diffview-link' => 'לינקען',
	'wikibase-error-ui-link-exists' => 'איר קענט נישט פֿארלינקען אהער ווײַל אן אנדער בלאט לינקט שוין דארטן.',
	'wikibase-error-ui-session-failure' => 'אייער סעסיע האט אויפגעהערט. זייט אזוי גוט אריינלאגירן נאכאמאל.',
	'wikibase-error-ui-edit-conflict' => "ס'האט פאסירט א רעדאקטירונג קאנפליקט. זייט אזוי גוט אנלאדן דעם בלאט און אויפהיטן נאכאמאל.",
	'wikibase-timedetails-time' => 'צײַט',
	'wikibase-timedetails-timezone' => 'צײַט זאנע',
	'wikibase-timedetails-calendar' => 'קאלאענדאר',
	'wikibase-timedetails-before' => 'פאר',
	'wikibase-timedetails-after' => 'נאך',
	'wikibase-globedetails-longitude' => 'געאגראַפֿישע לענג',
	'wikibase-globedetails-latitude' => 'גארטל־ליניע',
	'wikibase-globedetails-globe' => 'גלאבוס',
	'wikibase-replicationnote' => 'גיט אכט אז עס קען דויערן עטלעכע מינוטן ביז די ענדערונגען ווערן זעבאר ביי אלע וויקיס.',
	'wikibase-sitelinks-wikipedia' => 'וויקיפעדיע בלעטער פארבונדן מיט דעם דאטנאביעקט',
	'wikibase-sitelinks-sitename-columnheading' => 'שפראַך',
	'wikibase-sitelinks-sitename-columnheading-special' => 'וועבזײַטל',
	'wikibase-sitelinks-siteid-columnheading' => 'קאד',
	'wikibase-sitelinks-link-columnheading' => 'פארלינקטער בלאט',
	'wikibase-tooltip-error-details' => 'פרטים',
	'wikibase-validator-bad-type' => '$2 אַנשטאָט $1',
	'wikibase-validator-no-such-entity' => '$1 נישט געטראפן',
	'datatypes-type-wikibase-item' => 'איינהייט',
	'datatypes-type-commonsMedia' => 'קאמאנס מעדיע טעקע',
	'version-wikibase' => 'Wikibase',
);

/** Simplified Chinese (中文（简体）‎)
 * @author Cwek
 * @author Hydra
 * @author Li3939108
 * @author Liuxinyu970226
 * @author Shizhao
 * @author Stevenliuyi
 * @author Xiaomingyan
 * @author Yfdyh000
 * @author Zhuyifei1999
 * @author 乌拉跨氪
 */
$messages['zh-hans'] = array(
	'wikibase-lib-desc' => '储存维基库及其客户端的共同功能',
	'wikibase-entity-item' => '项',
	'wikibase-entity-property' => '属性',
	'wikibase-entity-query' => '查询',
	'wikibase-deletedentity-item' => '删除项',
	'wikibase-deletedentity-property' => '删除属性',
	'wikibase-deletedentity-query' => '删除查询',
	'wikibase-diffview-reference' => '参考',
	'wikibase-diffview-rank' => '等级',
	'wikibase-diffview-rank-preferred' => '首选级',
	'wikibase-diffview-rank-normal' => '普通级',
	'wikibase-diffview-rank-deprecated' => '弃用级',
	'wikibase-diffview-qualifier' => '限定符',
	'wikibase-diffview-label' => '标签',
	'wikibase-diffview-alias' => '别名',
	'wikibase-diffview-description' => '说明',
	'wikibase-diffview-link' => '链接',
	'wikibase-error-unexpected' => '发生意外错误。',
	'wikibase-error-save-generic' => '保存时发生错误，因此您所做的更改可能未完成。',
	'wikibase-error-remove-generic' => '删除时发生错误，因此您所做的更改可能未完成。',
	'wikibase-error-save-connection' => '保存时发生连接错误，因此您的更改可能未完成。请检查您的网络连接。',
	'wikibase-error-remove-connection' => '删除时发生连接错误，因此您的更改可能未完成。请检查您的网络连接。',
	'wikibase-error-save-timeout' => '我们遇到了技术问题，因此无法完成您的保存操作。',
	'wikibase-error-remove-timeout' => '我们遇到了技术问题，因此无法完成您的删除操作。',
	'wikibase-error-autocomplete-connection' => '无法查询站点API。请稍后重试。',
	'wikibase-error-autocomplete-response' => '服务器响应：$1',
	'wikibase-error-ui-client-error' => '连接客户端页面失败。请稍后重试。',
	'wikibase-error-ui-no-external-page' => '在相应维基项目上找不到指定条目。',
	'wikibase-error-ui-cant-edit' => '您不能执行此操作。',
	'wikibase-error-ui-no-permissions' => '您没有足够的权限执行此操作。',
	'wikibase-error-ui-link-exists' => '因另一项已链接该页，您不能再链接此页。',
	'wikibase-error-ui-session-failure' => '您的会话已过期。请重新登录。',
	'wikibase-error-ui-edit-conflict' => '发生编辑冲突。请重新加载后再次保存。',
	'wikibase-quantitydetails-amount' => '总额',
	'wikibase-quantitydetails-upperbound' => '上限',
	'wikibase-quantitydetails-lowerbound' => '下限',
	'wikibase-quantitydetails-unit' => '单位',
	'wikibase-timedetails-time' => '时间',
	'wikibase-timedetails-isotime' => 'ISO时间戳',
	'wikibase-timedetails-timezone' => '时区',
	'wikibase-timedetails-calendar' => '日历',
	'wikibase-timedetails-precision' => '精度',
	'wikibase-timedetails-before' => '之前',
	'wikibase-timedetails-after' => '之后',
	'wikibase-globedetails-longitude' => '经度',
	'wikibase-globedetails-latitude' => '纬度',
	'wikibase-globedetails-precision' => '精度',
	'wikibase-globedetails-globe' => '世界各地',
	'wikibase-replicationnote' => '该更改可能需要几分钟才能在所有的维基项目上显示，请谅解。',
	'wikibase-sitelinks-wikipedia' => '链接至该项的维基百科页面',
	'wikibase-sitelinks-sitename-columnheading' => '语言',
	'wikibase-sitelinks-sitename-columnheading-special' => '网站',
	'wikibase-sitelinks-siteid-columnheading' => '代码',
	'wikibase-sitelinks-link-columnheading' => '链接的页面',
	'wikibase-tooltip-error-details' => '详情',
	'wikibase-undeserializable-value' => '这个值无效以及无法显示。',
	'wikibase-validator-bad-type' => '$2，预期类型为$1',
	'wikibase-validator-too-long' => '不能多于$1个字符',
	'wikibase-validator-too-short' => '不能少于$1个字符',
	'wikibase-validator-too-high' => '超出范围，必须不高于$1',
	'wikibase-validator-too-low' => '超出范围，必须不低于$1',
	'wikibase-validator-malformed-value' => '输入格式错误：$1',
	'wikibase-validator-bad-entity-id' => 'ID格式错误：$1',
	'wikibase-validator-bad-entity-type' => '意外的实体类型$1',
	'wikibase-validator-no-such-entity' => '未找到$1',
	'wikibase-validator-no-such-property' => '未找到属性$1',
	'wikibase-validator-bad-value' => '非法值：$1',
	'wikibase-validator-bad-value-type' => '错误值类型$1，预期为$2',
	'wikibase-validator-bad-url' => '格式不正确的地址：$1',
	'wikibase-validator-bad-url-scheme' => '不被支持的URL方案：$1',
	'wikibase-validator-bad-http-url' => '格式不正确的 HTTP 地址：$1',
	'wikibase-validator-bad-mailto-url' => '格式不正确 mailto 地址：$1',
	'wikibase-validator-unknown-unit' => '未知单位：$1',
	'datatypes-type-wikibase-item' => '项',
	'datatypes-type-commonsMedia' => '共享资源媒体文件',
	'version-wikibase' => '维基数据库',
);

/** Traditional Chinese (中文（繁體）‎)
 * @author Ch.Andrew
 * @author Justincheng12345
 * @author Li3939108
 * @author Liuxinyu970226
 * @author Simon Shek
 * @author Tntchn
 * @author Waihorace
 */
$messages['zh-hant'] = array(
	'wikibase-lib-desc' => '儲存維基基礎及其客戶端的共同功能',
	'wikibase-entity-item' => '項目',
	'wikibase-entity-property' => '屬性',
	'wikibase-entity-query' => '查詢',
	'wikibase-deletedentity-item' => '已刪除項',
	'wikibase-deletedentity-property' => '已刪除屬性',
	'wikibase-deletedentity-query' => '已刪除查詢',
	'wikibase-diffview-reference' => '參考',
	'wikibase-diffview-rank' => '分級',
	'wikibase-diffview-rank-preferred' => '首選級別',
	'wikibase-diffview-rank-normal' => '正常級別',
	'wikibase-diffview-rank-deprecated' => '不建議級別',
	'wikibase-diffview-qualifier' => '修飾成分',
	'wikibase-diffview-label' => '標籤',
	'wikibase-diffview-alias' => '別名',
	'wikibase-diffview-description' => '描述',
	'wikibase-diffview-link' => '連結',
	'wikibase-error-unexpected' => '發生意外錯誤。',
	'wikibase-error-save-generic' => '儲存時發生錯誤，因此您所作的更變可能未完成。',
	'wikibase-error-remove-generic' => '刪除時發生錯誤，因此您所作的更變可能未完成。',
	'wikibase-error-save-connection' => '儲存時發生錯誤，因此您所作的更變可能未完成。請檢查您的網絡連接。',
	'wikibase-error-remove-connection' => '刪除時發生錯誤，因此您所作的更變可能未完成。請檢查您的網絡連接。',
	'wikibase-error-save-timeout' => '我们遇到技術問題，因此無法完成儲存。',
	'wikibase-error-remove-timeout' => '我们遇到技術問題，因此無法完成刪除。',
	'wikibase-error-autocomplete-connection' => '無法查詢站台 API 。請稍後重試。',
	'wikibase-error-autocomplete-response' => '系統回應：$1',
	'wikibase-error-ui-client-error' => '無法連接到客戶端頁面。請稍後重試。',
	'wikibase-error-ui-no-external-page' => '相應維基項目無法找到指定條目。',
	'wikibase-error-ui-cant-edit' => '您不能執行此操作。',
	'wikibase-error-ui-no-permissions' => '您没有足够權限執行此操作。',
	'wikibase-error-ui-link-exists' => '因為另一項目已連接，您不能再連接到此頁。',
	'wikibase-error-ui-session-failure' => '您的資料已過期。請重新登入。',
	'wikibase-error-ui-edit-conflict' => '發生編輯衝突。請重新整理再儲存。',
	'wikibase-quantitydetails-amount' => '總額',
	'wikibase-quantitydetails-upperbound' => '上限',
	'wikibase-quantitydetails-lowerbound' => '下限',
	'wikibase-quantitydetails-unit' => '單位',
	'wikibase-timedetails-time' => '時間',
	'wikibase-timedetails-isotime' => 'ISO時間戳記',
	'wikibase-timedetails-timezone' => '時區',
	'wikibase-timedetails-calendar' => '日曆',
	'wikibase-timedetails-precision' => '精度',
	'wikibase-timedetails-before' => '之前',
	'wikibase-timedetails-after' => '之後',
	'wikibase-globedetails-longitude' => '經度',
	'wikibase-globedetails-latitude' => '緯度',
	'wikibase-globedetails-precision' => '精度',
	'wikibase-globedetails-globe' => '世界各地',
	'wikibase-replicationnote' => '所做的更改可能需要幾分鐘的時間才能在所有的維基上看到，敬請留意。',
	'wikibase-sitelinks-wikipedia' => '連至此項目的維基百科頁面',
	'wikibase-sitelinks-sitename-columnheading' => '語言',
	'wikibase-sitelinks-sitename-columnheading-special' => '站點',
	'wikibase-sitelinks-siteid-columnheading' => '代碼',
	'wikibase-sitelinks-link-columnheading' => '已連結頁面',
	'wikibase-tooltip-error-details' => '詳細資訊',
	'wikibase-undeserializable-value' => '此值無效無法顯示。',
	'wikibase-validator-bad-type' => '用於代替$1的$2',
	'wikibase-validator-too-long' => '必須不超出$1個位元',
	'wikibase-validator-too-short' => '必須不少於$1個位元',
	'wikibase-validator-too-high' => '超出範圍，必須不高於$1',
	'wikibase-validator-too-low' => '超出範圍，必須不低於$1',
	'wikibase-validator-malformed-value' => '格式錯誤輸入：$1',
	'wikibase-validator-bad-entity-id' => '格式錯誤ID：$1',
	'wikibase-validator-bad-entity-type' => '意外的實體類型$1',
	'wikibase-validator-no-such-entity' => '$1無法找尋',
	'wikibase-validator-no-such-property' => '屬性$1無法找尋',
	'wikibase-validator-bad-value' => '非法值：$1',
	'wikibase-validator-bad-value-type' => '錯誤值形式$1，預期$2',
	'wikibase-validator-bad-url' => '格式錯誤URL：$1',
	'wikibase-validator-bad-url-scheme' => '無法支援的URL方案：$1',
	'wikibase-validator-bad-http-url' => '格式不正確的HTTP位址：$1',
	'wikibase-validator-bad-mailto-url' => '格式不正確的mailto位址：$1',
	'wikibase-validator-unknown-unit' => '未知單位：$1',
	'datatypes-type-wikibase-item' => '項目',
	'datatypes-type-commonsMedia' => '共享資源媒體檔案',
	'version-wikibase' => 'Wikibase',
);
