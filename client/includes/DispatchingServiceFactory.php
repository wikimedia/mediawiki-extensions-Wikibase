<?php

namespace Wikibase\Client;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * A factory/locator of services dispatching the action to services configured for the
 * particular input, based on the repository the particular input entity belongs to.
 * Dispatching services provide a way of using entities from multiple repositories.
 *
 * Services are defined by loading a wiring array(s), or by using defineService method.
 *
 * @license GPL-2.0+
 */
class DispatchingServiceFactory extends ServiceContainer implements EntityDataRetrievalServiceFactory {

	/**
	 * @var string[]
	 */
	private $repositoryNames;

	/**
	 * @var RepositoryServiceContainerFactory
	 */
	private $repositoryServiceContainerFactory;

	/**
	 * @var RepositoryServiceContainer[]
	 */
	private $repositoryServiceContainers = [];

	/**
	 * @param RepositoryServiceContainerFactory $repositoryServiceContainerFactory
	 * @param string[] $repositoryNames
	 */
	public function __construct(
		RepositoryServiceContainerFactory $repositoryServiceContainerFactory,
		array $repositoryNames
	) {
		parent::__construct();

		$this->repositoryServiceContainerFactory = $repositoryServiceContainerFactory;
		$this->repositoryNames = $repositoryNames;
	}

	/**
	 * @param string $service
	 * @return array An associative array mapping repository names to service instances configured for the repository
	 */
	public function getServiceMap( $service ) {
		$serviceMap = [];
		foreach ( $this->repositoryNames as $repositoryName ) {
			$container = $this->getContainerForRepository( $repositoryName );
			if ( $container !== null ) {
				$serviceMap[$repositoryName] = $container->getService( $service );
			}
		}
		return $serviceMap;
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return RepositoryServiceContainer|null
	 */
	private function getContainerForRepository( $repositoryName ) {
		if ( !array_key_exists( $repositoryName, $this->repositoryServiceContainers ) ) {
			try {
				$this->repositoryServiceContainers[$repositoryName] =
					$this->repositoryServiceContainerFactory->getContainer( $repositoryName );
			} catch ( UnknownForeignRepositoryException $exception ) {
				$this->repositoryServiceContainers[$repositoryName] = null;
			}
		}

		return $this->repositoryServiceContainers[$repositoryName];
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getService( 'EntityRevisionLookup' );
	}

	/**
	 * @return PropertyInfoLookup
	 */
	public function getPropertyInfoLookup() {
		return $this->getService( 'PropertyInfoLookup' );
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getService( 'TermBuffer' );
	}

}
