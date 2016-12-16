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
 * A factory/locator of services dispatching the action to services configured for the
 * particular input, based on the repository the particular input entity belongs to.
 * Dispatching services provide a way of using entities from multiple repositories.
 *
 * Services are defined by loading a wiring array(s), or by using defineService method.
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactory extends ServiceContainer {

	/**
	 * @var RepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	/**
	 * FIXME: injecting of the top-level factory (WikibaseClient) here is only a temporary solution.
	 * This class uses top-level factory to access settings and several services provided by the top-level
	 * factory. Also, the instance of the top-level factory is being passed to instantiators of services
	 * stored in the RepositoryServiceContainer in order to get service they depend on.
	 *
	 * This approach is not clean, the class should not depend on the top-level factory.
	 * This should be changed after some refactoring: this factory should be able to instantiate
	 * services it now gets from the client. Config should be also passed in properly, without
	 * a need to inject WikibaseClient instance to access relevant settings. Instantiators
	 * in RepositoryServiceWiring should rather be getting some other service container, not the
	 * whole top-level factory.
	 *
	 * @param WikibaseClient $client
	 */
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
			$client->getEntityIdParser(), // TODO: this should be moved to this class; see T153427
			$this->getIdPrefixMaps( $client->getSettings()->getSetting( 'foreignRepositories' ) )
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
	 * Returns a map of id prefix mappings defined for configured foreign repositories.
	 * @return array Associative array mapping repository names to repository-specific prefix mapping
	 */
	private function getIdPrefixMaps( array $settings ) {
		$mappings = [];
		foreach ( $settings as $repositoryName => $repositorySettings ) {
			if ( array_key_exists( 'prefixMapping', $repositorySettings ) ) {
				$mappings[$repositoryName] = $repositorySettings['prefixMapping'];
			}
		}
		return $mappings;
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
