# Services {#topic_services}

### Top level service factories

The [WikibaseRepo] and [WikibaseClient] classes are the top two services factories for the main Wikibase components.

Instead of constructing services where you need them, you should grab then via the top level factory singleton and inject them into your class.

For example:

```php
WikibaseRepo::getDefaultInstance()->getEntityContentFactory()
```

These top level singletons have existed since the early days of Wikibase.

## Data access

The Data access services can be retrieved from the top level factories with the following methods.

 - [WikibaseRepo::getWikibaseServices()]
 - [WikibaseClient::getWikibaseServices()]

Data access service containers in Wikibase are currently undergoing a transition.
 - From: [MultiRepository & PerRepository Service containers (Legacy)](#services_section_repo)
 - To: [SingleEntitySource & MultipleEntitySource Services (2019)](#services_section_entitysource)

This transition is controlled by [useEntitySourceBasedFederation] and soon the default will be the EntitySource based service containers.

See: https://phabricator.wikimedia.org/T241975

### MultiRepository & PerRepository Service containers (Legacy) {#services_section_repo}

In 2016 MediaWiki core started using [Dependency injection](https://doc.wikimedia.org/mediawiki-core/master/php/md_docs_Injection.html),
and as a part of that a ServiceContainer was created in MediaWiki core.

This service container then started being used in Wikibase by the data-access component, for future use with [federation], with the creation of the following service container structure:

 - [MultipleRepositoryAwareWikibaseServices] - Top-level container/factory of data access services implementing [WikibaseServices]
   - [GenericServices] - Provides the non repo or entity specific services for a [WikibaseServices] implementation.
   - [MultiRepositoryServices] - Repo or entity specific services for a [WikibaseServices] implementation.
     - [PerRepositoryServiceContainerFactory] - Factory for [PerRepositoryServiceContainer]s for a single repository.

NOTE: In recent work it was realized that perhaps some services provided by [GenericServices] are not that generic
and should also be provided PerRepository. This is yet to be investigated fully in https://phabricator.wikimedia.org/T205268

Per-repository and multi-repository services are defined using wiring files.
They are specified using the following global variables (each being an array of file paths).
Extensions can register their custom services by adding their files to those globals in their [extension.json file].

 - [wgWikibaseMultiRepositoryServiceWiringFiles]
 - [wgWikibasePerRepositoryServiceWiringFiles]

The defaults are:
 - MultiRepositoryServiceWiring.php
 - PerRepositoryServiceWiring.php

### SingleEntitySource & MultipleEntitySource Services (2019) {#services_section_entitysource}

In 2019 to implement database based [federation] for Wikimedia Commons more service containers were introduced with the plan of removing the previous set.

New service containers were created as the previous set no longer met the requirements of the initial stage of federation that was being targeted.

 - [MultipleEntitySourceServices] - Top-level container/factory of data access services implementing [WikibaseServices]
   - [GenericServices]
   - [SingleEntitySourceServices] - Services for a single entity source (perhaps comparable with [PerRepositoryServiceContainerFactory])

Wiring for EntitySource based service containers is created from the [EntitySourceDefinitions] which is generated from:
 - [WikibaseRepo::getDefaultEntityTypes()]
   - WikibaseLib.entitytypes.php
   - WikibaseRepo.entitytypes.php
 - The [WikibaseRepoEntityTypes] hook

[federation]: @ref md_docs_topics_federation
[EntitySourceDefinitions]: @ref Wikibase::DataAccess::EntitySourceDefinitions
[WikibaseServices]: @ref Wikibase::DataAccess::WikibaseServices
[GenericServices]: @ref Wikibase::DataAccess::GenericServices
[MultiRepositoryServices]: @ref Wikibase::DataAccess::MultiRepositoryServices
[PerRepositoryServiceContainer]: @ref Wikibase::DataAccess::PerRepositoryServiceContainer
[MultipleRepositoryAwareWikibaseServices]: @ref Wikibase::DataAccess::MultipleRepositoryAwareWikibaseServices
[PerRepositoryServiceContainerFactory]: @ref Wikibase::DataAccess::PerRepositoryServiceContainerFactory
[MultipleEntitySourceServices]: @ref Wikibase::DataAccess::MultipleEntitySourceServices
[SingleEntitySourceServices]: @ref Wikibase::DataAccess::SingleEntitySourceServices
[wgWikibaseMultiRepositoryServiceWiringFiles]: @ref wgWikibaseMultiRepositoryServiceWiringFiles
[wgWikibasePerRepositoryServiceWiringFiles]: @ref wgWikibasePerRepositoryServiceWiringFiles
[extension.json file]: https://www.mediawiki.org/wiki/Manual:Extension_registration
[useEntitySourceBasedFederation]: @ref common_useEntitySourceBasedFederation
[WikibaseRepo]: @ref Wikibase::Repo::WikibaseRepo
[WikibaseRepo::getWikibaseServices()]: @ref Wikibase::Repo::WikibaseRepo::getWikibaseServices()
[WikibaseRepo::getDefaultEntityTypes()]: @ref Wikibase::Repo::WikibaseRepo::getDefaultEntityTypes()
[WikibaseClient]: @ref Wikibase::Client::WikibaseClient
[WikibaseClient::getWikibaseServices()]: @ref Wikibase::Client::WikibaseClient::getWikibaseServices()
[WikibaseRepoEntityTypes]: @ref WikibaseRepoEntityTypes
