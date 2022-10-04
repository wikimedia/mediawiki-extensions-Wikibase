# Options

This document describes the configuration of the Wikibase components.

As usual, the extension is configured in MediaWiki's LocalSettings.php file.
However, Wikibase settings are placed in associative arrays, `$wgWBRepoSettings` and `$wgWBClientSettings` respectively, instead of individual global variables.

So, if the setting `foo` is described below, you would need to use ```$wgWBRepoSettings['foo']``` or ```$wgWBClientSettings['foo']``` in LocalSettings.php.

Default settings in each Wikibase settings array are setup by loading WikibaseLib.default.php followed by the default settings file for either:
 - Wikibase.default.php (for Repos)
 - WikibaseClient.default.php (for Clients)

[TOC]

Common Settings
----------------------------------------------------------------------------------------

Common settings exist on both a Repo and the Client.

### Sitelinks

#### siteLinkGroups {#common_siteLinkGroups}
The site groups to use in sitelinks.

 - Must correspond to a value used to give the site group in the MediaWiki `sites` table.
 - This defines which groups of sites can be linked to Wikibase items.

DEFAULT: is ```[]``` (This defines which groups of sites can be linked to Wikibase items.)

EXAMPLE: ```[ 'wikipedia', 'wikibooks', 'special' ]```

#### specialSiteLinkGroups
This maps one or more site groups into a single “special” group.

This is useful if sites from multiple site groups should be shown in a single “special” section on item pages, instead of one section per site group.
To show these site-groups you have to add the group “special” to the [siteLinkGroups] setting.

EXAMPLE: ```[ 'commons', 'meta', 'wikidata' ]```

### Change Propagation

See @ref docs_topics_change-propagation

#### useChangesTable
Whether to record changes in the database, so they can be pushed to clients.

Boolean, may be set to `false` to completely disable change dispatching from this wiki.

DEFAULT: ```true```

### Storage URLs

#### geoShapeStorageBaseUrl
Base URL of geo shape storage frontend.

Used primarily to build links to the geo shapes.
Will be concatenated with the page title, so should end with `/` or `title=`.
Special characters (e.g. space, percent, etc.) should *not* be encoded.

DEFAULT: ```"https://commons.wikimedia.org/wiki/"```

### geoShapeStorageApiEndpointUrl

DEFAULT: ```"https://commons.wikimedia.org/w/api.php"```

#### tabularDataStorageBaseUrl
Base URL of tabular data storage frontend.

Used primarily to build links to the tabular data pages.
Will be concatenated with the page title, so should end with `/` or `title=`.
Special characters (e.g. space, percent, etc.) should *not* be encoded.

DEFAULT: ```"https://commons.wikimedia.org/wiki/"```

### tabularDataStorageApiEndpointUrl

DEFAULT: ```"https://commons.wikimedia.org/w/api.php"```

### Shared cache

#### sharedCacheKeyGroup
Group name for a group of Wikibases.

Similar to [sharedCacheKeyPrefix] and normally a part of [sharedCacheKeyPrefix], however this shared cache key group should be used as a part of keys generated within Wikibase.

DEFAULT: Constructed from [$wgDBname].

#### sharedCacheKeyPrefix {#common_sharedCacheKeyPrefix}
Prefix to use for cache keys that should be shared among a wikibase repo and all its clients.

In order to share caches between clients (and the repo), set a prefix based on the repo's name and optionally some version ID.

DEFAULT: Constructed from [$wgDBname].

#### sharedCacheDuration
The duration of entries in the shared object cache, in seconds.

DEFAULT: 3600 seconds (1 hour).

#### sharedCacheType {#common_sharedCacheType}
The type of cache to use for the shared object cache. Use `CACHE_XXX` constants.

DEFAULT: [$wgMainCacheType]

#### termFallbackCacheVersion {#common_termFallbackCacheVersion}
Integer value to be appended to the shared cache prefix. Can be used to invalidate the term fallback cache by incrementing/changing this value.

DEFAULT: null

### Miscellaneous

#### entitySources {#common_entitySources}
An associative array mapping entity source names to settings relevant to the particular source.

See the [entitysources topic] for more details about the value of this setting.

#### disabledDataTypes
Array listing of disabled data types on a wiki.

This setting is intended to be used in a new Wikibase installation without items yet, or to control deployment of new data types.
This setting should be set to the same value in both client and repo settings.

DEFAULT: ```[]``` (empty array)

#### maxSerializedEntitySize
The maximum serialized size of entities, in KB.

Loading and storing will fail if this size is exceeded.
This is intended as a hard limit that prevents very large chunks of data being stored or processed due to abuse or erroneous code.

DEFAULT: [$wgMaxArticleSize]

### useKartographerGlobeCoordinateFormatter

DEFAULT: ```false```

### useKartographerMaplinkInWikitext

DEFAULT: ```false```

Repository Settings
----------------------------------------------------------------------------------------

### Urls, URIs & Paths

#### dataRightsUrl
URL to link to license for data contents.

DEFAULT: [$wgRightsUrl]

#### rdfDataRightsUrl
URL to link to license in RDF outputs.

DEFAULT: ```http://creativecommons.org/publicdomain/zero/1.0/``` (Public domain)

#### sparqlEndpoint
URL to the service description of the SPARQL end point for the repository.

DEFAULT: ````null```` (There is no SPARQL endpoint.)

EXAMPLE: ```https://query.wikidata.org/sparql```

#### globeUris
Mapping of globe URIs to canonical names, as recognized and used by [GeoData] extension when indexing and querying for coordinates.

If you want to remove one from this list, set its value to false. For example:
```php
$wgWBRepoSettings['globeUris']['http://www.wikidata.org/entity/Q2'] = false;
```

EXAMPLE: ```['http://www.wikidata.org/entity/Q2' => 'earth']```

### Properties & Items

#### idGenerator {#repo_idGenerator}
Allows the entity id generator to be chosen. (See @ref docs_storage_id-counters)

DEFAULT: ```original```

Allows values: `original`, `mysql-upsert`, or `auto`

#### idGeneratorSeparateDbConnection {#repo_idGeneratorSeparateDbConnection}
Should a separate DB connection be used to generate entity IDs?  (See @ref docs_storage_id-counters)

DEFAULT: ```false```

#### idGeneratorInErrorPingLimiter {#repo_idGeneratorInErrorPingLimiter}
Attempt to create an entity locks an entity id (for items, it would be Q####) and if saving fails due to validation issues for example, that id would be wasted.
This config helps by adding a bigger number to ratelimit and slow them down to avoid bots wasting significant number of Q-ids by sending faulty data over and over again.
Value of this config determines how much the user is going to be penalized for an error in creation of entities.
Zero means no penalty. The higher value, the heavier the penalty would be.

This feature depends on MediaWiki rate limits, which require a cache to be configured.

DEFAULT: 0

#### sandboxEntityIds
Entity ids to be used in various live examples.

These entities will be affected by changes made through those
examples, such as edits made by the API sandbox.

DEFAULT: ```[ 'mainItem' => 'Q999999998', 'auxItem' => 'Q999999999']```

#### badgeItems
Items allowed to be used as badges.

This setting expects an array of serialized item IDs pointing to their CSS class names.
With this class name it is possible to change the icon of a specific badge.

EXAMPLE: ```[ 'Q101' => 'wb-badge-goodarticle' ]```

#### redirectBadgeItems
These item IDs are badges which can be used to mark sitelinks to redirects.
A sitelink to a redirect may only be created when it includes one of these badges.
Note: all listed items have to be included in `badgeItems`.

This setting expects an array of serialized item IDs.

EXAMPLE: ```[ 'Q102', 'Q103' ]```

#### preferredPageImagesProperties
List of image property ID strings, in order of preference, that should be considered for the `page_image` [page property].

DEFAULT: ```[]``` (An empty array.)

EXAMPLE: ```[ 'P10', 'P123', 'P8000' ]```

#### preferredGeoDataProperties
List of properties (by ID string), in order of preference, that are considered when finding primary coordinates for the GeoData extension on an entity.

DEFAULT: ```[]``` (An empty array.)

#### formatterUrlProperty
Property to be used on properties that defines a formatter URL which is used to link external identifiers.

The placeholder `$1` will be replaced by the identifier.
When formatting identifiers, each identifier's property page is checked for its formatter URL (e.g. `http://d-nb.info/gnd/$1`) specified by the property from this setting.

EXAMPLE: On wikidata.org, this is set to `P1630`, a string property named “formatter URL”.

#### canonicalUriProperty
Property to be used on properties that defines a URI pattern which is used to link external identifiers in RDF and other exports. The placeholder `$1` will be replaced by the identifier.

When exporting identifiers to RDF or other formats, each identifier's property page is checked for its URI pattern (e.g. `http://d-nb.info/gnd/$1/about/rdf`) specified by the property from this setting.

EXAMPLE: On wikidata.org, this is set to `P1921`, a string property named “URI used in RDF”.

### Dispatching

#### localClientDatabases {#client_localClientDatabases}
An array of locally accessible client databases, for use by the dispatchChanges.php script.

See @ref docs_topics_change-propagation
This setting determines to which wikis changes are pushed directly.
It must be given either as an associative array, mapping site global IDs to logical database names, or, of the database names are the same as the site global IDs, as a list of databases.

DEFAULT: ```[]``` (An empty array, indicating no local client databases.)

Wikidata has all client sites listed in this array.

### Import, Export & Dumps

#### transformLegacyFormatOnExport
Whether entity revisions stored in a legacy format should be converted on the fly while exporting.

DEFAULT: ```true```

#### allowEntityImport
Allow importing entities via Special:Import and importDump.php.

Per default, imports are forbidden, since entities defined in another wiki would have or use IDs that conflict with entities defined locally.

DEFAULT: ```false```

#### pagePropertiesRdf
Array that maps between [page property] values and Wikibase predicates for RDF dumps.

Maps from database property name to an array that contains a key `'name'` (RDF property name, which will be prefixed by `wikibase:`) and an optional key `'type'`.

#### dumpDBDefaultGroup
This is the default database group to use in dump maintenance scripts, it defaults to `dump`.
Set to `null` to use the value from [$wgDBDefaultGroup].

DEFAULT: ```dump```

#### entityTypesWithoutRdfOutput
Array of entity type names which are not available to be output as RDF.

DEFAULT: ```[]``` (meaning RDF is available for all entity types)

#### entityDataFormats
Formats that shall be available via SpecialEntityData.

The first format will be used as the default.
Even if a format is allowed here, it may not be supported because when missing optional dependencies (e.g. purtle).
The formats are given using logical names as used by EntityDataSerializationService.

#### dataCdnMaxAge
Number of seconds for which data output on Special:EntityData should be cached.

Note: keep that low, because such caches cannot always be purged easily.

DEFAULT: [$wgCdnMaxAge]

#### entityDataCachePaths
URL paths for which entity data shall be cacheable.
A list of strings, each of which should be a URL path pattern,
usually starting with [$wgArticlePath] or [$wgScriptPath] and containing `{entity_id}` and `{revision_id}` placeholders,
but not including [$wgServer] or any other server.

Entity data is only cached if the request URL exactly matches one of the patterns specified here.

DEFAULT (assuming [$wgArticlePath] is `/wiki/$1`):
```
[
    '/wiki/Special:EntityData/{entity_id}.json?revision={revision_id}',
    '/wiki/Special:EntityData/{entity_id}.ttl?flavor=dump&revision={revision_id}',
]
```

### Search

#### enableEntitySearchUI
Boolean to determine if entity search UI should be enabled or not.

This overrides the behaviour of the default search box UI in MediaWiki.

DEFAULT: ```true```

#### searchIndexProperties
Array of properties (by ID string) that should be included in the `statement_keywords` field of the search index.

Relevant only for search engines supporting it.

#### searchIndexTypes
Array of auto-indexed type names.

Statements with properties of this type will automatically be indexed in the `statement_keywords` field.

Relevant only for search engines supporting it.

#### searchIndexPropertiesExclude
Array of properties (by ID string) that should be excluded from the `statement_keywords` field.

This takes priority over other searchIndex\* settings.

Relevant only for search engines supporting it.

#### searchIndexQualifierPropertiesForQuantity
Array of properties (by ID string) that, if used in a qualifier, will be used to write a value to the `'statement_quantity'` field.

Relevant only for search engines supporting it.

#### searchProfiles
Array of search profiles offered by the wbsearchentities and query+wbsearch API modules.
Keys are strings available as parameter values in the API;
the first key will be used as the default value if the parameter is not specified,
and it’s strongly recommended to make the first key 'default' with the value null.
Values are strings or null, and should be understood by the `EntitySearchHelper` implementation used by the wiki;
this may depend on other extensions, such as WikibaseCirrusSearch.
Must be nonempty.

DEFAULT: ```[ 'default' => null ]```

### Termbox & SSR

#### termboxEnabled {#repo_termboxEnabled}
Enable/Disable Termbox v2. Setting it to ```true``` will enable both client-side and server-side rendering functionality. In order for server-side rendering to work, the respective service needs to be set up and ```ssrServerUrl``` has to be set accordingly; otherwise, users without JavaScript will not see a termbox.

DEFAULT: ```true```

#### ssrServerUrl
The url to where the server-side-renderer server (for termbox) is running.

#### ssrServerTimeout
Time after which wikibase aborts the connection to the ssr server.

DEFAULT: ```3```

#### termboxUserSpecificSsrEnabled

Enable/Disable server-side rendering (SSR) for user-specific termbox markup.

DEFAULT: ```true```

It only comes into effect if the general [termboxEnabled] is `true`.
If disabled, user-specific termbox markup will only be created by client-side rendering after initial displaying of the generic termbox markup.

### Tags

These settings define [change tags][Help:Tags] that should be added to different edits.
All of them default to the empty list, meaning that no tags are added by default;
when you configure them, you also have to create those tags via Special:Tags.

#### updateRepoTags {#repo_updateRepoTags}
List of tags to be added to edits made via jobs enqueued by client wikis,
to update sitelinks when connected pages on the client are moved or deleted.
(Note that this is a _repo_ setting: the same list of tags is used for updates coming from all client wikis.)

DEFAULT: `[]`

#### viewUiTags
List of tags to be added to edits made via the main frontend (WikibaseView).

DEFAULT: `[]`

#### termboxTags
List of tags to be added to edits made via Termbox v2 (see [termboxEnabled]).

DEFAULT: `[]`

#### specialPageTags
List of tags to be added to edits made via special pages:
Special:NewItem, Special:SetLabel, etc.

DEFAULT: `[]`

### Miscellaneous

#### dataRightsText
Text for data license link.

DEFAULT: [$wgRightsText]

#### localEntitySourceName
Name of the entity source of the local repo (the same site).

Must match the name of the entity source as defined in [entitySources] setting.

This setting is intended to be used by Wikibase installations with complex setups which have multiple repos attached.

DEFAULT: ```local```

#### statementSections
Configuration to group statements together based on their datatype or other criteria like "propertySet". For example, putting all of external identifiers in one place.

EXAMPLE:
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
Section configurations other than "statements" and "identifiers" require you to define `wikibase-statementsection-*` messages for section headings to be rendered correctly.

DEFAULT: ```[]```

#### reservedIds
A map from entity ID type to a list of IDs to reserve and skip for new entities of that type.

IDs are given as integers.

DEFAULT: ```[]``` (empty array)

EXAMPLE: ```[ 'item' => [ 1, 2, 3 ] ]```

#### string-limits
Limits to impose on various strings, such as multilanguage terms, various data types etc.

Supported string types:
 - **multilang** - multilanguage strings like labels, descriptions and such. (used to be the multilang-limits option)
 - **VT:monolingualtext**
 - **VT:string**
 - **PT:url**

Supported limits:
 - length - the maximum length of the string, in characters.

DEFAULT:
```php
[
	'multilang' => [
		'length' => 250,
	],
	'VT:monolingualtext' => [
		'length' => 400,
	],
	'VT:string' => [
		'length' => 400,
	],
	'PT:url' => [
		'length' => 500,
	],
],
```

#### multilang-limits
**DEPRECATED** ( use string-limits ).
Limits to impose on multilanguage strings like labels, descriptions and such. Supported limits:

#### urlSchemes
Which URL schemes should be allowed in URL data values.

Supported schemes are `ftps`, `ircs`, `mms`, `nntp`, `redis`, `sftp`, `telnet`, `worldwind` and `gopher`.
Schemes (protocols) added here will only have any effect if validation is supported for that protocol; that is, adding `ftps` will work, while adding `dummy` will do nothing.

If you want to remove one from this list, set its value to false. For example:
```php
$wgWBRepoSettings['urlSchemes']['mailto'] = false;
```

DEFAULT: is ```['bzr', 'cvs', 'ftp', 'git', 'http', 'https', 'irc', 'mailto', 'ssh', 'svn']```

#### unitStorage
Definition for unit conversion storage.

Should be in the format [ObjectFactory] understands.

EXAMPLE: ```[ 'class' => 'Wikibase\Lib\Units\JsonUnitStorage', 'args' => [ __DIR__ . '/myUnits.json' ] ]```

#### canonicalLanguageCodes
Special non-canonical languages and their BCP 47 mappings

Based on: https://meta.wikimedia.org/wiki/Special_language_codes

If you want to remove one from this list, set its value to false. For example:
```php
$wgWBRepoSettings['canonicalLanguageCodes']['simple'] = false;
```


#### dataBridgeEnabled {#repo_dataBridgeEnabled}
Enable the repo parts of the Data Bridge Feature; see the corresponding client setting for more information.

DEFAULT: ```false```

#### taintedReferencesEnabled {#repo_taintedReferencesEnabled}
Enable/Disable the tainted reference feature.

DEFAULT: ```false```

#### federatedPropertiesEnabled {#repo_federatedPropertiesEnabled}
Enable the federated properties feature. **Note that** once this feature is enable (set true), it must not be disabled (set false) again.
The behaviour is unpredicted if it is disabled after it was enabled.

DEFAULT: ```false```

#### federatedPropertiesSourceScriptUrl {#repo_federatedPropertiesSourceScriptUrl}
A url path for the location of the source wikibase instance.
The set url path should allow access to both `index.php` and `api.php`

DEFAULT: ```https://www.wikidata.org/w/```

#### changeVisibilityNotificationClientRCMaxAge {#repo_changeVisibilityNotificationClientRCMaxAge}
Value of the `$wgRCMaxAge` setting, which specifies the max age (in seconds) of entries in the `recentchanges` table, on the client wikis.

DEFAULT: [$wgRCMaxAge].

#### changeVisibilityNotificationJobBatchSize {#repo_changeVisibilityNotificationJobBatchSize}
Batch size (how many revisions per job) to use when pushing `ChangeVisibilityNotification` jobs to clients.

DEFAULT: ```3```.

#### deleteNotificationClientRCMaxAge {#repo_deleteNotificationClientRCMaxAge}
Value of the `$wgRCMaxAge` setting, which specifies the max age (in seconds) of entries in the `recentchanges` table, on the client wikis.

Example: On entity-page deletion the DeleteDispatcher hook is called and creates a DispatchChangeDeletionNotification job which in turn collects the revision rows from `archive` using this threshold.

DEFAULT: [$wgRCMaxAge].

Client Settings
----------------------------------------------------------------------------------------

#### namespaces
List of namespaces on the client wiki that should have access to repository items.

DEFAULT: ```[]``` (Treated as setting is not set, ie. All namespaces are enabled.)

#### excludeNamespaces
List of namespaces on the client wiki to disable wikibase links, etc. for.

DEFAULT: ```[]```

EXAMPLE: `[ NS_USER_TALK ]`.

#### siteGlobalID {#client_siteGlobalID}
This site's global ID (e.g. `'itwiki'`), as used in the sites table.

DEFAULT: [$wgDBname].

#### siteLocalID
This site's local ID respective language code (e.g. `'it'`).

DEFAULT: [$wgLanguageCode].

*NOTE*: This setting will be removed once we can take this information from the sites table.

#### siteGroup
This site's site group (e.g. `'wikipedia'` or `'wikivoyage'`) as used in the sites table.

The setting is optional and falls back to site store lookup.
For performance reasons, it may be desirable to set this explicitly to avoid lookups.

### Repository

#### repoSiteId
Site global ID of connected repository wiki

DEFAULT: is to assume both client and repo are the same.

DEFAULT: Same as [siteGlobalID] wikibase setting

#### repoSiteName
Site name of the connected repository wiki.

The default is to assume client and repo are same wiki, so defaults to global [$wgSitename] setting.
If not the same wiki, defaults to 'Wikibase'.
This setting can also be set to an i18n message key and will be handled as a message, if the message key exists so that the repo site name can be translatable.

DEFAULT: [$wgSitename]

### Urls, URIs & Paths

#### repoUrl
The repository's base URL, including the schema (protocol) and domain; This URL can be protocol-relative.

DEFAULT: ```//wikidata.org```

*NOTE*: This may be removed once we can get this information from the sites table.

#### repoScriptPath
The repository's script path.

DEFAULT: [$wgScriptPath] - Assuming that the repo's script path is the same as this wiki's script path.

*NOTE*: This may be removed once we can get this information from the sites table.

#### repoArticlePath
The repository's article path.

DEFAULT: [$wgArticlePath] - Assuming that the repo's article path is the same as this wiki's script path.

*NOTE*: This may be removed once we can get this information from the sites table.

#### propertyOrderUrl
URL to use for retrieving the property order used for sorting properties by property ID.

Will be ignored if set to null.

EXAMPLE: ```https://www.wikidata.org/w/index.php?title=MediaWiki:Wikibase-SortedProperties&action=raw&sp_ver=1```

### Transclusion & Data Access

#### allowDataTransclusion {#client_allowDataTransclusion}
Switch to enable data transclusion features like the ```{{#property}}``` parser function and the `wikibase` [Scribunto] module.

DEFAULT: ```true```

#### allowLocalShortDesc
Switch to enable local override of the central description with `{{SHORTDESC:}}`.

DEFAULT: ```false```

#### forceLocalShortDesc
Switch to force local override of the central description with `{{SHORTDESC:}}`. Requires `allowLocalShortDesc` to be enabled.

DEFAULT: ```false```

#### allowArbitraryDataAccess {#client_allowArbitraryDataAccess}
Switch to allow accessing arbitrary items from the `wikibase` [Scribunto] module and the via the parser functions (instead of just the item which is linked to the current page).

DEFAULT: ```true```

#### allowDataAccessInUserLanguage
Switch to allow accessing data in the user's language rather than the content language from the `wikibase` [Scribunto] module and the via the parser functions.

Useful for multilingual wikis
Allows users to split the ParserCache by user language.

DEFAULT: ```false```

#### disabledAccessEntityTypes
List of entity types that access to them in the client should be disabled.

DEFAULT: ```[]```

#### entityAccessLimit
Limit for the number of different full entities that can be loaded on any given page, via [Scribunto] or the property parser function.

DEFAULT: ```250```

#### referencedEntityIdAccessLimit
Maximum number of calls to `mw.wikibase.getReferencedEntityId` allowed on a single page.

#### referencedEntityIdMaxDepth
Maximum search depth for referenced entities in `mw.wikibase.getReferencedEntityId`.

#### referencedEntityIdMaxReferencedEntityVisits
Maximum number of entities to visit in a `mw.wikibase.getReferencedEntityId` call.

#### trackLuaFunctionCallsPerSiteGroup
Whether to track Lua function calls with a per-sitegroup key, like `MediaWiki.wikipedia.wikibase.client.scribunto.wikibase.functionName.call`.

#### trackLuaFunctionCallsPerWiki
Whether to track Lua function calls with a per-site key, like `MediaWiki.dewiki.wikibase.client.scribunto.wikibase.functionName.call`.

### Sitelinks

#### languageLinkSiteGroup
ID of the site group to be shown as language links.

DEFAULT: `null` (That is the site's own site group.)

#### languageLinkAllowedSiteGroups
List of allowed group of sitelinks to be shown as language links.
For example for Wikimedia Commons, this can be `commons` and `wikipedia`.

DEFAULT: `null` (Meaning value of languageLinkSiteGroup will be the only allowed group)

#### badgeClassNames
A list of additional CSS class names for site links that have badges.

The array has to consist of serialized item IDs pointing to their CSS class names, like ```['Q101' => 'badge-goodarticle']```.
Note that this extension does not add any CSS to actually display the badges.

#### otherProjectsLinks
Site global ID list of sites which should be linked in the other project's sidebar section.

Empty value will suppress this section.

DEFAULT: Everything in the Wikibase [siteLinkGroups] setting.

### Recent Changes

#### injectRecentChanges {#client_injectRecentChanges}
Whether changes on the repository should be injected into this wiki's recent changes table, so they show up on watchlists, etc.

Requires the dispatchChanges.php script to run, and this wiki to be listed in the [localClientDatabases] setting on the repository.
See @ref docs_topics_change-propagation

#### showExternalRecentChanges
Whether changes on the repository should be displayed on Special:RecentChanges, Special:Watchlist, etc on the client wiki.

In contrast to [injectRecentChanges], this setting just removes the changes from the user interface.
This is intended to temporarily prevent external changes from showing in order to find or fix some issue on a live site.

DEFAULT: ```true```

#### recentChangesBatchSize {#client_recentChangesBatchSize}
Number of `recentchanges` table rows to create in each InjectRCRecordsJob, a job used to send client wikis notifications about relevant changes to entities.

Higher value mean fewer jobs but longer run-time per job.

DEFAULT: [wikiPageUpdaterDbBatchSize], for backwards compatibility, or MediaWiki core's [$wgUpdateRowsPerJob], which currently defaults to 300.

### Echo

#### sendEchoNotification
If true, allows users on the client wiki to get a notification when a page they created is connected to a repo item.

This requires the [Echo] extension.

#### echoIcon
If `sendEchoNotification` is set to `true`, you can also provide what icon the user will see.

The correct syntax is ```[ 'url' => '...' ]``` or ```[ 'path' => '...' ]``` where `path` is relative to [$wgExtensionAssetsPath].

DEFAULT: ```false``` (That is there will be the default Echo icon.)

### Data Bridge

#### dataBridgeEnabled {#client_dataBridgeEnabled}
Enables the Data Bridge Feature, which allows editing a repository directly from a client wiki.

To enable it, set this setting to `true` on both repo and client and also configure [dataBridgeHrefRegExp].

DEFAULT: ```false```

#### dataBridgeHrefRegExp {#client_dataBridgeHrefRegExp}
Regular expression to match edit links for which the Data Bridge is enabled.

Uses JavaScript syntax, with the first capturing group containing the title of the entity, the second one containing the entity ID (usually a part of the first capturing group) and the third one containing the property ID to edit.
Mandatory if [client dataBridgeEnabled] is set to `true` – there is no default value.

####dataBridgeEditTags {#client_dataBridgeEditTags}
A list of tags for tracking edits through the Data Bridge.

Optional if [client dataBridgeEnabled] is set to `true`, with a default value of ```[]```.
Please note: you also have to create those tags in the target repository via Special:Tags.

#### dataBridgeIssueReportingLink
The URL for link to where the users can report errors with the Data Bridge.

It may have a `<body>` placeholder which will be replaced with some text containing more information about the error.

DEFAULT: `https://phabricator.wikimedia.org/maniphest/task/edit/form/1/?title=Wikidata+Bridge+error&description=${body}&tags=Wikidata-Bridge`

### Tags

These settings define [change tags][Help:Tags] that should be added to different edits.
All of them default to the empty list, meaning that no tags are added by default;
when you configure them, you also have to create those tags on the target repository via Special:Tags.

#### linkItemTags
List of tags to be added to edits made via the sitelink management UI (linking a page to another page).
Due to caching, changes to this setting may take up to a day to take effect.

DEFAULT: `[]`

See also these related settings:
- [dataBridgeEditTags]
- [updateRepoTags]

### Miscellaneous

#### itemAndPropertySourceName
Name of the providing Item and Property definitions (data is used from here, including sitelinks).

Must match the name of the entity source as defined in [entitySources] setting.

This setting is intended to be used by Wikibase installations with complex setups which have multiple repos attached.

The entity source named by this setting must be a database entity source (i.e. its `type` must be `db`).
If its `repoDatabase` is a string, that string must also be a site global ID for the repository wiki;
otherwise, [recent changes injection][injectRecentChanges] will not work.

DEFAULT: ```local```

#### propagateChangesToRepo
Switch to enable or disable the propagation of client changes to the repo.

DEFAULT: ```true```

#### entityUsagePerPageLimit
If a page in client uses too many aspects and entities, Wikibase issues a warning.

This setting determines value of that threshold.

DEFAULT: ```100```

#### pageSchemaNamespaces
An array of client namespace ids defaulting to empty (disabled)

Pages with a matching namespace will include a JSON-LD schema script for search engine optimization (SEO).

#### entitySchemaNamespace
Namespace id for entity schema data type

DEFAULT: ```640```

#### disabledUsageAspects
Array of usage aspects that should not be saved in the [wbc_entity_usage] table.

This supports aspect codes (like “T”, “L” or “X”), but not full aspect keys (like “L.de”).
For example ```[ 'D', 'C' ]``` can be used to disable description and statement usages.
A replacement usage type can be given in the form of ```[ 'usage-type-to-replace' => 'replacement' ]```.

#### wikiPageUpdaterDbBatchSize {#client_wikiPageUpdaterDbBatchSize}
DEPRECATED. If set, acts as a default for [purgeCacheBatchSize] and [recentChangesBatchSize].

#### purgeCacheBatchSize {#client_purgeCacheBatchSize}
Number of pages to process in each HTMLCacheUpdateJob, a job used to send client wikis notifications about relevant changes to entities.

A Higher value means fewer jobs but longer run-time per job.

DEFAULT: [wikiPageUpdaterDbBatchSize] (for backwards compatibility) or MediaWiki core's [$wgUpdateRowsPerJob] (which currently defaults to 300).

#### entityUsageModifierLimits
Associative array mapping usage type to the limit.

If number of modifiers for the given aspect of an entity passes this limit, it turns all modifiers to a general entity usage in the given aspect.
This is useful when with bad lua, a page in client uses all languages or statements in the repo causing the wbc_entity_usage become too big.

#### addEntityUsagesBatchSize
Batch size for adding entity usage records.

DEFAULT: ```500```

#### wellKnownReferencePropertyIds
Associative array mapping certain well-known property roles to the IDs of the properties fulfilling those roles.

When formatting references (currently, only for Data Bridge), a few properties are treated specially.
In this setting, those can be specified:
the keys `referenceUrl`, `title`, `statedIn`, `author`, `publisher`, `publicationDate` and `retrievedDate`
correspond to the Wikidata properties [reference URL], [title], [stated in], [author], [publisher], [publication date] and [retrieved] respectively.
Each property is optional.

DEFAULT: array mapping each well-known name to `null`.

[$wgDBname]: https://www.mediawiki.org/wiki/Manual:$wgDBname
[$wgMainCacheType]: https://www.mediawiki.org/wiki/Manual:$wgMainCacheType
[$wgMaxArticleSize]: https://www.mediawiki.org/wiki/Manual:$wgMaxArticleSize
[$wgRightsUrl]: https://www.mediawiki.org/wiki/Manual:$wgRightsUrl
[$wgRightsText]: https://www.mediawiki.org/wiki/Manual:$wgRightsText
[$wgDBDefaultGroup]: https://www.mediawiki.org/wiki/Manual:$wgDBDefaultGroup
[$wgLanguageCode]: https://www.mediawiki.org/wiki/Manual:$wgLanguageCode
[$wgSitename]: https://www.mediawiki.org/wiki/Manual:$wgSitename
[$wgServer]: https://www.mediawiki.org/wiki/Manual:$wgServer
[$wgUpdateRowsPerJob]: https://www.mediawiki.org/wiki/Manual:$wgUpdateRowsPerJob
[$wgCdnMaxAge]: https://www.mediawiki.org/wiki/Manual:$wgCdnMaxAge
[$wgExtensionAssetsPath]: https://www.mediawiki.org/wiki/Manual:$wgExtensionAssetsPath
[$wgRCMaxAge]: https://www.mediawiki.org/wiki/Manual:$wgRCMaxAge
[$wgScriptPath]: https://www.mediawiki.org/wiki/Manual:$wgScriptPath
[$wgArticlePath]: https://www.mediawiki.org/wiki/Manual:$wgArticlePath
[GeoData]: https://www.mediawiki.org/wiki/Extension:GeoData
[Echo]: https://www.mediawiki.org/wiki/Extension:Echo
[ObjectFactory]: https://www.mediawiki.org/wiki/ObjectFactory
[page property]: https://www.mediawiki.org/wiki/Manual:Page_props_table
[Scribunto]: (https://www.mediawiki.org/wiki/Scribunto)
[Help:Tags]: https://www.mediawiki.org/wiki/Special:MyLanguage/Help:Tags
[siteLinkGroups]: #common_siteLinkGroups
[entitySources]: #common_entitySources
[sharedCacheKeyPrefix]: #common_sharedCacheKeyPrefix
[termboxEnabled]: #repo_termboxEnabled
[updateRepoTags]: #repo_updateRepoTags
[client dataBridgeEnabled]: #client_dataBridgeEnabled
[dataBridgeHrefRegExp]: #client_dataBridgeHrefRegExp
[dataBridgeEditTags]: #client_dataBridgeEditTags
[injectRecentChanges]: #client_injectRecentChanges
[localClientDatabases]: #client_localClientDatabases
[recentChangesBatchSize]: #client_recentChangesBatchSize
[purgeCacheBatchSize]: #client_purgeCacheBatchSize
[wikiPageUpdaterDbBatchSize]: #client_wikiPageUpdaterDbBatchSize
[siteGlobalID]: #client_siteGlobalID
[entitysources topic]: @ref docs_topics_entitysources
[wbc_entity_usage]: @ref docs_sql_wbc_entity_usage
[reference URL]: https://www.wikidata.org/wiki/Property:P854
[title]: https://www.wikidata.org/wiki/Property:P1476
[stated in]: https://www.wikidata.org/wiki/Property:P248
[author]: https://www.wikidata.org/wiki/Property:P50
[publisher]: https://www.wikidata.org/wiki/Property:P123
[publication date]: https://www.wikidata.org/wiki/Property:P577
[retrieved]: https://www.wikidata.org/wiki/Property:P813
