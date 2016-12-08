<?php

namespace Wikibase\Client\Store;

use MediaWiki\Services\ServiceContainer;
use Wikibase\Client\WikibaseClient;

/**
 * @license GPL-2.0+
 */
class RepositoryServiceContainer extends ServiceContainer {

	/**
	 * @var string|false
	 */
	private $databaseName;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @param string|false $databaseName
	 * @param string $repositoryName
	 * @param WikibaseClient $client Top-level factory passed to service instantiators
	 * @param string[] $serviceWiringFiles
	 */
	public function __construct( $databaseName, $repositoryName, WikibaseClient $client, array $serviceWiringFiles ) {
		parent::__construct( [ $client ] );

		$this->databaseName = $databaseName;
		$this->repositoryName = $repositoryName;

		$this->loadWiringFiles( $serviceWiringFiles );
	}

	public function getRepositoryName() {
		return $this->repositoryName;
	}

	public function getDatabaseName() {
		return $this->databaseName;
	}

}
