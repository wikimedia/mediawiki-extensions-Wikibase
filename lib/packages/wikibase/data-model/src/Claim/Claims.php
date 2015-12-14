<?php

namespace Wikibase\DataModel\Claim;

use ArrayObject;
use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Statement\Statement;

/**
 * A claim (identified using it's GUID) can only be added once.
 *
 * @deprecated since 1.0, use StatementList and associated classes instead.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author H. Snater < mediawiki@snater.com >
 */
class Claims extends ArrayObject {

	/**
	 * @see GenericArrayObject::__construct
	 *
	 * @since 0.3
	 * @deprecated since 1.0, use StatementList instead.
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

			foreach ( $input as $claim ) {
				$this[] = $claim;
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
			throw new InvalidArgumentException( 'Can\'t handle statements with no GUID set' );
		}

		$key = $this->getGuidKey( $guid );
		return $key;
	}

	/**
	 * @since 0.1
	 * @deprecated since 1.0, use StatementList::addStatement() instead.
	 *
	 * @param Statement $statement
	 *
	 * @throws InvalidArgumentException
	 */
	public function addClaim( Statement $statement ) {
		if ( func_num_args() > 1 ) {
			throw new InvalidArgumentException( '$index is not supported any more' );
		}

		$this[] = $statement;
	}

	/**
	 * @since 0.3
	 * @deprecated since 1.0, use StatementList::getFirstStatementByGuid() instead.
	 *
	 * @param string $claimGuid
	 *
	 * @return bool
	 */
	public function hasClaimWithGuid( $claimGuid ) {
		return $this->offsetExists( $claimGuid );
	}

	/**
	 * @since 0.3
	 * @deprecated since 1.0, should not be needed any more.
	 *
	 * @param string $claimGuid
	 */
	public function removeClaimWithGuid( $claimGuid ) {
		if ( $this->offsetExists( $claimGuid ) ) {
			$this->offsetUnset( $claimGuid );
		}
	}

	/**
	 * @since 0.3
	 * @deprecated since 1.0, use StatementList::getFirstStatementByGuid() instead.
	 *
	 * @param string $claimGuid
	 *
	 * @return Statement|null
	 */
	public function getClaimWithGuid( $claimGuid ) {
		if ( $this->offsetExists( $claimGuid ) ) {
			return $this->offsetGet( $claimGuid );
		} else {
			return null;
		}
	}

	/**
	 * @see ArrayAccess::offsetExists
	 * @deprecated since 1.0, should never be called.
	 *
	 * @param string $guid
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function offsetExists( $guid ) {
		$key = $this->getGuidKey( $guid );
		return parent::offsetExists( $key );
	}

	/**
	 * @see ArrayAccess::offsetGet
	 * @deprecated since 1.0, should never be called.
	 *
	 * @param string $guid
	 *
	 * @return Statement
	 * @throws InvalidArgumentException
	 */
	public function offsetGet( $guid ) {
		$key = $this->getGuidKey( $guid );
		return parent::offsetGet( $key );
	}

	/**
	 * @see ArrayAccess::offsetSet
	 * @deprecated since 1.0, should never be called.
	 *
	 * @param string $guid
	 * @param Statement $statement
	 *
	 * @throws InvalidArgumentException
	 */
	public function offsetSet( $guid, $statement ) {
		if ( !( $statement instanceof Statement ) ) {
			throw new InvalidArgumentException( '$statement must be an instance of Statement' );
		}

		$claimKey = $this->getClaimKey( $statement );

		if ( $guid !== null ) {
			$guidKey = $this->getGuidKey( $guid );

			if ( $guidKey !== $claimKey ) {
				throw new InvalidArgumentException( 'The key must be the claim\'s GUID.' );
			}
		}

		parent::offsetSet( $claimKey, $statement );
	}

	/**
	 * @see ArrayAccess::offsetUnset
	 * @deprecated since 1.0, should never be called.
	 *
	 * @param string $guid
	 */
	public function offsetUnset( $guid ) {
		$key = $this->getGuidKey( $guid );
		parent::offsetUnset( $key );
	}

}
