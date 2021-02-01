<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\EntityTypeDefinitions;
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

	/** @var null|string[] */
	private $sourceToConceptBaseUriMap = null;

	/** @var null|string[] */
	private $sourceToRdfNodeNamespacePrefixMap = null;

	/** @var null|string[] */
	private $sourceToRdfPredicateNamespacePrefixMap = null;

	/**
	 * @var string[] Associative array mapping "sub entity type" name to the name of its "parent" entity type
	 */
	private $subEntityTypeMap;

	/**
	 * @param EntitySource[] $sources with unique names. An single entity type can not be used in two different sources.
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 */
	public function __construct( array $sources, EntityTypeDefinitions $entityTypeDefinitions ) {
		Assert::parameterElementType( EntitySource::class, $sources, '$sources' );
		$this->assertNoDuplicateSourcesOrEntityTypes( $sources );
		$this->sources = $sources;
		$this->subEntityTypeMap = $this->buildSubEntityTypeMap( $entityTypeDefinitions );
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

	private function buildSubEntityTypeMap( EntityTypeDefinitions $entityTypeDefinitions ) {
		$subEntityTypes = $entityTypeDefinitions->get( EntityTypeDefinitions::SUB_ENTITY_TYPES );

		$subEntityTypeMap = [];
		foreach ( $subEntityTypes as $type => $subTypes ) {
			foreach ( $subTypes as $subType ) {
				$subEntityTypeMap[$subType] = $type;
			}
		}

		return $subEntityTypeMap;
	}

	public function getSources() {
		return $this->sources;
	}

	/**
	 * @todo when the same entity type can be provided by multiple source (currently forbidden),
	 * this should return all sources
	 *
	 * @param string $entityType Entity type or sub type
	 * @return EntitySource|null EntitySource or null if no EntitySource configured for the type
	 */
	public function getSourceForEntityType( string $entityType ): ?EntitySource {
		if ( array_key_exists( $entityType, $this->subEntityTypeMap ) ) {
			$entityType = $this->subEntityTypeMap[$entityType];
		}

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

	private function buildEntityTypeToSourceMapping() {
		$this->entityTypeToSourceMapping = [];
		foreach ( $this->sources as $source ) {
			$entityTypes = $source->getEntityTypes();
			foreach ( $entityTypes as $type ) {
				$this->entityTypeToSourceMapping[$type] = $source;
			}
		}
		foreach ( $this->subEntityTypeMap as $subEntityType => $mainEntityType ) {
			// Only add sub entities that are enabled to be mapping
			if ( array_key_exists( $mainEntityType, $this->entityTypeToSourceMapping ) ) {
				$this->entityTypeToSourceMapping[$subEntityType] = $this->entityTypeToSourceMapping[$mainEntityType];
			}
		}
		return $this->entityTypeToSourceMapping;
	}

	/**
	 * @return string[]
	 */
	public function getConceptBaseUris() {
		if ( $this->sourceToConceptBaseUriMap === null ) {
			$this->sourceToConceptBaseUriMap = [];
			foreach ( $this->sources as $source ) {
				$this->sourceToConceptBaseUriMap[$source->getSourceName()] = $source->getConceptBaseUri();
			}
		}
		return $this->sourceToConceptBaseUriMap;
	}

	/**
	 * @return string[]
	 */
	public function getRdfNodeNamespacePrefixes() {
		if ( $this->sourceToRdfNodeNamespacePrefixMap === null ) {
			$this->sourceToRdfNodeNamespacePrefixMap = [];
			foreach ( $this->sources as $source ) {
				$this->sourceToRdfNodeNamespacePrefixMap[$source->getSourceName()] = $source->getRdfNodeNamespacePrefix();
			}
		}
		return $this->sourceToRdfNodeNamespacePrefixMap;
	}

	/**
	 * @return string[]
	 */
	public function getRdfPredicateNamespacePrefixes() {
		if ( $this->sourceToRdfPredicateNamespacePrefixMap === null ) {
			$this->sourceToRdfPredicateNamespacePrefixMap = [];
			foreach ( $this->sources as $source ) {
				$this->sourceToRdfPredicateNamespacePrefixMap[$source->getSourceName()] = $source->getRdfPredicateNamespacePrefix();
			}
		}
		return $this->sourceToRdfPredicateNamespacePrefixMap;
	}

}
