<?php

namespace Wikibase\Client;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\SettingsArray;

/**
 * @license GPL-2.0+
 */
class DispatchingServiceFactory extends ServiceContainer {

	private $repositoryNames;

	/**
	 * @var RepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	public function __construct( WikibaseClient $client, array $wiringFiles ) {
		parent::__construct();

		$this->repositoryNames = array_merge(
			[ '' ],
			array_keys( $client->getSettings()->getSetting( 'foreignRepositories' ) )
		);

		$this->initRepositoryServiceContainers( $client );

		$this->loadWiringFiles( $wiringFiles );
	}

	private function initRepositoryServiceContainers( WikibaseClient $client ) {
		foreach ( $this->repositoryNames as $repositoryName ) {
			$this->repositoryServiceContainers[$repositoryName] = new RepositoryServiceContainer(
				$this->getRepositoryDatabaseName( $repositoryName, $client->getSettings() ),
				$repositoryName,
				$client,
				$client->getSettings()->getSetting( 'repositoryServiceWiringFiles' )

			);
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
		foreach ( $this->repositoryNames as $repositoryName ) {
			if ( array_key_exists( $repositoryName, $this->repositoryServiceContainers ) ) {
				$serviceMap[$repositoryName] = $this->repositoryServiceContainers[$repositoryName]->getService( $service );
			}
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
