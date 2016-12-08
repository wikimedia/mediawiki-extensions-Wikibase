<?php

namespace Wikibase\Client;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0+
 */
class DispatchingServiceFactory extends ServiceContainer {

	/**
	 * @var RepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	public function __construct( WikibaseClient $client ) {
		parent::__construct();

		$this->initRepositoryServiceContainers( $client );
	}

	private function initRepositoryServiceContainers( WikibaseClient $client ) {
		$repositoryNames = array_merge(
			[ '' ],
			array_keys( $client->getSettings()->getSetting( 'foreignRepositories' ) )
		);

		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			$client->getEntityIdParser(),
			$client->getIdPrefixMapping()
		);
		$dataValueDeserializerFactory = new RepositorySpecificDataValueDeserializerFactory( $idParserFactory );

		foreach ( $repositoryNames as $repositoryName ) {
			$container = new RepositoryServiceContainer(
				$this->getRepositoryDatabaseName( $repositoryName, $client->getSettings() ),
				$repositoryName,
				$idParserFactory->getIdParser( $repositoryName ),
				$dataValueDeserializerFactory->getDeserializer( $repositoryName ),
				$client
			);
			$container->loadWiringFiles( $client->getSettings()->getSetting( 'repositoryServiceWiringFiles' ) );

			$this->repositoryServiceContainers[$repositoryName] = $container;
		}
	}

	/**
	 * @param string $repositoryName
	 * @param SettingsArray $settings
	 *
	 * @return string|false
	 */
	private function getRepositoryDatabaseName( $repositoryName, SettingsArray $settings ) {
		if ( $repositoryName === '' ) {
			return $settings->getSetting( 'repoDatabase' );
		}

		$foreignRepoSettings = $settings->getSetting( 'foreignRepositories' );
		return $foreignRepoSettings[$repositoryName]['repoDatabase'];
	}

	/**
	 * @param string $service
	 * @return array An associative array mapping repository names to service instances configured for the repository
	 */
	public function getServiceMap( $service ) {
		$serviceMap = [];
		foreach ( $this->repositoryServiceContainers as $repositoryName => $container ) {
				$serviceMap[$repositoryName] = $container->getService( $service );
		}
		return $serviceMap;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getService( 'EntityRevisionLookup' );
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getService( 'TermBuffer' );
	}

}
