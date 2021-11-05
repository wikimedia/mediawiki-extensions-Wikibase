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
	 * @var null|DatabaseEntitySource[]
	 */
	private $entityTypeToDatabaseSourceMapping = null;

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
				if ( $source->getType() === ApiEntitySource::TYPE ) {
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

	/**
	 * @return EntitySource[]
	 */
	public function getSources(): array {
		return $this->sources;
	}

	/**
	 * @param string $entityType Entity type or sub type
	 * @return DatabaseEntitySource|null DatabaseEntitySource or null if no DatabaseEntitySource configured for the type
	 */
	public function getDatabaseSourceForEntityType( string $entityType ): ?DatabaseEntitySource {
		$entityType = $this->subEntityTypesMapper->getParentEntityType( $entityType ) ?? $entityType;

		$entityTypeToSourceMapping = $this->getEntityTypeToDatabaseSourceMapping();
		if ( array_key_exists( $entityType, $entityTypeToSourceMapping ) ) {
			return $entityTypeToSourceMapping[$entityType];
		}

		return null;
	}

	/**
	 * As of Federated Properties v2 there is only one source of federation per entity type, so returning a single EntitySource is ok.
	 */
	public function getApiSourceForEntityType( string $entityType ): ?ApiEntitySource {
		$entityType = $this->subEntityTypesMapper->getParentEntityType( $entityType ) ?? $entityType;

		foreach ( $this->sources as $source ) {
			if ( $source->getType() === ApiEntitySource::TYPE && in_array( $entityType, $source->getEntityTypes() ) ) {
				return $source;
			}
		}

		return null;
	}

	/**
	 * @return DatabaseEntitySource[]
	 */
	public function getEntityTypeToDatabaseSourceMapping() {
		if ( $this->entityTypeToDatabaseSourceMapping === null ) {
			$this->buildEntityTypeToDatabaseSourceMapping();
		}
		return $this->entityTypeToDatabaseSourceMapping;
	}

	private function buildEntityTypeToDatabaseSourceMapping() {
		$this->entityTypeToDatabaseSourceMapping = [];
		foreach ( $this->sources as $source ) {
			if ( $source->getType() === DatabaseEntitySource::TYPE ) {
				$entityTypes = $source->getEntityTypes();
				foreach ( $entityTypes as $type ) {
					$this->entityTypeToDatabaseSourceMapping[$type] = $source;
				}
			}
		}
		foreach ( $this->entityTypeToDatabaseSourceMapping as $mainEntityType => $source ) {
			foreach ( $this->subEntityTypesMapper->getSubEntityTypes( $mainEntityType ) as $subEntityType ) {
				$this->entityTypeToDatabaseSourceMapping[$subEntityType] = $this->entityTypeToDatabaseSourceMapping[$mainEntityType];
			}
		}
		return $this->entityTypeToDatabaseSourceMapping;
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
