# Services

### Top level service factories

The [WikibaseRepo] and [WikibaseClient] classes are the top two services factories for the main Wikibase components.

Instead of constructing services where you need them, you should grab then via the top level factory singleton and inject them into your class.

For example:

```php
WikibaseRepo::getDefaultInstance()->getEntityContentFactory()
```

These top level singletons have existed since the early days of Wikibase.

## Data access

The Data access services, [SingleEntitySource & MultipleEntitySource Services](#services_section_entitysource), can be retrieved
from the top level factories with the following methods.

 - [WikibaseRepo::getWikibaseServices()]
 - [WikibaseClient::getWikibaseServices()]

### SingleEntitySource & MultipleEntitySource Services {#services_section_entitysource}

In 2019 to implement database based [federation] for Wikimedia Commons more service containers were introduced with the plan of removing the previous set.

New service containers were created as the previous set no longer met the requirements of the initial stage of federation that was being targeted.

 - [MultipleEntitySourceServices] - Top-level container/factory of data access services implementing [WikibaseServices]
   - [GenericServices]
   - [SingleEntitySourceServices] - Services for a single entity source

Wiring for EntitySource based service containers is created from the [EntitySourceDefinitions] which is generated from:
 - [WikibaseRepo::getDefaultEntityTypes()]
   - WikibaseLib.entitytypes.php
   - WikibaseRepo.entitytypes.php
 - The [WikibaseRepoEntityTypes] hook

[federation]: @ref md_docs_topics_federation
[EntitySourceDefinitions]: @ref Wikibase::DataAccess::EntitySourceDefinitions
[WikibaseServices]: @ref Wikibase::DataAccess::WikibaseServices
[GenericServices]: @ref Wikibase::DataAccess::GenericServices
[MultipleEntitySourceServices]: @ref Wikibase::DataAccess::MultipleEntitySourceServices
[SingleEntitySourceServices]: @ref Wikibase::DataAccess::SingleEntitySourceServices
[WikibaseRepo]: @ref Wikibase::Repo::WikibaseRepo
[WikibaseRepo::getWikibaseServices()]: @ref Wikibase::Repo::WikibaseRepo::getWikibaseServices()
[WikibaseRepo::getDefaultEntityTypes()]: @ref Wikibase::Repo::WikibaseRepo::getDefaultEntityTypes()
[WikibaseClient]: @ref Wikibase::Client::WikibaseClient
[WikibaseClient::getWikibaseServices()]: @ref Wikibase::Client::WikibaseClient::getWikibaseServices()
[WikibaseRepoEntityTypes]: @ref WikibaseRepoEntityTypes
