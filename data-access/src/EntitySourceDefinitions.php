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
		//return $this->sources[$entityType];
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
