<?php

namespace Wikibase\Client\Store;

use MediaWiki\Services\ServiceContainer;

/**
 * @license GPL-2.0+
 */
class RepositoryServiceContainer extends ServiceContainer {

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param string $repositoryName
	 * @param string[] $serviceWiringFiles
	 * @param array $extraInstantiationParams
	 */
	public function __construct( $repositoryName, array $serviceWiringFiles, array $extraInstantiationParams ) {
		parent::__construct( $extraInstantiationParams );

		$this->repositoryName = $repositoryName;

		$this->loadWiringFiles( $serviceWiringFiles );
	}

	public function getRepositoryName() {
		return $this->repositoryName;
	}

	public function getEntityRevisionLookup() {
		$this->getService( 'EntityRevisionLookup' );
	}

}
