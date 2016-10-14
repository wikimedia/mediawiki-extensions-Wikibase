<?php

namespace Wikibase\Client;

use Config;
use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTermLookup;

/**
 * @license GPL-2.0+
 */
class RepositorySpecificServices extends ServiceContainer {

	/**
	 * @var WikibaseClient
	 */
	private $client;

	/**
	 * @var Config
	 */
	private $config;

	public function __construct( WikibaseClient $client, Config $config ) {
		parent::__construct();

		$this->client = $client;
		$this->config = $config;

		$this->loadWiringFiles( $config->get( 'ServiceWiringFiles' ) );
	}

	/**
	 * @return WikibaseClient
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return string[]
	 */
	public function getForeignRepositories() {
		return array_keys( $this->config->get( 'ForeignRepositorySettings' ) );
	}

	/**
	 * @return EntityLookup;
	 */
	public function getEntityLookup() {
		return $this->getService( 'EntityLookup' );
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() {
		return $this->getService( 'EntityRevisionLookup' );
	}

	/**
	 * @return EntityTermLookup
	 */
	public function getTermLookup() {
		return $this->getService( 'TermLookup' );
	}

}
