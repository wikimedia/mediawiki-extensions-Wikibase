<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * An EntitySource includes information needed to interact with one or more entity types at a given source.
 * EntitySource can only currently be used via direct database access.
 *
 * @see EntitySourceDefinitions for defining multiple EntitySources within a single site.
 *
 * @license GPL-2.0-or-later
 */
class EntitySource {

	/**
	 * @var string
	 */
	private $sourceName;

	/**
	 * @var string|false The name of the database to use (use false for the local db)
	 */
	private $databaseName;

	/**
	 * @var string[]
	 */
	private $entityTypes;

	/**
	 * @var int[]
	 */
	private $entityNamespaceIds;

	/**
	 * @var string[]
	 */
	private $entitySlots;

	/**
	 * @var string
	 */
	private $conceptBaseUri;

	private $rdfNodeNamespacePrefix;

	private $rdfPredicateNamespacePrefix;

	/**
	 * @var string
	 */
	private $interwikiPrefix;

	/**
	 * @param string $name Unique name for the source for a given configuration / site, used for indexing the sources internally.
	 *        This does not have to be a wikiname, sitename or dbname, it can for example just be 'properties'.
	 * @param string|false $databaseName The name of the database to use (use false for the local db)
	 * @param array $entityNamespaceIdsAndSlots Associative array indexed by entity type (string), values are
	 * array of form [ 'namespaceId' => int, 'slot' => string ]
	 * @param string $conceptBaseUri
	 * @param string $interwikiPrefix
	 */
	public function __construct( $name, $databaseName, array $entityNamespaceIdsAndSlots, $conceptBaseUri, $interwikiPrefix ) {
		Assert::parameterType( 'string', $name, '$name' );
		Assert::parameter( is_string( $databaseName ) || $databaseName === false, '$databaseName', 'must be a string or false' );
		Assert::parameterType( 'string', $conceptBaseUri, '$conceptBaseUri' );
		Assert::parameterType( 'string', $interwikiPrefix, '$interwikiPrefix' );
		$this->assertEntityNamespaceIdsAndSlots( $entityNamespaceIdsAndSlots );

		$this->sourceName = $name;
		$this->databaseName = $databaseName;
		$this->conceptBaseUri = $conceptBaseUri;
		$this->rdfNodeNamespacePrefix = 'wd';
		$this->rdfPredicateNamespacePrefix = '';
		$this->interwikiPrefix = $interwikiPrefix;

		$this->setEntityTypeData( $entityNamespaceIdsAndSlots );
	}

	private function assertEntityNamespaceIdsAndSlots( array $entityNamespaceIdsAndSlots ) {
		foreach ( $entityNamespaceIdsAndSlots as $entityType => $namespaceIdAndSlot ) {
			if ( !is_string( $entityType ) ) {
				throw new \InvalidArgumentException( 'Entity type name not a string: ' . $entityType );
			}
			if ( !is_array( $namespaceIdAndSlot ) ) {
				throw new \InvalidArgumentException( 'Namespace and slot not defined for entity type: ' . $entityType );
			}
			if ( !array_key_exists( 'namespaceId', $namespaceIdAndSlot ) ) {
				throw new \InvalidArgumentException( 'Namespace ID not defined for entity type: ' . $entityType );
			}
			if ( !array_key_exists( 'slot', $namespaceIdAndSlot ) ) {
				throw new \InvalidArgumentException( 'Slot not defined for entity type: ' . $entityType );
			}
			if ( !is_int( $namespaceIdAndSlot['namespaceId'] ) ) {
				throw new \InvalidArgumentException( 'Namespace ID for entity type must be an integer: ' . $entityType );
			}
			if ( !is_string( $namespaceIdAndSlot['slot'] ) ) {
				throw new \InvalidArgumentException( 'Slot for entity type must be a string: ' . $entityType );
			}
		}
	}

	private function setEntityTypeData( array $entityNamespaceIdsAndSlots ) {
		$this->entityTypes = array_keys( $entityNamespaceIdsAndSlots );
		$this->entityNamespaceIds = array_map(
			function ( $x ) {
				return $x['namespaceId'];
			},
			$entityNamespaceIdsAndSlots
		);
		$this->entitySlots = array_map(
			function ( $x ) {
				return $x['slot'];
			},
			$entityNamespaceIdsAndSlots
		);
	}

	public function getDatabaseName() {
		return $this->databaseName;
	}

	public function getSourceName() {
		return $this->sourceName;
	}

	public function getEntityTypes() {
		return $this->entityTypes;
	}

	public function getEntityNamespaceIds() {
		return $this->entityNamespaceIds;
	}

	public function getEntitySlotNames() {
		return $this->entitySlots;
	}

	public function getConceptBaseUri() {
		return $this->conceptBaseUri;
	}

	public function getRdfNodeNamespacePrefix() {
		return $this->rdfNodeNamespacePrefix;
	}

	public function getRdfPredicateNamespacePrefix() {
		return $this->rdfPredicateNamespacePrefix;
	}

	public function getInterwikiPrefix() {
		return $this->interwikiPrefix;
	}

}
