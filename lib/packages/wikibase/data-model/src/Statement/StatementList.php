<?php declare( strict_types=1 );

namespace Wikibase\DataModel\Statement;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Exception\PropertyChangedException;
use Wikibase\DataModel\Exception\StatementGuidChangedException;
use Wikibase\DataModel\Exception\StatementNotFoundException;
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
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class StatementList implements IteratorAggregate, Countable {

	/**
	 * @var Statement[]
	 */
	private $statements;

	/**
	 * @param Statement ...$statements
	 */
	public function __construct( Statement ...$statements ) {
		$this->statements = $statements;
	}

	/**
	 * Returns the property ids used by the statements.
	 * The keys of the returned array hold the serializations of the property ids.
	 *
	 * @return PropertyId[] Array indexed by property id serialization.
	 */
	public function getPropertyIds(): array {
		$propertyIds = [];

		foreach ( $this->statements as $statement ) {
			$propertyIds[$statement->getPropertyId()->getSerialization()] = $statement->getPropertyId();
		}

		return $propertyIds;
	}

	/**
	 * @see ReferenceList::addReference
	 *
	 * @since 1.0, setting an index is supported since 6.1
	 *
	 * @param Statement $statement
	 * @param int|null $index New position of the added statement, or null to append.
	 *
	 * @throws InvalidArgumentException
	 */
	public function addStatement( Statement $statement, int $index = null ): void {
		if ( $index === null ) {
			$this->statements[] = $statement;
		} elseif ( $index >= 0 ) {
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
	public function addNewStatement(
		Snak $mainSnak, $qualifiers = null, $references = null, string $guid = null
	): void {
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
	public function removeStatementsWithGuid( ?string $guid ): void {
		foreach ( $this->statements as $index => $statement ) {
			if ( $statement->getGuid() === $guid ) {
				unset( $this->statements[$index] );
			}
		}

		$this->statements = array_values( $this->statements );
	}

	/**
	 * @param StatementGuid $statementGuid The GUID of the Statement to be replaced
	 * @param Statement $newStatement The new Statement
	 *
	 * @throws StatementNotFoundException if the Statement with $statementGuid can't be found
	 * @throws StatementGuidChangedException if the $newStatement has a different StatementGuid
	 * @throws PropertyChangedException if the $newStatement has a different MainSnak Property
	 */
	public function replaceStatement( StatementGuid $statementGuid, Statement $newStatement ): void {
		$index = $this->getIndexOfFirstStatementWithGuid( (string)$statementGuid );
		if ( $index === null ) {
			throw new StatementNotFoundException( "Statement with GUID '$statementGuid' not found" );
		} elseif ( $newStatement->getGuid() && (string)$statementGuid !== $newStatement->getGuid() ) {
			throw new StatementGuidChangedException(
				'The new Statement must not have a different Statement GUID than the original'
			);
		} elseif ( !$this->statements[$index]->getMainSnak()->getPropertyId()->equals( $newStatement->getMainSnak()->getPropertyId() ) ) {
			throw new PropertyChangedException(
				'The new Statement must not have a different Property than the original'
			);
		}

		$newStatement->setGuid( (string)$statementGuid );
		$this->statements[$index] = $newStatement;
	}

	/**
	 * Statements that have a main snak already in the list are filtered out.
	 * The last occurrences are retained.
	 *
	 * @since 1.0
	 *
	 * @return self
	 */
	public function getWithUniqueMainSnaks(): self {
		$statements = [];

		foreach ( $this->statements as $statement ) {
			$statements[$statement->getMainSnak()->getHash()] = $statement;
		}

		return new self( ...array_values( $statements ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param PropertyId $id
	 *
	 * @return self
	 */
	public function getByPropertyId( PropertyId $id ): self {
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
	public function getByRank( $acceptableRanks ): self {
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
	public function getBestStatements(): self {
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
	public function getAllSnaks(): array {
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
	public function getMainSnaks(): array {
		$snaks = [];

		foreach ( $this->statements as $statement ) {
			$snaks[] = $statement->getMainSnak();
		}

		return $snaks;
	}

	/**
	 * @return Iterator|Statement[]
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->statements );
	}

	/**
	 * @return Statement[] Numerically indexed (non-sparse) array.
	 */
	public function toArray(): array {
		return $this->statements;
	}

	/**
	 * @see Countable::count
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->statements );
	}

	/**
	 *
	 * @param mixed $target
	 *
	 * @return bool
	 */
	public function equals( $target ): bool {
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

	private function statementsEqual( array $statements ): bool {
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
	public function isEmpty(): bool {
		return empty( $this->statements );
	}

	/**
	 * @see StatementByGuidMap
	 *
	 * @since 3.0
	 *
	 * @param string|null $statementGuid
	 *
	 * @return Statement|null The first statement with the given GUID or null if not found.
	 */
	public function getFirstStatementWithGuid( ?string $statementGuid ): ?Statement {
		$index = $this->getIndexOfFirstStatementWithGuid( $statementGuid );
		return $this->statements[$index] ?? null;
	}

	/**
	 * @param string|null $statementGuid
	 *
	 * @return int|null The index of the first statement with the given GUID or null if not found.
	 */
	private function getIndexOfFirstStatementWithGuid( ?string $statementGuid ): ?int {
		foreach ( $this->statements as $index => $statement ) {
			if ( $statement->getGuid() === $statementGuid ) {
				return $index;
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
	public function filter( StatementFilter $filter ): self {
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
	public function clear(): void {
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
