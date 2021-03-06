<?php

namespace Wikibase\DataAccess;

use Wikibase\Lib\SubEntityTypesMapper;
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
	 * @var SubEntityTypesMapper
	 */
	private $subEntityTypesMapper;

	/**
	 * @param EntitySource[] $sources with unique names. An single entity type can not be used in two different sources.
	 * @param SubEntityTypesMapper $subEntityTypesMapper
	 */
	public function __construct( array $sources, SubEntityTypesMapper $subEntityTypesMapper ) {
		Assert::parameterElementType( EntitySource::class, $sources, '$sources' );
		$this->assertNoDuplicateSourcesOrEntityTypes( $sources );
		$this->sources = $sources;
		$this->subEntityTypesMapper = $subEntityTypesMapper;
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
				if ( $source->getType() === EntitySource::TYPE_API ) {
					continue; // it's ok to have more than one entity source per entity type if it's an api source
				}

				if ( array_key_exists( $type, $entityTypesProvided ) ) {
					throw new \InvalidArgumentException(
						'Entity type "' . $type . '" has already been defined in source: "' . $entityTypesProvided[$type] . '"'
					);
				}
				$entityTypesProvided[$type] = $source->getSourceName();
			}

		}
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
		$entityType = $this->subEntityTypesMapper->getParentEntityType( $entityType ) ?? $entityType;

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
		foreach ( $this->entityTypeToSourceMapping as $mainEntityType => $source ) {
			foreach ( $this->subEntityTypesMapper->getSubEntityTypes( $mainEntityType ) as $subEntityType ) {
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
