<?php

namespace Wikibase\DataModel\Claim;

use ArrayObject;
use Comparable;
use Hashable;
use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\ByPropertyIdGrouper;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * A statement (identified using it's GUID) can only be added once.
 *
 * @deprecated since 1.0 - use StatementList and associated classes instead
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 */
class Claims extends ArrayObject implements Hashable, Comparable {

	/**
	 * @see GenericArrayObject::__construct
	 *
	 * @since 0.3
	 *
	 * @param Statement[]|Traversable|null $input
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $input = null ) {
		parent::__construct( array() );

		if ( $input !== null ) {
			if ( !is_array( $input ) && !( $input instanceof Traversable ) ) {
				throw new InvalidArgumentException( '$input must be an array or an instance of Traversable' );
			}

			foreach ( $input as $statement ) {
				$this[] = $statement;
			}
		}
	}

	/**
	 * @param string $guid
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function getGuidKey( $guid ) {
		if ( !is_string( $guid ) ) {
			throw new InvalidArgumentException( '$guid must be a string; got ' . gettype( $guid ) );
		}

		$key = strtoupper( $guid );
		return $key;
	}

	/**
	 * @param Statement $statement
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function getClaimKey( Statement $statement ) {
		$guid = $statement->getGuid();

		if ( $guid === null ) {
			throw new InvalidArgumentException( 'Can\'t handle claims with no GUID set!' );
		}

		$key = $this->getGuidKey( $guid );
		return $key;
	}

	/**
	 * @since 0.1
	 *
	 * @param Statement $statement
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function addClaim( Statement $statement, $index = null ) {
		if ( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( '$index must be an integer or null; got ' . gettype( $index ) );
		} elseif ( is_null( $index ) || $index >= count( $this ) ) {
			$this[] = $statement;
		} else {
			$this->insertClaimAtIndex( $statement, $index );
		}
	}

	/**
	 * @param Statement $statement
	 * @param int $index
	 */
	private function insertClaimAtIndex( Statement $statement, $index ) {
		// Determine the claims to shift and remove them from the array:
		$statementsToShift = array_slice( (array)$this, $index );

		foreach ( $statementsToShift as $object ) {
			$this->offsetUnset( $this->getClaimKey( $object ) );
		}

		// Append the new statement and re-append the previously removed claims:
		$this[] = $statement;

		foreach ( $statementsToShift as $object ) {
			$this[] = $object;
		}
	}

	/**
	 * @since 0.1
	 *
	 * @param Statement $statement
	 *
	 * @return bool
	 */
	public function hasClaim( Statement $statement ) {
		$guid = $statement->getGuid();

		if ( $guid === null ) {
			return false;
		}

		$key = $this->getGuidKey( $guid );
		return $this->offsetExists( $key );
	}

	/**
	 * @since 0.5
	 *
	 * @param Statement $statement
	 *
	 * @return int|bool
	 */
	public function indexOf( Statement $statement ) {
		$guid = $statement->getGuid();
		$index = 0;

		/**
		 * @var Statement $statementObject
		 */
		foreach ( $this as $statementObject ) {
			if ( $statementObject->getGuid() === $guid ) {
				return $index;
			}
			$index++;
		}

		return false;
	}

	/**
	 * @since 0.1
	 *
	 * @param Statement $statement
	 */
	public function removeClaim( Statement $statement ) {
		$guid = $statement->getGuid();

		if ( $guid === null ) {
			return;
		}

		$key = $this->getGuidKey( $guid );

		if ( $this->offsetExists( $key ) ) {
			$this->offsetUnset( $key );
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param string $statementGuid
	 *
	 * @return bool
	 */
	public function hasClaimWithGuid( $statementGuid ) {
		return $this->offsetExists( $statementGuid );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $statementGuid
	 */
	public function removeClaimWithGuid( $statementGuid ) {
		if ( $this->offsetExists( $statementGuid ) ) {
			$this->offsetUnset( $statementGuid );
		}
	}

	/**
	 * @since 0.3
	 *
	 * @param string $statementGuid
	 *
	 * @return Statement|null
	 */
	public function getClaimWithGuid( $statementGuid ) {
		if ( $this->offsetExists( $statementGuid ) ) {
			return $this->offsetGet( $statementGuid );
		} else {
			return null;
		}
	}

	/**
	 * @see ArrayAccess::offsetExists
	 *
	 * @param string $guid
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetExists( $guid ) {
		$key = $this->getGuidKey( $guid );
		return parent::offsetExists( $key );
	}

	/**
	 * @see ArrayAccess::offsetGet
	 *
	 * @param string $guid
	 *
	 * @return Statement
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetGet( $guid ) {
		$key = $this->getGuidKey( $guid );
		return parent::offsetGet( $key );
	}

	/**
	 * @see ArrayAccess::offsetSet
	 *
	 * @param string $guid
	 * @param Statement $statement
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetSet( $guid, $statement ) {
		if ( !( $statement instanceof Statement ) ) {
			throw new InvalidArgumentException( '$statement must be an instance of Claim' );
		}

		$statementKey = $this->getClaimKey( $statement );

		if ( $guid !== null ) {
			$guidKey = $this->getGuidKey( $guid );

			if ( $guidKey !== $statementKey ) {
				throw new InvalidArgumentException( 'The key must be the claim\'s GUID.' );
			}
		}

		parent::offsetSet( $statementKey, $statement );
	}

	/**
	 * @see ArrayAccess::offsetUnset
	 *
	 * @param string $guid
	 */
	public function offsetUnset( $guid ) {
		$key = $this->getGuidKey( $guid );
		parent::offsetUnset( $key );
	}

	/**
	 * Get array of Statement guids
	 *
	 * @since 0.4
	 *
	 * @return string[]
	 */
	public function getGuids() {
		return array_map( function ( Statement $statement ) {
			return $statement->getGuid();
		}, iterator_to_array( $this ) );
	}

	/**
	 * Returns the claims for the given property.
	 *
	 * @since 0.4
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return Claims
	 */
	public function getClaimsForProperty( PropertyId $propertyId ) {
		$byPropertyIdGrouper = new ByPropertyIdGrouper( $this );

		if ( !$byPropertyIdGrouper->hasPropertyId( $propertyId ) ) {
			return new self();
		}

		return new self( $byPropertyIdGrouper->getByPropertyId( $propertyId ) );
	}

	/**
	 * Returns the main Snaks of the claims in this list.
	 *
	 * @since 0.4
	 *
	 * @return Snak[]
	 */
	public function getMainSnaks() {
		$snaks = array();

		/* @var Statement $statement */
		foreach ( $this as $statement ) {
			$guid = $statement->getGuid();
			$snaks[$guid] = $statement->getMainSnak();
		}

		return $snaks;
	}

	/**
	 * Returns a map from GUIDs to statement hashes.
	 *
	 * @since 0.4
	 *
	 * @return string[]
	 */
	public function getHashes() {
		$snaks = array();

		/* @var Statement $statement */
		foreach ( $this as $statement ) {
			$guid = $statement->getGuid();
			$snaks[$guid] = $statement->getHash();
		}

		return $snaks;
	}

	/**
	 * Returns a hash based on the value of the object.
	 * The hash is based on the hashes of the claims contained,
	 * with the order of claims considered significant.
	 *
	 * @since 0.5
	 *
	 * @return string
	 */
	public function getHash() {
		$hash = sha1( '' );

		/* @var Statement $statement */
		foreach ( $this as $statement ) {
			$hash = sha1( $hash . $statement->getHash() );
		}

		return $hash;
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return !$this->getIterator()->valid();
	}

	/**
	 * Returns a new instance only containing the claims with the given rank.
	 *
	 * @since 0.7
	 *
	 * @param int $rank
	 *
	 * @return Claims
	 */
	public function getByRank( $rank ) {
		$statements = new self();

		/* @var Statement $statement */
		foreach ( $this as $statement ) {
			if ( $statement->getRank() === $rank ) {
				$statements[] = $statement;
			}
		}

		return $statements;
	}

	/**
	 * Returns a new instance only containing the claims with the given ranks.
	 *
	 * @since 0.7.2
	 *
	 * @param int[] $ranks
	 *
	 * @return Claims
	 */
	public function getByRanks( array $ranks ) {
		$ranks = array_flip( $ranks );
		$statements = new self();

		/* @var Statement $statement */
		foreach ( $this as $statement ) {
			if ( isset( $ranks[$statement->getRank()] ) ) {
				$statements[] = $statement;
			}
		}

		return $statements;
	}

	/**
	 * Returns a new instance only containing the best claims (these are the highest
	 * ranked claims, but never deprecated ones). This implementation ignores the properties
	 * so you probably want to call Claims::getClaimsForProperty first or use
	 * StatementList::getBestStatementPerProperty instead.
	 *
	 * @see Claims::getClaimsForProperty
	 * @see StatementList::getBestStatementPerProperty
	 *
	 * @since 0.7
	 *
	 * @return Claims
	 */
	public function getBestClaims() {
		$rank = Statement::RANK_PREFERRED;

		do {
			$statements = $this->getByRank( $rank );
			$rank--;
		} while ( $statements->isEmpty() && $rank > Statement::RANK_DEPRECATED );

		return $statements;
	}

	/**
	 * @see Comparable::equals
	 *
	 * @since 0.7.4
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

		foreach ( $this as $statement ) {
			if ( !$target->hasExactClaim( $statement ) ) {
				return false;
			}
		}

		return true;
	}

	private function hasExactClaim( Statement $statement ) {
		return $this->hasClaim( $statement )
			&& $this->getClaimWithGuid( $statement->getGuid() )->equals( $statement );
	}

}
