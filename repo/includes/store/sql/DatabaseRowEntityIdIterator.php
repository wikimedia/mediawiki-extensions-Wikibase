<?php

namespace Wikibase;
use ResultWrapper;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * Allows a database result set containing entity IDs to be iterated as EntityId objects.
 *
 * @since 0.5
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DatabaseRowEntityIdIterator extends ConvertingResultWrapper {

	/**
	 * @var string
	 */
	protected $idField;

	/**
	 * @var string
	 */
	protected $typeField;

	public function __construct( ResultWrapper $rows, $typeField, $idField ) {
		parent::__construct( $rows );

		$this->idField = $idField;
		$this->typeField = $typeField;
	}

	/**
	 * Converts a database row into the desired representation.
	 *
	 * @param object $row An object representing the raw database row, as returned by ResultWrapper::current().
	 *
	 * @return EntityId
	 */
	protected function convert( $row ) {
		$idField = $this->idField; //PHP fails
		$typeField = $this->typeField; //PHP fails

		$id = (int)$row->$idField;
		$type = $row->$typeField;

		//TODO: use an EntityIdFactory here
		switch ( $type ) {
			case 'item':
				$entityId = new ItemId( "Q$id" );
				break;
			case 'property':
				$entityId = new PropertyId( "P$id" );
				break;
			default:
				throw new \RuntimeException( "Unknown entity type: $type" );
		}

		return $entityId;
	}
}
