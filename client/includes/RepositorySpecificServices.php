<?php

namespace Wikibase\Client;

use Config;
use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RepositorySpecificEntityRevisionLookupFactory;

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
	 * @return EntityLookup[]
	 */
	public function getEntityLookups() {
		// TODO: should rather use some other method as it returns a repo name => service map, not a service
		return $this->getService( 'EntityLookups' );
	}

	/**
	 * @return EntityRevisionLookup[]
	 */
	public function getEntityRevisionLookups() {
		// TODO: should rather use some other method as it returns a repo name => service map, not a service
		return $this->getService( 'EntityRevisionLookups' );
	}

	/**
	 * @return RepositorySpecificEntityRevisionLookupFactory
	 */
	public function getRepositorySpecificEntityRevisionLookupFactory() {
		return new RepositorySpecificEntityRevisionLookupFactory(
			$this->getPrefixMappingEntityIdParserFactory(),
			new ForbiddenSerializer( 'Entity serialization is not supported on the client!' ),
			$this->getDataValueDeserializerFactory(),
			$this->getDeserializerFactoryCallbacks(),
			$this->client->getEntityNamespaceLookup(),
			$this->client->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024,
			$this->getDatabaseNames()
		);
	}

	/**
	 * @return PrefixMappingEntityIdParserFactory
	 */
	private function getPrefixMappingEntityIdParserFactory() {
		return new PrefixMappingEntityIdParserFactory(
			$this->client->getEntityIdParser(),
			$this->getPrefixMapping()
		);
	}

	/**
	 * @return string[]
	 */
	public function getRepositoryNames() {
		return array_merge( [ '' ], array_keys( $this->config->get( 'ForeignRepositorySettings' ) ) );
	}

	/**
	 * @return array
	 */
	private function getPrefixMapping() {
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
	private function getDatabaseNames() {
		$names = [ '' => $this->client->getSettings()->getSetting( 'repoDatabase' ) ];
		$foreignRepositorySettings = $this->config->get( 'ForeignRepositorySettings' );
		foreach ( $foreignRepositorySettings as $repositoryName => $settings ) {
			$names[$repositoryName] = $settings['repoDatabase'];
		}
		return $names;
	}

	/**
	 * @return RepositorySpecificDataValueDeserializerFactory
	 */
	private function getDataValueDeserializerFactory() {
		return new RepositorySpecificDataValueDeserializerFactory(
			$this->getPrefixMappingEntityIdParserFactory()
		);
	}

	/**
	 * @return callable[]
	 */
	private function getDeserializerFactoryCallbacks() {
		return [
			'item' => function( DeserializerFactory $deserializerFactory ) {
				return $deserializerFactory->newItemDeserializer();
			},
			'property' => function( DeserializerFactory $deserializerFactory ) {
				return $deserializerFactory->newPropertyDeserializer();
			},
		];
	}

}
