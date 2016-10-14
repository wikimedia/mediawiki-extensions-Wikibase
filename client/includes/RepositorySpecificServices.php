<?php

namespace Wikibase\Client;

use Config;
use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;

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
	 * @return array
	 */
	public function getPrefixMapping() {
		$mapping = [];
		$foreignRepositorySettings = $this->config->get( 'ForeignRepositorySettings' );
		foreach ( $foreignRepositorySettings as $repositoryName => $settings ) {
			if ( array_key_exists( 'prefixMapping', $settings ) ) {
				$mapping[$repositoryName] = $settings['prefixMapping'];
			}
		}
		return $mapping;
	}

	/**
	 * @return string[]
	 */
	public function getDatabaseNames() {
		$names = [];
		$foreignRepositorySettings = $this->config->get( 'ForeignRepositorySettings' );
		foreach ( $foreignRepositorySettings as $repositoryName => $settings ) {
			$names[$repositoryName] = $settings['repoDatabase'];
		}
		return $names;
	}

	/**
	 * @return RepositorySpecificDataValueDeserializerFactory
	 */
	public function getDataValueDeserializerFactory() {
		return $this->getService( 'DataValueDeserializerFactory' );
	}

	/**
	 * @return EntityLookup
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
	 * @return PrefixMappingEntityIdParserFactory
	 */
	public function getPrefixMappingEntityIdParserFactory() {
		return $this->getService( 'PrefixMappingEntityIdParserFactory' );
	}

}
