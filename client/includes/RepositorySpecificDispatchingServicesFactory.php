<?php

namespace Wikibase\Client;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;
use Wikibase\Lib\Store\DispatchingBufferingTermLookup;

/**
 * @license GPL-2.0+
 */
class RepositorySpecificDispatchingServicesFactory {

	/**
	 * @var EntityLookup[]
	 */
	private $entityLookups = [];

	private $entityRevisionLookups = [];

	private $termLookups = [];

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	public function __construct( array $config, EntityIdParser $idParser ) {
		if ( array_key_exists( 'entityLookups', $config ) ) {
			$this->entityLookups = $config['entityLookups'];
		}
		if ( array_key_exists( 'entityRevisionLookups', $config ) ) {
			$this->entityRevisionLookups = $config['entityRevisionLookups'];
		}
		if ( array_key_exists( 'termLookups', $config ) ) {
			$this->termLookups = $config['termLookups'];
		}
		$this->idParser = $idParser;
	}

	public function getEntityLookup() {
		return new DispatchingEntityLookup( $this->entityLookups, $this->idParser );
	}

	public function getEntityRevisionLookup() {
		return new DispatchingEntityRevisionLookup( $this->entityRevisionLookups );
	}

	public function getTermLookup() {
		return new DispatchingBufferingTermLookup( $this->termLookups );
	}

}
