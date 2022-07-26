<?php

namespace Wikibase\DataAccess;

use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class DatabaseEntitySource implements EntitySource {

	public const TYPE = 'db';

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
	private $entityTypes = [];

	/**
	 * @var int[]
	 */
	private $entityNamespaceIds = [];

	/**
	 * @var string[]
	 */
	private $entitySlots = [];

	/**
	 * @var string
	 */
	private $conceptBaseUri;

	/** @var string */
	private $rdfNodeNamespacePrefix;

	/** @var string */
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
	 * @param string $rdfNodeNamespacePrefix
	 * @param string $rdfPredicateNamespacePrefix
	 * @param string $interwikiPrefix
	 */
	public function __construct(
		$name,
		$databaseName,
		array $entityNamespaceIdsAndSlots,
		$conceptBaseUri,
		$rdfNodeNamespacePrefix,
		$rdfPredicateNamespacePrefix,
		$interwikiPrefix
	) {
		Assert::parameterType( 'string', $name, '$name' );
		Assert::parameter( is_string( $databaseName ) || $databaseName === false, '$databaseName', 'must be a string or false' );
		Assert::parameterType( 'string', $conceptBaseUri, '$conceptBaseUri' );
		Assert::parameterType( 'string', $rdfNodeNamespacePrefix, '$rdfNodeNamespacePrefix' );
		Assert::parameterType( 'string', $rdfPredicateNamespacePrefix, '$rdfPredicateNamespacePrefix' );
		Assert::parameterType( 'string', $interwikiPrefix, '$interwikiPrefix' );

		$this->sourceName = $name;
		$this->databaseName = $databaseName;
		$this->conceptBaseUri = $conceptBaseUri;
		$this->rdfNodeNamespacePrefix = $rdfNodeNamespacePrefix;
		$this->rdfPredicateNamespacePrefix = $rdfPredicateNamespacePrefix;
		$this->interwikiPrefix = $interwikiPrefix;
		$this->setEntityTypeData( $entityNamespaceIdsAndSlots );
	}

	private function setEntityTypeData( array $entityNamespaceIdsAndSlots ) {
		foreach ( $entityNamespaceIdsAndSlots as $entityType => $namespaceIdAndSlot ) {
			if ( !is_string( $entityType ) ) {
				throw new \InvalidArgumentException( 'Entity type name not a string: ' . $entityType );
			}
			if ( !is_int( $namespaceIdAndSlot['namespaceId'] ) ) {
				throw new \InvalidArgumentException( 'Namespace ID for entity type must be an integer: ' . $entityType );
			}
			if ( !is_string( $namespaceIdAndSlot['slot'] ) ) {
				throw new \InvalidArgumentException( 'Slot for entity type must be a string: ' . $entityType );
			}

			$this->entityTypes[] = $entityType;
			$this->entityNamespaceIds[$entityType] = $namespaceIdAndSlot['namespaceId'];
			$this->entitySlots[$entityType] = $namespaceIdAndSlot['slot'];
		}
	}

	/**
	 * @return string|false The name of the database to use (use false for the local db)
	 */
	public function getDatabaseName() {
		return $this->databaseName;
	}

	public function getSourceName(): string {
		return $this->sourceName;
	}

	public function getEntityTypes(): array {
		return $this->entityTypes;
	}

	public function getEntityNamespaceIds(): array {
		return $this->entityNamespaceIds;
	}

	public function getEntitySlotNames(): array {
		return $this->entitySlots;
	}

	public function getConceptBaseUri(): string {
		return $this->conceptBaseUri;
	}

	public function getRdfNodeNamespacePrefix(): string {
		return $this->rdfNodeNamespacePrefix;
	}

	public function getRdfPredicateNamespacePrefix(): string {
		return $this->rdfPredicateNamespacePrefix;
	}

	public function getInterwikiPrefix(): string {
		return $this->interwikiPrefix;
	}

	public function getType(): string {
		return self::TYPE;
	}
}
