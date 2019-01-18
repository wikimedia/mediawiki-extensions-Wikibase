<?php

namespace Wikibase\DataAccess;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitions {

	/**
	 * @var EntitySource[]
	 */
	private $sources;

	public function __construct( array $sources ) {
		// TODO: assert there is no mulitple source providing the same entity type (illegal as of Jan 2018)
		$this->sources = $sources;
	}

	/**
	 * @return EntitySource[]
	 */
	public function getSources() {
		$sourceMap = [];
		foreach ( $this->sources as $source ) {
			$sourceMap[$source->getSourceName()] = $source;
		}
		return $sourceMap;
	}

	public function getSourceForEntityType( $entityType ) {
		// TODO: when the same entity type can be provided by multitple source (currently forbidden),
		// this should return all sources
		foreach ( $this->sources as $source ) {
			if ( in_array( $entityType, $source->getEntityTypes() ) ) {
				return $source;
			}
		}

		return null;
	}

	public function getEntityTypeToSourceMapping() {
		$mapping = [];
		foreach ( $this->sources as $source ) {
			$entityTypes = $source->getEntityTypes();
			foreach ( $entityTypes as $type ) {
				$mapping[$type] = $source;
			}
		}
		return $mapping;
	}

	//
}
