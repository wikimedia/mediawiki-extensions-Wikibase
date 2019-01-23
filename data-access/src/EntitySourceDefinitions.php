<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitions {

	/**
	 * @var EntitySource[]
	 */
	private $sources;

	/**
	 * @param EntitySource[] $sources
	 */
	public function __construct( array $sources ) {
		Assert::parameterElementType( EntitySource::class, $sources, '$sources' );
		$this->assertNoMultipleSourcesForTheEntityType( $sources );
		$this->sources = $sources;
	}

	/**
	 * @param EntitySource[] $sources
	 */
	private function assertNoMultipleSourcesForTheEntityType( array $sources ) {
		$entityTypesProvided = [];
		foreach ( $sources as $source ) {
			foreach ( $source->getEntityTypes() as $type ) {
				if ( array_key_exists( $type, $entityTypesProvided ) ) {
					throw new \InvalidArgumentException(
						'Entity type "' . $type . '" has already been defined in source: "' . $entityTypesProvided[$type] . '"'
					);
				}
				$entityTypesProvided[$type] = $source->getSourceName();
			}
		}
	}

	/**
	 * @param string $entityType
	 * @return EntitySource|null
	 */
	public function getSourceForEntityType( $entityType ) {
		// TODO: when the same entity type can be provided by multiple source (currently forbidden),
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

}
