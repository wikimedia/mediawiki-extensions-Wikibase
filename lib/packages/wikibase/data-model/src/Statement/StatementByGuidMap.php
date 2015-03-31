<?php

namespace Wikibase\DataModel\Statement;

use InvalidArgumentException;

/**
 * Ordered and unique collection of Statement objects.
 * Provides indexed access by Statement GUID. Can only contain Statements that have a non-null GUID.
 *
 * @since 3.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class StatementByGuidMap {

	private $statements = array();

	/**
	 * @param Statement[] $statements
	 */
	public function __construct( $statements = array() ) {
		foreach ( $statements as $statement ) {
			$this->addStatement( $statement );
		}
	}

	private function addStatement( Statement $statement ) {
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

}
