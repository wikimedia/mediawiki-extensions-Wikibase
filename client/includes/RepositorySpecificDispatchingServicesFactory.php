<?php

namespace Wikibase\Client;

use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\DispatchingTermLookup;
use Wikibase\Lib\Store\DispatchingEntityRevisionLookup;

/**
 * @license GPL-2.0+
 */
class RepositorySpecificDispatchingServicesFactory {

	private $entityLookups = [];

	private $entityRevisionLookups = [];

	private $termLookups = [];

	public function __construct( array $config ) {
		if ( array_key_exists( 'entityLookups', $config ) ) {
			$this->entityLookups = $config['entityLookups'];
		}
		if ( array_key_exists( 'entityRevisionLookups', $config ) ) {
			$this->entityRevisionLookups = $config['entityRevisionLookups'];
		}
		if ( array_key_exists( 'termLookups', $config ) ) {
			$this->termLookups = $config['termLookups'];
		}
	}

	public function getEntityLookup() {
		return new DispatchingEntityLookup( $this->entityLookups );
	}

	public function getEntityRevisionLookup() {
		return new DispatchingEntityRevisionLookup( $this->entityRevisionLookups );
	}

	public function getTermLookup() {
		return new DispatchingTermLookup( $this->termLookups );
	}

}
