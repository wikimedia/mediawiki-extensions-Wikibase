<?php

namespace Wikibase\Client;

use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;
use Wikibase\SettingsArray;

/**
 * TODO: rename the class? it is not really a dispatching service factory in its current form
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactory {

	private $repositoryNames;

	/**
	 * @var RepositorySpecificEntityRevisionLookupFactory
	 */
	private $entityRevisionLookupFactory;

	/**
	 * @var RepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityNamespaceLookup $entityNamespaceLookup,
		array $deserializerFactoryCallbacks,
		SettingsArray $clientSettings
	) {
		$entityIdParserFactory = new PrefixMappingEntityIdParserFactory( $entityIdParser, [] ); // TODO: read mapping from settings
		$this->entityRevisionLookupFactory = new RepositorySpecificEntityRevisionLookupFactory(
			$entityIdParserFactory,
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			new RepositorySpecificDataValueDeserializerFactory( $entityIdParserFactory ),
			$deserializerFactoryCallbacks,
			$entityNamespaceLookup,
			$clientSettings->getSetting( 'maxSerializedEntitySize' ) * 1024,
			$this->getDatabaseNameMap( $clientSettings )
		);

		$this->repositoryNames = array_merge(
			[ '' ],
			array_keys( $clientSettings->getSetting( 'foreignRepositories' ) )
		);

		$this->initRepositoryServiceContainers( $clientSettings );
	}

	/**
	 * @param SettingsArray $settings
	 * @return string[]
	 */
	private function getDatabaseNameMap( SettingsArray $settings ) {
		$names = [ '' => $settings->getSetting( 'repoDatabase' ) ];

		$foreignRepositorySettings = $settings->getSetting( 'foreignRepositories' );
		foreach ( $foreignRepositorySettings as $repositoryName => $repositorySettings ) {
			$names[$repositoryName] = $repositorySettings['repoDatabase'];
		}

		return $names;
	}

	private function initRepositoryServiceContainers( SettingsArray $settings ) {
		foreach ( $this->repositoryNames as $repositoryName ) {
			$this->repositoryServiceContainers[$repositoryName] = new RepositoryServiceContainer(
				$repositoryName,
				$settings->getSetting( 'repositoryServiceWiringFiles' ),
				[ $this->entityRevisionLookupFactory ]
			);
		}
	}

	public function getServiceMap( $service ) {
		$serviceMap = [];
		foreach ( $this->repositoryNames as $repositoryName ) {
			$serviceMap[$repositoryName] = $this->repositoryServiceContainers[$repositoryName]->getService( $service );
		}
		return $serviceMap;
	}

}
