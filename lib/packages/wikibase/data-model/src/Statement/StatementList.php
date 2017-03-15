<?php

namespace Wikibase\DataModel\Statement;

use ArrayIterator;
use Comparable;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Ordered and non-unique collection of Statement objects.
 * Provides various filter operations.
 *
 * Does not do any indexing by default.
 * Does not provide complex modification functionality.
 *
 * @since 1.0
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class StatementList implements IteratorAggregate, Comparable, Countable {

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	/**
	 * @param Statement[]|Traversable|Statement $statements
	 * @param Statement [$statement2,...]
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $statements = [] /*...*/ ) {
		if ( $statements instanceof Statement ) {
			$statements = func_get_args();
		}

		if ( !is_array( $statements ) && !( $statements instanceof Traversable ) ) {
			throw new InvalidArgumentException( '$statements must be an array or an instance of Traversable' );
		}

		foreach ( $statements as $statement ) {
			if ( !( $statement instanceof Statement ) ) {
				throw new InvalidArgumentException( 'Every element in $statements must be an instance of Statement' );
			}

			$this->statements[] = $statement;
		}
	}

	/**
	 * Returns the property ids used by the statements.
	 * The keys of the returned array hold the serializations of the property ids.
	 *
	 * @return PropertyId[] Array indexed by property id serialization.
	 */
	public function getPropertyIds() {
		$propertyIds = [];

		foreach ( $this->statements as $statement ) {
			$propertyIds[$statement->getPropertyId()->getSerialization()] = $statement->getPropertyId();
		}

		return $propertyIds;
	}

	/**
	 * @since 1.0, setting an index is supported since 6.1
	 * @see ReferenceList::addReference
	 *
	 * @param Statement $statement
	 * @param int|null $index New position of the added statement, or null to append.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addStatement( Statement $statement, $index = null ) {
		if ( $index === null ) {
			$this->statements[] = $statement;
		} elseif ( is_int( $index ) && $index >= 0 ) {
			array_splice( $this->statements, $index, 0, [ $statement ] );
		} else {
			throw new InvalidArgumentException( '$index must be a non-negative integer or null' );
		}
	}

	/**
	 * @param Snak $mainSnak
	 * @param Snak[]|SnakList|null $qualifiers
	 * @param Reference[]|ReferenceList|null $references
	 * @param string|null $guid
	 */
	public function addNewStatement( Snak $mainSnak, $qualifiers = null, $references = null, $guid = null ) {
		$qualifiers = is_array( $qualifiers ) ? new SnakList( $qualifiers ) : $qualifiers;
		$references = is_array( $references ) ? new ReferenceList( $references ) : $references;

		$statement = new Statement( $mainSnak, $qualifiers, $references );
		$statement->setGuid( $guid );

		$this->statements[] = $statement;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $guid
	 */
	public function removeStatementsWithGuid( $guid ) {
		foreach ( $this->statements as $index => $statement ) {
			if ( $statement->getGuid() === $guid ) {
				unset( $this->statements[$index] );
			}
		}

		$this->statements = array_values( $this->statements );
	}

	/**
	 * Statements that have a main snak already in the list are filtered out.
	 * The last occurrences are retained.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public function getWithUniqueMainSnaks() {
		$statements = [];

		foreach ( $this->statements as $statement ) {
			$statements[$statement->getMainSnak()->getHash()] = $statement;
		}

		return new self( $statements );
	}

	/**
	 * @since 3.0
	 *
	 * @param PropertyId $id
	 *
	 * @return self
	 */
	public function getByPropertyId( PropertyId $id ) {
		$statementList = new self();

		foreach ( $this->statements as $statement ) {
			if ( $statement->getPropertyId()->equals( $id ) ) {
				$statementList->statements[] = $statement;
			}
		}

		return $statementList;
	}

	/**
	 * @since 3.0
	 *
	 * @param int|int[] $acceptableRanks
	 *
	 * @return self
	 */
	public function getByRank( $acceptableRanks ) {
		$acceptableRanks = array_flip( (array)$acceptableRanks );
		$statementList = new self();

		foreach ( $this->statements as $statement ) {
			if ( array_key_exists( $statement->getRank(), $acceptableRanks ) ) {
				$statementList->statements[] = $statement;
			}
		}

		return $statementList;
	}

	/**
	 * Returns the so called "best statements".
	 * If there are preferred statements, then this is all the preferred statements.
	 * If there are no preferred statements, then this is all normal statements.
	 *
	 * @since 2.4
	 *
	 * @return self
	 */
	public function getBestStatements() {
		$statements = $this->getByRank( Statement::RANK_PREFERRED );

		if ( !$statements->isEmpty() ) {
			return $statements;
		}

		return $this->getByRank( Statement::RANK_NORMAL );
	}

	/**
	 * Returns a list of all Snaks on this StatementList. This includes at least the main snaks of
	 * all statements, the snaks from qualifiers, and the snaks from references.
	 *
	 * This is a convenience method for use in code that needs to operate on all snaks, e.g.
	 * to find all referenced Entities.
	 *
	 * @since 1.1
	 *
	 * @return Snak[] Numerically indexed (non-sparse) array.
	 */
	public function getAllSnaks() {
		$snaks = [];

		foreach ( $this->statements as $statement ) {
			foreach ( $statement->getAllSnaks() as $snak ) {
				$snaks[] = $snak;
			}
		}

		return $snaks;
	}

	/**
	 * @since 2.3
	 *
	 * @return Snak[] Numerically indexed (non-sparse) array.
	 */
	public function getMainSnaks() {
		$snaks = [];

		foreach ( $this->statements as $statement ) {
			$snaks[] = $statement->getMainSnak();
		}

		return $snaks;
	}

	/**
	 * @return Traversable|Statement[]
	 */
	public function getIterator() {
		return new ArrayIterator( $this->statements );
	}

	/**
	 * @return Statement[] Numerically indexed (non-sparse) array.
	 */
	public function toArray() {
		return $this->statements;
	}

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count() {
		return count( $this->statements );
	}

	/**
	 * @see Comparable::equals
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		if ( !( $target instanceof self )
			|| $this->count() !== $target->count()
		) {
			return false;
		}

		return $this->statementsEqual( $target->statements );
	}

	private function statementsEqual( array $statements ) {
		reset( $statements );

		foreach ( $this->statements as $statement ) {
			if ( !$statement->equals( current( $statements ) ) ) {
				return false;
			}

			next( $statements );
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return empty( $this->statements );
	}

	/**
	 * @since 3.0
	 * @see StatementByGuidMap
	 *
	 * @param string|null $statementGuid
	 *
	 * @return Statement|null The first statement with the given GUID or null if not found.
	 */
	public function getFirstStatementWithGuid( $statementGuid ) {
		foreach ( $this->statements as $statement ) {
			if ( $statement->getGuid() === $statementGuid ) {
				return $statement;
			}
		}

		return null;
	}

	/**
	 * @since 4.1
	 *
	 * @param StatementFilter $filter
	 *
	 * @return self
	 */
	public function filter( StatementFilter $filter ) {
		$statementList = new self();

		foreach ( $this->statements as $statement ) {
			if ( $filter->statementMatches( $statement ) ) {
				$statementList->statements[] = $statement;
			}
		}

		return $statementList;
	}

	/**
	 * Removes all statements from this list.
	 *
	 * @since 7.0
	 */
	public function clear() {
		$this->statements = [];
	}

	/**
	 * @see http://php.net/manual/en/language.oop5.cloning.php
	 *
	 * @since 5.1
	 */
	public function __clone() {
		foreach ( $this->statements as &$statement ) {
			$statement = clone $statement;
		}
	}

}
