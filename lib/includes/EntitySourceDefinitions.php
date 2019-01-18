<?php

namespace Wikibase\Lib;

// TODO: move to data access?
class EntitySourceDefinitions {

	/**
	 * @var EntitySource[]
	 */
	private $sources;

	public function __construct( array $sources ) {
		$this->sources = $sources;
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
