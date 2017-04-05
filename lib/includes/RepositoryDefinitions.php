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
 *  - base-concept-uri: Base URI of concept URIs (e.g. used in RDF output). This should include
 *    scheme and authority part of the URI. Only entity ID will be added to the base URI.
 *  - base-data-uri: Base URI of data URIs (e.g. used in RDF output). This should include
 *    scheme and authority part of the URI. Only entity ID will be added to the base URI.
 *  - entity-types: list of entity names the repository provides (array of strings).
 *  - prefix-mapping: map of repository prefixes used in the repository (@see docs/foreign-entity-ids.wiki
 *    in the Data Model component for documention on prefix mapping).
 *
 * Note: currently single entity type is mapped to a single repository. This might change in the future
 * and a particular entity type might be provided by multiple repositories.
 *
 * @see docs/options.wiki for documentation on Client's "foreignRepositories" settings defining configuration
 * of foreign repositories..
 *
 * @license GPL-2.0+
 */
class RepositoryDefinitions {

	/**
	 * @var array
	 */
	private $repositoryDefinitions = [];

	/**
	 * @var string[]
	 */
	private $entityTypeToRepositoryMapping = [];

	/**
	 * @param array $repositoryDefinitions Associative array mapping repository names to an array of
	 * repository settings. Empty-string key stands for local repository.
	 * See class description for information on the expected format of $repositoryDefinitions
	 *
	 * @throws InvalidArgumentException if $repositoryDefinitions has invalid format
	 */
	public function __construct( array $repositoryDefinitions ) {
		$requiredFields = [ 'database', 'base-concept-uri', 'base-data-uri', 'entity-types', 'prefix-mapping' ];

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

		$this->entityTypeToRepositoryMapping = $this->buildEntityTypeToRepositoryMapping( $repositoryDefinitions );
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
		return $this->getMapForDefinitionField( 'base-concept-uri' );
	}

	/**
	 * @return string[] Associative array (string => string) mapping repository names to base URIs of data URIs.
	 */
	public function getDataBaseUris() {
		return $this->getMapForDefinitionField( 'base-data-uri' );
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
	 */
	public function getEntityTypesPerRepository() {
		return $this->getMapForDefinitionField( 'entity-types' );
	}

	/**
	 * @return string[] Associative array (string => string) mapping entity types to repository names which provide
	 * entities of the given type.
	 */
	public function getEntityTypeToRepositoryMapping() {
		return $this->entityTypeToRepositoryMapping;
	}

	/**
	 * @return string[] List of entity type names provided by all defined repositories.
	 */
	public function getAllEntityTypes() {
		return array_keys( $this->entityTypeToRepositoryMapping );
	}

	/**
	 * @param array $definition
	 * @param array $requiredFields
	 * @return bool
	 */
	private function definitionContainsAllRequiredFields( array $definition, array $requiredFields ) {
		return count( array_intersect_key( array_flip( $requiredFields ), $definition ) ) === count( $requiredFields );
	}

	/**
	 * @param array $repositoryDefinitions
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return string[]
	 */
	private function buildEntityTypeToRepositoryMapping( array $repositoryDefinitions ) {
		$mapping = [];

		foreach ( $repositoryDefinitions as $repositoryName => $definition ) {
			if ( !isset( $definition['entity-types'] ) ) {
				continue;
			}

			foreach ( $definition['entity-types'] as $entityType ) {
				if ( isset( $mapping[$entityType] ) ) {
					throw new InvalidArgumentException(
						'Using same entity types on multiple repositories is not supported yet. '
						. '"' . $entityType . '" has already be defined for repository '
						. '"' . $mapping[$entityType] .'"'
					);
				}

				$mapping[$entityType] = $repositoryName;
			}
		}

		return $mapping;
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
			if ( isset( $definition[$field] ) ) {
				$values[$repositoryName] = $definition[$field];
			}
		}

		return $values;
	}

}
