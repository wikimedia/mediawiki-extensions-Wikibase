<?php

namespace Wikibase\Client\Store;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Client\WikibaseClient;

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
	 * @param WikibaseClient $client Top-level factory passed to service instantiators
	 * @param string[] $serviceWiringFiles
	 */
	public function __construct( $repositoryName, WikibaseClient $client, array $serviceWiringFiles ) {
		parent::__construct( [ $client ] );

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
