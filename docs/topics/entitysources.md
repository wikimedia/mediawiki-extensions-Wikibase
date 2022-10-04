# Entity sources

In 2019 [EntitySource] and [EntitySourceDefinitions] were introduced for defining the source of an entity type.

Currently only a single entity source is allowed per entity type on a given repo.

This concept was introduced in 2019 to allow for the first steps of [federation].

Entity source configuration is controlled by a configuration option called `entitySources` in both repo and client.

## Configuration

An entitysource is an associative array mapping entity source names to settings relevant to the particular source.

DEFAULT: None, must be configured.
The _example_ (not default!) settings configure a local entity source with items in namespace 120, properties in namespace 122, and extension-defined entity types in namespaces according to the `WikibaseRepoEntityNamespaces` hook.
Custom (non-example) settings will not include extension-defined entity types by default (the `WikibaseRepoEntityNamespaces` hook is only run by the example settings), all entity types must be configured explicitly in that case.

Configuration of each source is an associative array containing the following keys:

 - `entityNamespaces`: A map of entity type identifiers (strings) that the local wiki supports from the foreign repository to namespaces IDs related to pages of entities of the given type on foreign repository's wiki. If entities are stored in alternative slots, the syntax ```<namespaceId>/<slot>``` can be used to define which slot to use.
 - `repoDatabase`: A symbolic database identifier (string) that MediaWiki's LBFactory class understands. `false` means “this wiki's database”. If you set this to a string, it’s a good idea to ensure that it’s also valid site global ID for the corresponding wiki; in particular, this is required for the source which `itemAndPropertySourceName` refers to.
 - `baseUri`: A base URI (string) for concept URIs. It should contain scheme and authority part of the URI.
 - `interwikiPrefix`: An interwiki prefix configured in the local wiki referring to the wiki related to the entity source.
 - `rdfNodeNamespacePrefix`: A prefix used in RDF turtle node namespaces, e.g. 'wd' would result in namespaces like 'wd' for the entity namespace, and 'wdt' for the direct claim namespace, whereas 'sdc' prefix would result in the namespaces 'sdc' and 'sdct' accordingly.
 - `rdfPredicateNamespacePrefix`: A prefix used in RDF turtle predicate namespaces, e.g. '' would result in namespaces like 'ps' for the simple value claim namespace, whereas 'sdc' prefix would result in the namespace 'sdcps'.
 - `type`: Type of source of the entities. It can be `db` if the entity source is a local database and `api` if the entity is from a federated source. see [Federated Properties]. The default value is `db`.

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
        'type' => 'db'
    ],
];
$wgWBRepoSettings['entitySources'] = $entitySources;
$wgWBRepoSettings['localEntitySourceName'] = 'myrepo';
$wgWBClientSettings['entitySources'] = $entitySources;
$wgWBClientSettings['itemAndPropertySourceName'] = 'myrepo';
```

Note that additional entity types defined by extensions will not work out of the box in this configuration:
you must explicitly configure them in the `entityNamespaces` of the entity source.

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
$wgWBRepoSettings['localEntitySourceName'] = $isThisCommons ? 'commons' : 'wikidata';
$wgWBClientSettings['entitySources'] = $entitySources;
$wgWBClientSettings['itemAndPropertySourceName'] = 'wikidata';
```

[federation]: @ref docs_topics_federation
[Federated Properties]: @ref docs_components_repo-federated-properties
[client repo relationship]: @ref docs_topics_repo-client-relationship
[EntitySource]: @ref Wikibase::DataAccess::EntitySource
[EntitySourceDefinitions]: @ref Wikibase::DataAccess::EntitySourceDefinitions
[EntitySourceDefinitionsConfigParser]: @ref Wikibase::Repo::EntitySourceDefinitionsConfigParser
