<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikimedia\Assert\Assert;

/**
 * Service providing access to repository settings.
 *
 * Repositories are identified by strings. A specially empty string name is used for the local repository.
 *
 * Each repository definition is an associative array with following keys:
 *  - database: symbolic name of the database (string or false),
 *  - base-uri: Base URI of concept URIs (e.g. used in RDF output). This should include
 *    scheme and authority part of the URI. Only entity ID will be added to the base URI.
 *  - entity-namespaces: map of names of entity types (strings) the repository provides to namespaces IDs (ints)
 *    related to the given entity type on the repository's wiki.
 *  - prefix-mapping: map of repository prefixes used in the repository (@see docs/foreign-entity-ids.wiki
 *    in the Data Model component for documentation on prefix mapping).
 *
 * Note: currently single entity type is mapped to a single repository. This might change in the future
 * and a particular entity type might be provided by multiple repositories.
 *
 * @see docs/options.wiki for documentation on "foreignRepositories" settings defining configuration
 * of foreign repositories..
 *
 * @license GPL-2.0-or-later
 */
class RepositoryDefinitions {

	/**
	 * @var array[]
	 */
	private $repositoryDefinitions = [];

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @var array[]
	 */
	private $entityTypeToRepositoryMapping = [];

	/**
	 * @var array[]
	 */
	private $entityTypesPerRepository = [];

	/**
	 * @var int[]
	 */
	private $entityNamespaces;

	/**
	 * @param array[] $repositoryDefinitions Associative array mapping repository names to an array of
	 * repository settings. Empty-string key stands for local repository.
	 * See class description for information on the expected format of $repositoryDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 *
	 * @throws InvalidArgumentException if $repositoryDefinitions has invalid format
	 */
	public function __construct( array $repositoryDefinitions, EntityTypeDefinitions $entityTypeDefinitions ) {
		$requiredFields = [ 'database', 'base-uri', 'entity-namespaces', 'prefix-mapping' ];

		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $repositoryDefinitions, '$repositoryDefinitions' );
		Assert::parameterElementType( 'array', $repositoryDefinitions, '$repositoryDefinitions' );
		Assert::parameter(
			array_key_exists( '', $repositoryDefinitions ),
			'$repositoryDefinitions',
			'must contain definition of the local repository (empty-string key)'
		);
		foreach ( $repositoryDefinitions as $definition ) {
			Assert::parameter(
				$this->definitionContainsAllRequiredFields( $definition, $requiredFields ),
				'$repositoryDefinitions',
				'each repository definition must contain the following keys: ' . implode( ', ', $requiredFields )
			);
		}

		$this->repositoryDefinitions = $repositoryDefinitions;
		$this->entityTypeDefinitions = $entityTypeDefinitions;

		$this->buildEntityTypeMappings( $repositoryDefinitions );
	}

	/**
	 * @return string[]
	 */
	public function getRepositoryNames() {
		return array_keys( $this->repositoryDefinitions );
	}

	/**
	 * @return array Associative array (string => string|false) mapping repository names to database symbolic names
	 */
	public function getDatabaseNames() {
		return $this->getMapForDefinitionField( 'database' );
	}

	/**
	 * @return string[] Associative array (string => string) mapping repository names to base URIs of concept URIs.
	 */
	public function getConceptBaseUris() {
		return $this->getMapForDefinitionField( 'base-uri' );
	}

	/**
	 * @return array[] Associative array (string => array) mapping repository names to prefix mapping for the repository,
	 */
	public function getPrefixMappings() {
		return $this->getMapForDefinitionField( 'prefix-mapping' );
	}

	/**
	 * @return array[] Associative array (string => string[]) mapping repository names to lists of entity types
	 * provided by each repository.
	 * Note: This includes sub entity types.
	 */
	public function getEntityTypesPerRepository() {
		return $this->entityTypesPerRepository;
	}

	/**
	 * @return array[] Associative array (string => array) mapping entity types to a list of
	 * [string repository name, int namespace] pairs, for repositories that provide entities of the given type,
	 * and the namespace ID on the respective repository.
	 * Note: Sub entities are not represented in the return as they do not have a namespace.
	 */
	public function getEntityTypeToRepositoryMapping() {
		return $this->entityTypeToRepositoryMapping;
	}

	/**
	 * @return string[] List of entity type names provided by all defined repositories.
	 * Note: This includes sub entity types.
	 */
	public function getAllEntityTypes() {
		$mainEntityTypes = array_keys( $this->entityTypeToRepositoryMapping );
		return array_merge( $mainEntityTypes, $this->getSubEntityTypesFromEntityTypes( $mainEntityTypes ) );
	}

	/**
	 * @param string[] $entityTypes
	 * @return string[]
	 */
	private function getSubEntityTypesFromEntityTypes( $entityTypes ) {
		$subTypeMap = $this->entityTypeDefinitions->getSubEntityTypes();
		$subTypes = [];
		foreach ( $entityTypes as $type ) {
			if ( array_key_exists( $type, $subTypeMap ) ) {
				$subTypes = array_merge( $subTypes, $subTypeMap[$type] );
			}
		}
		return array_unique( $subTypes );
	}

	/**
	 * @return int[] Associative array (string => int) mapping entity type names to namespace IDs (numbers) related
	 * namespace on the wiki of the repository that provides entities of the given type.
	 * Note: Sub entities are not represented in the return as they do not have a namespace.
	 */
	public function getEntityNamespaces() {
		return $this->entityNamespaces;
	}

	/**
	 * @param array $definition
	 * @param array $requiredFields
	 *
	 * @return bool
	 */
	private function definitionContainsAllRequiredFields( array $definition, array $requiredFields ) {
		return count( array_intersect_key( array_flip( $requiredFields ), $definition ) ) === count( $requiredFields );
	}

	/**
	 * @param array[] $repositoryDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 *
	 * @throws InvalidArgumentException
	 */
	private function buildEntityTypeMappings( array $repositoryDefinitions ) {
		$subEntityTypeMap = $this->entityTypeDefinitions->getSubEntityTypes();

		$this->entityTypeToRepositoryMapping = [];
		$this->entityTypesPerRepository = [];
		$this->entityNamespaces = [];

		foreach ( $repositoryDefinitions as $repositoryName => $definition ) {
			foreach ( $definition['entity-namespaces'] as $type => $namespace ) {
				if ( isset( $this->entityTypeToRepositoryMapping[$type] ) ) {
					throw new InvalidArgumentException(
						'Using same entity types on multiple repositories is not supported yet. '
						. '"' . $type . '" has already be defined for repository '
						. '"' . $this->entityTypeToRepositoryMapping[$type][0][0] .'"'
					);
				}

				$this->entityTypeToRepositoryMapping[$type][] = [ $repositoryName, $namespace ];
				$this->entityNamespaces[$type] = $namespace;

				$this->entityTypesPerRepository[$repositoryName][] = $type;
				if ( array_key_exists( $type, $subEntityTypeMap ) ) {
					foreach ( $subEntityTypeMap[$type] as $subEntityType ) {
						$this->entityTypesPerRepository[$repositoryName][] = $subEntityType;
					}
				}
			}
		}
	}

	/**
	 * @param string $field
	 *
	 * @return array Associative array mapping repository names to values of $field in
	 * the repository definition provided to the class constructor.
	 */
	private function getMapForDefinitionField( $field ) {
		$values = [];

		foreach ( $this->repositoryDefinitions as $repositoryName => $definition ) {
			if ( array_key_exists( $field, $definition ) ) {
				$values[$repositoryName] = $definition[$field];
			}
		}

		return $values;
	}

}
