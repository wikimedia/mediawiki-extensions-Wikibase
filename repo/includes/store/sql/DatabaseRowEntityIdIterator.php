<?php

namespace Wikibase;

use ResultWrapper;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * Allows a database result set containing entity IDs to be iterated as EntityId objects.
 *
 * @since 0.5
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
	 * @var EntityIdParser
	 */
	protected $parser;

	public function __construct( ResultWrapper $rows, $idField, EntityIdParser $parser ) {
		parent::__construct( $rows );

		$this->idField = $idField;
		$this->parser = $parser;
	}

	/**
	 * Converts a database row into the desired representation.
	 *
	 * @param object $row An object representing the raw database row, as returned by ResultWrapper::current().
	 *
	 * @throws \RuntimeException
	 * @return EntityId
	 */
	protected function convert( $row ) {
		$idField = $this->idField; //PHP fails
		$id = $row->$idField;

		return $this->parser->parse( $id );
	}
}
