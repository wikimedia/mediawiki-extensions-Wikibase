# Options {#topic_options}

This document describes the configuration of the Wikibase extensions.

As usual, the extension is configured in MediaWiki's LocalSettings.php file. However, Wikibase settings are placed in associative arrays, `$wgWBRepoSettings` and `$wgWBClientSettings` respectively, instead of individual global variables. So, if the setting `foo` is described below, you would need to use `$wgWBRepoSettings[ 'foo' ]` or `$wgWBClientSettings[ 'foo' ]` in LocalSettings.php.

Common Settings
---------------

### Basic Settings

changesDatabase
The database that changes are recorded to for processing by clients. This must be set to a symbolic database identifier that MediaWiki's LBFactory class understands; `false` means that the wiki's own database shall be used.

*NOTE*: On the client, this setting should usually be the same as the **repoDatabase** setting.

siteLinkGroups
The site groups to use in sitelinks. Must correspond to a value used to give the site group in the MediaWiki `sites` table. This defines which groups of sites can be linked to Wikibase items.

*DEFAULT*: is `array( 'wikipedia' )` (This defines which groups of sites can be linked to Wikibase items.)

*NOTE*: This setting replaces the old **siteLinkGroup**' setting, which only allowed for a single group.

specialSiteLinkGroups
This maps one or more site groups into a single “special” group. This is useful if sites from multiple site groups should be shown in a single “special” section on item pages, instead of one section per site group. To show these site-groups you have to add the group “special” to the **siteLinkGroups** setting (see above).

entitySources
An associative array mapping entity source names to settings relevant to the particular source. Configuration of each source is an associative array containing the following keys:

'entityNamespaces': A map of entity type identifiers (strings) that the local wiki supports from the foreign repository to namespaces (IDs or canonical names) related to pages of entities of the given type on foreign repository's wiki. If entities are stored in alternative slots, the syntax <namespace>/<slot> can be used to define which slot to use.
'repoDatabase': A symbolic database identifier (string) that MediaWiki's LBFactory class understands. Note that `false` would mean “this wiki's database”.
'baseUri': A base URI (string) for concept URIs. It should contain scheme and authority part of the URI.
'interwikiPrefix': An interwiki prefix configured in the local wiki referring to the wiki related to the entity source.
'rdfNodeNamespacePrefix': A prefix used in RDF turtle node namespaces, e.g. 'wd' would result in namespaces like 'wd' for the entity namespace, and 'wdt' for the direct claim namespace, whereas 'sdc' prefix would result in the namespaces 'sdc' and 'sdct' accordingly.
'rdfPredicateNamespacePrefix': A prefix used in RDF turtle predicate namespaces, e.g. '' would result in namespaces like 'ps' for the simple value claim namespace, whereas 'sdc' prefix would result in the namespace 'sdcps'.

### Expert Settings

sharedCacheKeyGroup
Group name for a group of Wikibases. Similar to sharedCacheKeyPrefix and normally a part of sharedCacheKeyPrefix, however this shared cache key group should be used as a part of keys generated within Wikibase.

*DEFAULT*: Constructed from `$wgDBname`.

sharedCacheKeyPrefix
Prefix to use for cache keys that should be shared among a wikibase repo and all its clients. In order to share caches between clients (and the repo), set a prefix based on the repo's name and `WBL_VERSION` or a similar version ID.

*DEFAULT*: Constructed from `$wgDBname` and `WBL_VERSION`.

*NOTE*: The default may change in order to use the repo's database name automatically.

sharedCacheDuration
The duration of entries in the shared object cache, in seconds.

*DEFAULT*: 3600 seconds (1 hour).

sharedCacheType
The type of cache to use for the shared object cache. Use `CACHE_XXX` constants.

*DEFAULT*: `$wgMainCacheType`

useChangesTable
Whether to record changes in the database, so they can be pushed to clients. Boolean, may be set to `false` in situations where there are no clients to notify to preserve space.

*DEFAULT*: `true`

*NOTE*: If this is `true`, the `pruneChanges.php` script should run periodically to remove old changes from the database table.

disabledDataTypes
Array listing of disabled data types on a wiki. This setting is intended to be used in a new Wikibase installation without items yet, or to control deployment of new data types. This setting should be set to the same value in both client and repo settings.

*DEFAULT*: empty array

maxSerializedEntitySize
The maximum serialized size of entities, in KB. Loading and storing will fail if this size is exceeded. This is intended as a hard limit that prevents very large chunks of data being stored or processed due to abuse or erroneous code.

*DEFAULT*: `$wgMaxArticleSize`

geoShapeStorageBaseUrl
Base URL of geo shape storage frontend. Used primarily to build links to the geo shapes. Will be concatenated with the page title, so should end with `/` or `title=`. Special characters (e.g. space, percent, etc.) should *not* be encoded.

tabularDataStorageBaseUrl
Base URL of tabular data storage frontend. Used primarily to build links to the tabular data pages. Will be concatenated with the page title, so should end with `/` or `title=`. Special characters (e.g. space, percent, etc.) should *not* be encoded.

useTermsTableSearchFields
Whether to use the search-related fields (`term_search_key` and `term_weight`) of the `wb_terms` table. This should not be disabled unless some other search backend is used (see `entitySearch` below).

forceWriteTermsTableSearchFields
If true, write search-related fields of the `wb_terms` table even if they are not used. Useful if you want to experiment with `useTermsTableSearchFields` and don’t want missed updates in the table.

useEntitySourceBasedFederation
Temporary flag defining whether the repository-prefix-based or entity-source-based federation mechanism (i.e. use of entities from multiple Wikibase instances) should be used. Use `true` to use entity-source-based federation. Default: `false`.

Repository Settings
-------------------

### Basic Settings

entityNamespaces
Defines which kind of entity is managed in which namespace. It is given as an associative array mapping entity types such as `'item'` to namespaces (IDs or canonical names). Mapping must be done for each type of entity that should be supported. If entities are stored in alternative slots, the syntax <namespace>/<slot> can be used to define which slot to use.

dataRightsUrl
URL to link to license for data contents.

*DEFAULT*: `$wgRightsUrl`

rdfDataRightsUrl
URL to link to license in RDF outputs.

*DEFAULT*: `'http://creativecommons.org/publicdomain/zero/1.0/'` (Public domain)

dataRightsText
Text for data license link.

*DEFAULT*: `$wgRightsText`

sparqlEndpoint
URL to the service description of the SPARQL end point for the repository.

*DEFAULT*: `null` (There is no SPARQL endpoint.)

badgeItems
Items allowed to be used as badges. This setting expects an array of serialized item IDs pointing to their CSS class names, like `array( 'Q101' => 'wb-badge-goodarticle' )`. With this class name it is possible to change the icon of a specific badge.

preferredPageImagesProperties
List of image property ID strings, in order of preference, that should be considered for the `page_image` page property.

*DEFAULT*: `array()` (An empty array.)

conceptBaseUri
Base URI for building concept URIs (for example used in Rdf output). This has to include the protocol and domain, only an entity identifier will be appended.

preferredGeoDataProperties
List of properties (by ID string), in order of preference, that are considered when finding primary coordinates for the GeoData extension on an entity.

*DEFAULT*: `array()` (An empty array.)

localClientDatabases
An array of locally accessible client databases, for use by the `dispatchChanges.php` script. This setting determines to which wikis changes are pushed directly. It must be given either as an associative array, mapping global site IDs to logical database names, or, of the database names are the same as the site IDs, as a list of databases.

*DEFAULT*: `array()` (An empty array, indicating no local client databases.)

foreignRepositories
An associative array mapping foreign repository names to settings relevant to the particular repository. Each repository's settings are an associative array containing the following keys:

'entityNamespaces'
A map of entity type identifiers (strings) that the local wiki supports from the foreign repository to namespaces (IDs or canonical names) related to pages of entities of the given type on foreign repository's wiki. If entities are stored in alternative slots, the syntax <namespace>/<slot> can be used to define which slot to use.

'repoDatabase'
A symbolic database identifier (string) that MediaWiki's LBFactory class understands.

'baseUri'
A base URI (string) for concept URIs. It should contain scheme and authority part of the URI.

'prefixMapping': A prefix mapping array, see also docs/foreign-entity-ids.wiki in the DataModel component.

enableEntitySearchUI
Boolean to determine if entity search UI should be enabled or not.

*DEFAULT*: `true`

localEntitySourceName
Name of the entity source name of the "local" repo, i.e. the repo of the local wiki. Should be the name of the entity source defined in `entitySources` setting. Default: `local`.

### Expert Settings

dispatchBatchChunkFactor
Chunk factor used internally by the `dispatchChanges.php` script. If most clients are not interested in most changes, this factor can be raised to lower the number of database queries needed to fetch a batch of changes.

*DEFAULT*: `3`

dispatchDefaultBatchSize
Overrides the default value for batch-size in dispatchChanges.php

dispatchDefaultMaxChunks
Overrides the default value for max-chunks in dispatchChanges.php

dispatchDefaultDispatchInterval
Overrides the default value for dispatch-interval in dispatchChanges.php

dispatchDefaultDispatchRandomness
Overrides the default value for randomness in dispatchChanges.php

dispatchMaxTime
Overrides the default value for max-time in dispatchChanges.php

dispatchIdleDelay
Overrides the default value for idle-delay in dispatchChanges.php

idBlacklist
A map from entity ID type to a list of IDs to reserve and skip for new entities of that type. IDs are given as integers. For example `[ 'item' => [ 1, 2, 3 ] ]`.

string-limits
Limits to impose on various strings, such as multilanguage terms, various data types etc.

Supported string types:
multilang
multilanguage strings like labels, descriptions and such. (used to be the multilang-limits option)

Supported limits:
length
the maximum length of the string, in characters.

multilang-limits
DEPRECATED( use string-limits ). Limits to impose on multilanguage strings like labels, descriptions and such. Supported limits:

length
the maximum length of the string, in characters.

*DEFAULT*:`array( 'length' => 250 )`.

urlSchemes
Which URL schemes should be allowed in URL data values. Supported schemes are `ftps`, `ircs`, `mms`, `nntp`, `redis`, `sftp`, `telnet`, `worldwind` and `gopher`. Schemes (protocols) added here will only have any effect if validation is supported for that protocol; that is, adding `ftps` will work, while adding `dummy` will do nothing.

*DEFAULT*: is `array( 'bzr', 'cvs', 'ftp', 'git', 'http', 'https', 'irc', 'mailto', 'ssh', 'svn' )`.

formatterUrlProperty
Property to be used on properties that defines a formatter URL which is used to link external identifiers. The placeholder `$1` will be replaced by the identifier. Example

On wikidata.org, this is set to `P1630`, a string property named “formatter URL”. When formatting identifiers, each identifier's property page is checked for its formatter URL (e.g. `http://d-nb.info/gnd/$1`) specified by the property from this setting.

canonicalUriProperty
Property to be used on properties that defines a URI pattern which is used to link external identifiers in RDF and other exports. The placeholder `$1` will be replaced by the identifier. Example

On wikidata.org, this is set to `P1921`, a string property named “URI used in RDF”. When exporting identifiers to RDF or other formats, each identifier's property page is checked for its URI pattern (e.g. `http://d-nb.info/gnd/$1/about/rdf`) specified by the property from this setting.

transformLegacyFormatOnExport
Whether entity revisions stored in a legacy format should be converted on the fly while exporting.

*DEFAULT*: `true`

allowEntityImport
Allow importing entities via Special:Import and importDump.php. Per default, imports are forbidden, since entities defined in another wiki would have or use IDs that conflict with entities defined locally.


*DEFAULT*: `false`

pagePropertiesRdf
Array that maps between page properties and Wikibase predicates for RDF dumps. Maps from database property name to an array that contains a key `'name'` (RDF property name, which will be prefixed by `wikibase:`) and an optional key `'type'`.

unitStorage
Definition for unit conversion storage. Should be in the format `ObjectFactory` understands, example

`array( 'class' => 'Wikibase\\Lib\\JsonUnitStorage', 'args' => array( 'myUnits.json' ) )`.

dispatchingLockManager
If you want to use another lock mechanism for dispatching changes to clients instead of database locking (which can occupy too many connections to the master database), set its name in this config. See \$wgLockManagers documentation in MediaWiki core for more information on configuring a locking mechanism inside core.

searchIndexProperties
Array of properties (by ID string) that should be included in the <code>'statement_keywords'<code> field of the search index. Relevant only for search engines supporting it.

searchIndexTypes
Array of auto-indexed type names. Statements with properties of this type will automatically be indexed in the “statement_keywords” field. Relevant only for search engines supporting it.

searchIndexPropertiesExclude
Array of properties (by ID string) that should be excluded from the `'statement_keywords'` field. This takes priority over other searchIndex\* settings. Relevant only for search engines supporting it.

searchIndexQualifierPropertiesForQuantity
Array of properties (by ID string) that, if used in a qualifier, will be used to write a value to the `'statement_quantity'` field. Relevant only for search engines supporting it.

dispatchLagToMaxLagFactor
If set to a positive number, the median dispatch lag (in seconds) will be divided by this number and passed to core like database lag (see the API maxlag parameter).

*DEFAULT*: `0` (disabled)

dumpDBDefaultGroup
This is the default database group to use in dump maintenance scripts, it defaults to “dump”. Set to `null` to use the value from `$wgDBDefaultGroup`.

*DEFAULT*: is `'dump'`

entityTypesWithoutRdfOutput
Array of entity type names which are not available to be output as RDF, default: empty list, meaning RDF is available for all entity types.

termboxEnabled
Enable/Disable the server-side-rendered (SSR) termbox. The default setting is `false`, so the SSR feature for termbox is disabled.

*DEFAULT*: `false`

ssrServerUrl
The url to where the server-side-renderer server (for termbox) is running.

ssrServerTimeout
Time after which wikibase aborts the connection to the ssr server.
termboxUserSpecificSsrEnabled
Enable/Disable server-side rendering (SSR) for user-specific termbox markup. The default setting is `true`. It only comes into effect if the general "termboxEnabled" is `true`. If disabled, user-specific termbox markup will only be created by client-side rendering after initial displaying of the generic termbox markup.
dataBridgeEnabled
Enable the repo parts of the Data Bridge Feature; see the corresponding client setting for more information. Default: `false`

taintedReferencesEnabled
Enable/Disable the tainted reference feature. The default setting is `false`, so that the feature is disabled.

*DEFAULT*: `false`

statementSections
Configuration to group statements together based on their datatype or other criteria like "propertySet". For example, putting all of external identifiers in one place. Here's an example:
```
$wgWBRepoSettings['statementSections'] = [
	'item' => [
		'statements' => null,
		'identifiers' => [
			'type' => 'dataType',
			'dataTypes' => [ 'external-id' ],
		],
	],
];
```
This configuration requires you to define `wikibase-statementsection-identifiers` message, otherwise rendering items will be broken.

*DEFAULT*: []

Client Settings
---------------

### Basic Settings

namespaces
List of namespaces on the client wiki that should have access to repository items.

*DEFAULT*: `array()` (Treated as setting is not set, ie. namespaces are enabled.)

excludeNamespaces
List of namespaces on the client wiki to disable wikibase links, etc. for.

*DEFAULT*: `array()`

Example `array( NS_USER_TALK )`.

repositories
An associative array mapping repository names to settings relevant to the particular repository. Local repository is identified using the empty string as its name. Each repository's settings are an associative array containing the following keys:

'entityNamespaces': A map of entity type identifiers (strings) that the local wiki supports from the foreign repository to namespaces (IDs or canonical names) related to pages of entities of the given type on foreign repository's wiki. If entities are stored in alternative slots, the syntax <namespace>/<slot> can be used to define which slot to use.
'repoDatabase': A symbolic database identifier (string) that MediaWiki's LBFactory class understands. Note that `false` would mean “this wiki's database”!
'baseUri': A base URI (string) for concept URIs. It should contain scheme and authority part of the URI.
'prefixMapping': A prefix mapping array, see also docs/foreign-entity-ids.wiki in the DataModel component.

repoUrl
The repository's base URL, including the schema (protocol) and domain; This URL can be protocol-relative.

*DEFAULT*: `'//wikidata.org'`

*NOTE*: This may be removed once we can get this information from the sites table.

repoScriptPath
The repository's script path.

*DEFAULT*: `$wgScriptPath` (Assuming that the repo's script path is the same as this wiki's script path.)

*NOTE*: This may be removed once we can get this information from the sites table.

repoArticlePath
The repository's article path.

*DEFAULT*: `$wgArticlePath` (Assuming that the repo's article path is the same as this wiki's script path.)

*NOTE*: This may be removed once we can get this information from the sites table.

siteGlobalID
This site's global ID (e.g. `'itwiki'`), as used in the sites table.

*DEFAULT*: `$wgDBname`.

siteLocalID
This site's local ID respective language code (e.g. `'it'`).

*DEFAULT*: `$wgLanguageCode`.

*NOTE*: This setting will be removed once we can take this information from the sites table.

siteGroup
This site's site group (e.g. `'wikipedia'` or `'wikivoyage'`) as used in the sites table. The setting is optional and falls back to site store lookup. For performance reasons, it may be desirable to set this explicitly to avoid lookups.

repoSiteId
Site ID of connected repository wiki. Default is to assume both client and repo are the same.

*DEFAULT*: `$siteGlobalID`

repoSiteName
Site name of the connected repository wiki. Default is to assume client and repo are same wiki, so defaults to global \$wgSitename setting. If not the same wiki, defaults to 'Wikidata'. This setting can also be set to an i18n message key and will be handled as a message, if the message key exists so that the repo site name can be translatable.

*DEFAULT*: `$wgSitename`

repoNamespaces
An array telling the client wiki which namespaces on the repository are used for which entity type. This is given as an associative array mapping entity type IDs such as Item::ENTITY_TYPE, to namespace names. This information is used when constructing links to entities on the repository.

*DEFAULT*: (items in main namespace):

:

        [
            'item' => "",
            'property' => 'Property'
        ]

allowDataTransclusion
Switch to enable data transclusion features like the `<nowiki>{{#property}}</nowiki>` parser function and the `wikibase` Scribunto module.

*DEFAULT*: `true`

allowLocalShortDesc
Switch to enable local override of the central description with `<nowiki>{{SHORTDESC:}}</nowiki>`.

*DEFAULT*: `false`

allowArbitraryDataAccess
Switch to allow accessing arbitrary items from the `wikibase` Scribunto module and the via the parser functions (instead of just the item which is linked to the current page).

*DEFAULT*: `true`

allowDataAccessInUserLanguage
Switch to allow accessing data in the user's language rather than the content language from the `wikibase` Scribunto module and the via the parser functions. Useful for multilingual wikis

Allows users to split the ParserCache by user language.

*DEFAULT*: `false`

entityAccessLimit
Limit for the number of different full entities that can be loaded on any given page, via Scribunto or the property parser function.

*DEFAULT*: `200`

propagateChangesToRepo
Switch to enable or disable the propagation of client changes to the repo.

*DEFAULT*: `true`.

languageLinkSiteGroup
ID of the site group to be shown as language links.

*DEFAULT*: `null` (That is the site's own site group.)

badgeClassNames
A list of additional CSS class names for site links that have badges. The array has to consist of serialized item IDs pointing to their CSS class names, like `array( 'Q101' => 'badge-goodarticle' )`. Note that this extension does not add any CSS to actually display the badges.

otherProjectsLinks
Site global ID list of sites which should be linked in the other project's sidebar section. Empty value will suppress this section.

propertyOrderUrl
URL to use for retrieving the property order used for sorting properties by property ID. Will be ignored if set to null.

disabledAccessEntityTypes
List of entity types that access to them in the client should be disabled.

entityUsagePerPageLimit
If a page in client uses too many aspects and entities, Wikibase issues a warning. This setting determines value of that threshold.

*DEFAULT*: `100`

### Expert Settings

injectRecentChanges
Whether changes on the repository should be injected into this wiki's recent changes table, so they show up on watchlists, etc. Requires the `dispatchChanges.php` script to run, and this wiki to be listed in the `localClientDatabases` setting on the repository.

showExternalRecentChanges
Whether changes on the repository should be displayed on Special:RecentChanges, Special:Watchlist, etc on the client wiki. In contrast to `injectRecentChanges`, this setting just removes the changes from the user interface. This is intended to temporarily prevent external changes from showing in order to find or fix some issue on a live site.

*DEFAULT*: `false`

sendEchoNotification
If true, allows users on the client wiki to get a notification when a page they created is connected to a repo item. This requires the Echo extension.

echoIcon
If `sendEchoNotification` is set to `true`, you can also provide what icon the user will see. The correct syntax is `[ 'url' => '...' ]` or `[ 'path' => '...' ]` where `path` is relative to `$wgExtensionAssetsPath`.

*DEFAULT*: `false` (That is there will be the default Echo icon.)

disabledUsageAspects
Array of usage aspects that should not be saved in the `wbc_entity_usage` table. This supports aspect codes (like “T”, “L” or “X”), but not full aspect keys (like “L.de”). For example `[ 'D', 'C' ]` can be used to disable description and statement usages. A replacement usage type can be given in the form of `[ 'usage-type-to-replace' => 'replacement' ]`.

fineGrainedLuaTracking
Enable fine-grained tracking on entities accessed through Lua in client. Not all (X) usage will be recorded, but each aspect will be recorded individually based on actual usage.

wikiPageUpdaterDbBatchSize
DEPRECATED. If set, acts as a default for purgeCacheBatchSize and recentChangesBatchSize.

purgeCacheBatchSize
Number of pages to process in each HTMLCacheUpdateJob, a job used to send client wikis notifications about relevant changes to entities. Higher value mean fewer jobs but longer run-time per job.

*DEFAULT*: wikiPageUpdaterDbBatchSize (for backwards compatibility) or MediaWiki core's `$wgUpdateRowsPerJob` (which currently defaults to 300).

recentChangesBatchSize
Number of `recentchanges` table rows to create in each InjectRCRecordsJob, a job used to send client wikis notifications about relevant changes to entities. Higher value mean fewer jobs but longer run-time per job.

*DEFAULT*: wikiPageUpdaterDbBatchSize (for backwards compatibility) or MediaWiki core's `$wgUpdateRowsPerJob` (which currently defaults to 300).

entityUsageModifierLimits
Associative array mapping usage type to the limit. If number of modifiers for the given aspect of an entity passes this limit, it turns all modifiers to a general entity usage in the given aspect. This is useful when with bad lua, a page in client uses all languages or statements in the repo causing the wbc_entity_usage become too big.

referencedEntityIdAccessLimit

Maximum number of calls to `mw.wikibase.getReferencedEntityId` allowed on a single page.

referencedEntityIdMaxDepth
Maximum search depth for referenced entities in `mw.wikibase.getReferencedEntityId`.

referencedEntityIdMaxReferencedEntityVisits
Maximum number of entities to visit in a `mw.wikibase.getReferencedEntityId` call.

pageSchemaNamespaces
An array of namespace numbers defaulting to empty (disabled); pages with a matching namespace will include a JSON-LD schema script for search engine optimization (SEO).

trackLuaFunctionCallsPerSiteGroup
Whether to track Lua function calls with a per-sitegroup key, like `MediaWiki.wikipedia.wikibase.client.scribunto.wikibase.functionName.call`.

trackLuaFunctionCallsPerWiki
Whether to track Lua function calls with a per-site key, like `MediaWiki.dewiki.wikibase.client.scribunto.wikibase.functionName.call`.

addEntityUsagesBatchSize
Batch size for adding entity usage records. Default is 500

dataBridgeEnabled
Enables the Data Bridge Feature, which allows editing a repository directly from a client wiki. To enable it, set this setting to `true` on both repo and client and also configure `dataBridgeHrefRegExp` (see below). Default: `false`

dataBridgeHrefRegExp
Regular expression to match edit links for which the Data Bridge is enabled. Uses JavaScript syntax, with the first capturing group containing the title of the entity, the second one containing the entity ID (usually a part of the first capturing group) and the third one containing the property ID to edit. Mandatory if `dataBridgeEnabled` is set to `true` – there is no default value.

dataBridgeEditTags
A list of tags for tracking edits through the Data Bridge. Optional if `dataBridgeEnabled` is set to `true`, with a default value of `[]`. Please note: you also have to create those tags in the target repository via Special:Tags.
