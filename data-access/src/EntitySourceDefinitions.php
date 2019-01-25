<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * A collection of EntitySource objects.
 * Allows looking up an EntitySource object for a given entity type.
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceDefinitions {

	/**
	 * @var EntitySource[]
	 */
	private $sources;

	/**
	 * @var null|EntitySource[]
	 */
	private $entityTypeToSourceMapping = null;

	/**
	 * @param EntitySource[] $sources with unique names. An single entity type can not be used in two different sources.
	 */
	public function __construct( array $sources ) {
		Assert::parameterElementType( EntitySource::class, $sources, '$sources' );
		$this->assertNoDuplicateSourcesOrEntityTypes( $sources );
		$this->sources = $sources;
	}

	/**
	 * @param EntitySource[] $sources
	 */
	private function assertNoDuplicateSourcesOrEntityTypes( array $sources ) {
		$entityTypesProvided = [];
		$sourceNamesProvided = [];

		foreach ( $sources as $source ) {

			$sourceName = $source->getSourceName();
			if ( in_array( $sourceName, $sourceNamesProvided ) ) {
				throw new \InvalidArgumentException(
					'Source "' . $sourceName . '" has already been defined in sources array'
				);
			}
			$sourceNamesProvided[] = $sourceName;

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
		$entityTypeToSourceMapping = $this->getEntityTypeToSourceMapping();
		if ( array_key_exists( $entityType, $entityTypeToSourceMapping ) ) {
			return $entityTypeToSourceMapping[$entityType];
		}

		return null;
	}

	/**
	 * @return EntitySource[]
	 */
	public function getEntityTypeToSourceMapping() {
		if ( $this->entityTypeToSourceMapping === null ) {
			$this->buildEntityTypeToSourceMapping();
		}
		return $this->entityTypeToSourceMapping;
	}

	private function buildEntityTypeToSourceMapping(){
		$this->entityTypeToSourceMapping = [];
		foreach ( $this->sources as $source ) {
			$entityTypes = $source->getEntityTypes();
			foreach ( $entityTypes as $type ) {
				$this->entityTypeToSourceMapping[$type] = $source;
			}
		}
		return $this->entityTypeToSourceMapping;
	}

}
