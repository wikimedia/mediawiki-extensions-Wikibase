<?php

namespace Wikibase\DataModel\Statement;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Ordered and unique collection of Statement objects.
 * Can only contain Statements that have a non-null GUID.
 *
 * Provides indexed access by Statement GUID.
 * Does not provide complex modification functionality.
 *
 * @since 3.0
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class StatementByGuidMap implements IteratorAggregate, Countable {

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	/**
	 * @param Statement[]|Traversable $statements
	 */
	public function __construct( $statements = [] ) {
		foreach ( $statements as $statement ) {
			$this->addStatement( $statement );
		}
	}

	/**
	 * If the provided statement has a GUID not yet in the map, it will be appended to the map.
	 * If the GUID is already in the map, the statement with this guid will be replaced.
	 *
	 * @throws InvalidArgumentException
	 * @param Statement $statement
	 */
	public function addStatement( Statement $statement ) {
		if ( $statement->getGuid() === null ) {
			throw new InvalidArgumentException( 'Can only add statements that have a non-null GUID' );
		}

		$this->statements[$statement->getGuid()] = $statement;
	}

	/**
	 * @param string $statementGuid
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function hasStatementWithGuid( $statementGuid ) {
		$this->assertIsStatementGuid( $statementGuid );

		return array_key_exists( $statementGuid, $this->statements );
	}

	private function assertIsStatementGuid( $statementGuid ) {
		if ( !is_string( $statementGuid ) ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string' );
		}
	}

	/**
	 * @param string $statementGuid
	 *
	 * @return Statement|null
	 */
	public function getStatementByGuid( $statementGuid ) {
		$this->assertIsStatementGuid( $statementGuid );

		if ( array_key_exists( $statementGuid, $this->statements ) ) {
			return $this->statements[$statementGuid];
		}

		return null;
	}

	/**
	 * Removes the statement with the specified GUID if it exists.
	 *
	 * @param string $statementGuid
	 */
	public function removeStatementWithGuid( $statementGuid ) {
		$this->assertIsStatementGuid( $statementGuid );
		unset( $this->statements[$statementGuid] );
	}

	/**
	 * @see Countable::count
	 * @return int
	 */
	public function count(): int {
		return count( $this->statements );
	}

	/**
	 * The iterator has the GUIDs of the statements as keys.
	 *
	 * @see IteratorAggregate::getIterator
	 * @return Iterator|Statement[]
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator( $this->statements );
	}

	/**
	 * Returns the map in array form. The array keys are the GUIDs of their associated statement.
	 *
	 * @return Statement[]
	 */
	public function toArray() {
		return $this->statements;
	}

}
