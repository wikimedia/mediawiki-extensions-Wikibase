# Entity sources

In 2019 [EntitySource] and [EntitySourceDefinitions] were introduced for defining the source of an entity type.

Currently only a single entity source is allowed per entity type on a given repo.

This concept was introduced in 2019 to allow for the first steps of [federation].

Entity source configuration is controlled by a configuration option called `entitySources` in both repo and client.

If you do not specifically set configuration some defaults will be generated for you by the following classes:
 - [EntitySourceDefinitionsConfigParser]
 - [EntitySourceDefinitionsLegacyRepoSettingsParser]
 - [EntitySourceDefinitionsLegacyClientSettingsParser]

## Configuration

An entitysource is an associative array mapping entity source names to settings relevant to the particular source.

DEFAULT: Populated with a local default from existing settings:
 - [entityNamespaces](#entityNamespaces)
 - [changesDatabase](#changesDatabase)
 - [conceptBaseUri](#conceptBaseUri)
And with foreign repos using the `foreignRepositories` setting. (@ref md_docs_topics_options)

Configuration of each source is an associative array containing the following keys:

 - `entityNamespaces`: A map of entity type identifiers (strings) that the local wiki supports from the foreign repository to namespaces IDs related to pages of entities of the given type on foreign repository's wiki. If entities are stored in alternative slots, the syntax ```<namespaceId>/<slot>``` can be used to define which slot to use.
 - `repoDatabase`: A symbolic database identifier (string) that MediaWiki's LBFactory class understands. Note that `false` would mean “this wiki's database”.
 - `baseUri`: A base URI (string) for concept URIs. It should contain scheme and authority part of the URI.
 - `interwikiPrefix`: An interwiki prefix configured in the local wiki referring to the wiki related to the entity source.
 - `rdfNodeNamespacePrefix`: A prefix used in RDF turtle node namespaces, e.g. 'wd' would result in namespaces like 'wd' for the entity namespace, and 'wdt' for the direct claim namespace, whereas 'sdc' prefix would result in the namespaces 'sdc' and 'sdct' accordingly.
 - `rdfPredicateNamespacePrefix`: A prefix used in RDF turtle predicate namespaces, e.g. '' would result in namespaces like 'ps' for the simple value claim namespace, whereas 'sdc' prefix would result in the namespace 'sdcps'.

### Single entity source example

This example can be used when setting up a simple [client repo relationship].

```php
$entitySources = [
    'myrepo' => [
        'entityNamespaces' => [ 'item' => 120, 'property' => 122 ],
        'repoDatabase' => 'myrepodb',
        'baseUri' => 'SOME_CONCEPTBASEURI',
        'interwikiPrefix' => 'SOME_INTERWIKI',
        'rdfNodeNamespacePrefix' => 'SOME_NODERDFPREFIX',
        'rdfPredicateNamespacePrefix' => 'SOME_PREDICATERDFPREFIX',
    ],
];
$wgWBRepoSettings['entitySources'] = $entitySources;
$wgWBClientSettings['entitySources'] = $entitySources;
```

### Wikimedia Commons & Wikidata example

The following example shows Wikimedia Commons using entities from Wikidata.org:

```php
$entitySources = [
    'wikidata' => [
        'entityNamespaces' => [
            'item' => 0,
            'property' => 120,
            'lexeme' => 146,
        ],
        'repoDatabase' => 'wikidatawiki',
        'baseUri' => 'http://www.wikidata.org/entity/',
        'rdfNodeNamespacePrefix' => 'wd',
        'rdfPredicateNamespacePrefix' => '',
        'interwikiPrefix' => 'd',
    ],
    'commons' => [
        'entityNamespaces' => [
            'mediainfo' => '6/mediainfo',
        ],
        'repoDatabase' => 'commonswiki',
        'baseUri' => 'https://commons.wikimedia.org/wiki/Special:EntityData/',
        'rdfNodeNamespacePrefix' => 'sdc',
        'rdfPredicateNamespacePrefix' => 'sdc',
        'interwikiPrefix' => 'c',
    ],
];
$wgWBRepoSettings['entitySources'] = $entitySources;
$wgWBClientSettings['entitySources'] = $entitySources;
```

[federation]: @ref md_docs_topics_federation
[client repo relationship]: @ref md_docs_topics_repo-client-relationship
[EntitySource]: @ref Wikibase::DataAccess::EntitySource
[EntitySourceDefinitions]: @ref Wikibase::DataAccess::EntitySourceDefinitions
[EntitySourceDefinitionsConfigParser]: @ref Wikibase::Repo::EntitySourceDefinitionsConfigParser
[EntitySourceDefinitionsLegacyRepoSettingsParser]: @ref Wikibase::Repo::EntitySourceDefinitionsLegacyRepoSettingsParser
[EntitySourceDefinitionsLegacyClientSettingsParser]: @ref Wikibase::Client::EntitySourceDefinitionsLegacyClientSettingsParser
