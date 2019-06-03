<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use MWNamespace;
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
	 * @var string[]
	 */
	private $entitySlots;

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
	 * [string repository name, int namespace, string slot] tupels, for repositories that
	 * provide entities of the given type, the namespace ID on the respective repository,
	 * and the name of the slot in which the entities are stored in pages on that namespace.
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
	 * @return string[] Associative array (string => string) mapping entity type names to
	 * the role names of the slots that are used to store that type of entity on the repository
	 * that provides entities of the given type.
	 * Note: if the entity is stored in the main slot, it may be omitted from the mapping returned
	 * by this method.
	 */
	public function getEntitySlots() {
		return $this->entitySlots;
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
	 *
	 * @throws InvalidArgumentException
	 */
	private function buildEntityTypeMappings( array $repositoryDefinitions ) {
		$subEntityTypeMap = $this->entityTypeDefinitions->getSubEntityTypes();

		$this->entityTypeToRepositoryMapping = [];
		$this->entityTypesPerRepository = [];
		$this->entityNamespaces = [];
		$this->entitySlots = [];

		foreach ( $repositoryDefinitions as $repositoryName => $definition ) {
			// Even if a repo has no namespaces defined, still correctly set an array T208308
			$this->entityTypesPerRepository[$repositoryName] = [];

			foreach ( $definition['entity-namespaces'] as $type => $namespaceAndSlot ) {
				if ( isset( $this->entityTypeToRepositoryMapping[$type] ) ) {
					throw new InvalidArgumentException(
						'Using same entity types on multiple repositories is not supported yet. '
						. '"' . $type . '" has already be defined for repository '
						. '"' . $this->entityTypeToRepositoryMapping[$type][0][0] .'"'
					);
				}

				list( $namespace, $slotName ) = $this->splitNamespaceAndSlot( $namespaceAndSlot );

				$this->entityTypeToRepositoryMapping[$type][] = [
					$repositoryName,
					$namespace,
					$slotName,
				];
				$this->entityNamespaces[$type] = $namespace;
				$this->entitySlots[$type] = $slotName;

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
	 * @param $namespaceAndSlot
	 *
	 * @return array list( $namespace, $slotName )
	 */
	private function splitNamespaceAndSlot( $namespaceAndSlot ) {
		if ( is_int( $namespaceAndSlot ) ) {
			return [ $namespaceAndSlot, 'main' ];
		}

		if ( !preg_match( '!^(\w*)(/(\w+))?!', $namespaceAndSlot, $m ) ) {
			throw new InvalidArgumentException(
				'Bad namespace/slot specification: an integer namespace index, or a canonical'
				. ' namespace name, or have the form <namespace>/<slot-name>.'
				. ' Found ' . $namespaceAndSlot
			);
		}

		if ( is_numeric( $m[1] ) ) {
			$ns = intval( $m[1] );
		} else {
			$ns = MWNamespace::getCanonicalIndex( strtolower( $m[1] ) );
		}

		if ( !is_int( $ns ) ) {
			throw new InvalidArgumentException(
				'Bad namespace specification: must be either an integer or a canonical'
				. ' namespace name. Found ' . $m[1]
			);
		}

		return [
			$ns,
			$m[3] ?? 'main'
		];
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
