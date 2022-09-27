# Federation

Federation here means accessing the entities of one Wikibase Repository from another Wikibase Repository.

* “Foreign” is used to mean that something refers to and comes from another Wikibase repository.
* Foreign EntityIds and mappings are documented in the file docs/foreign-entity-ids.wiki in the wikibase/data-model component.

As of March 2017:
 - In order to enable access to entities from federated repositories both Repo and Client components must be enabled.
 - Accessing data of foreign entities relies on the shared database access (databases of federated repositories must be in the same database cluster).

----

2020 will add API (instead of DB) based federation as described above (one entity source per entity type).

## Configuration

* A Wikibase Repository is configured as documented in @ref docs_topics_options.
* In order for federation to work foreign sources must be configured using the `entitySources` setting.

To see an example please look at the dedicated [entitysources] topic.

[options documentation]: @ref docs_topics_options
[entitysources]: @ref docs_topics_entitysources
