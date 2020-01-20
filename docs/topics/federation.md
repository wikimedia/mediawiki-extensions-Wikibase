# Federation

Federation here means accessing the entities of one Wikibase Repository from another Wikibase Repository.

* “Foreign” is used to mean that something refers to and comes from another Wikibase repository.
* Foreign EntityIds and mappings are documented in the file docs/foreign-entity-ids.wiki in the wikibase/data-model component.

As of March 2017:
 - In order to enable access to entities from federated repositories both Repo and Client components must be enabled.
 - Accessing data of foreign entities relies on the shared database access (databases of federated repositories must be in the same database cluster).

In 2019 [EntitySource] and [EntitySourceDefinitions] were introduced for defining the source of an entity type.
Currently only a single entity source is allowed per entity type on a given repo.

2020 will add API (instead of DB) based federation as described above (one entity source per entity type).

## Configuration

* A Wikibase Repository is configured as documented in @ref md_docs_topics_options.
* In order for federation to work:
  * [useEntitySourceBasedFederation] must be true.
  * Foreign sources must be configured using the [entitySources] setting.

### Example

The following basic example roughly shows Wikimedia Commons using entities from Wikidata.org:

```php
$entitySources = [
	'local' => [
		'repoDatabase' => 'commonswiki',
		'entityNamespaces' => [ 'mediainfo' => '6/mediainfo' ],
		'baseUri' => 'https://commons.wikimedia.org/wiki/Special:EntityData/',
	],
	'd' => [
		'repoDatabase' => 'wikidatawiki',
		'entityNamespaces' => [ 'item' => 0, 'property' => 120 ],
		'baseUri' => 'http://www.wikidata.org/entity/',
	],
];
$wgWBRepoSettings['entitySources'] = $entitySources;
$wgWBClientSettings['entitySources'] = $entitySources;
```

[options documentation]: @ref md_docs_topics_options
[entitySources]: @ref #common_entitySources
[useEntitySourceBasedFederation]: @ref #common_useEntitySourceBasedFederation
[wgWBRepoSettings]: @ref #wgWBRepoSettings
[wgWBClientSettings]: @ref #wgWBClientSettings
[EntitySource]: @ref Wikibase::DataAccess::EntitySource
[EntitySourceDefinitions]: @ref Wikibase::DataAccess::EntitySourceDefinitions
