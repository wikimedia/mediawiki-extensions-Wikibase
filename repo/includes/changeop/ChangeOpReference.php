<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Reference;
use Wikibase\References;
use Wikibase\Statement;
use Wikibase\PropertyValueSnak;
use Wikibase\Lib\EntityIdFormatter;

/**
 * Class for reference change operation
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpReference extends ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @since 0.4
	 *
	 * @var Reference|null
	 */
	protected $reference;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $referenceHash;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdFormatter
	 */
	protected $idFormatter;

	/**
	 * Constructs a new reference change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Reference|null $reference
	 * @param string $referenceHash
	 * @param EntityIdFormatter $entityIdFormatter
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, $reference, $referenceHash, EntityIdFormatter $idFormatter ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $referenceHash ) ) {
			throw new InvalidArgumentException( '$referenceHash needs to be a string' );
		}

		if ( !( $reference instanceof Reference ) && !is_null( $reference ) ) {
			throw new InvalidArgumentException( '$reference needs to be an instance of Reference or null' );
		}

		if ( $referenceHash === '' && $reference === null ) {
			throw new InvalidArgumentException( 'Either $referenceHash or $reference needs to be set' );
		}

		$this->claimGuid = $claimGuid;
		$this->reference = $reference;
		$this->referenceHash = $referenceHash;
		$this->idFormatter = $idFormatter;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * - the reference gets removed when $referenceHash is set and $reference is not set
	 * - a new reference gets added when $referenceHash is empty and $reference is set
	 * - the reference gets set to $reference when $referenceHash and $reference are set
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool
	 *
	 * @throws ChangeOpException
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$claims = new Claims( $entity->getClaims() );

		if( !$claims->hasClaimWithGuid( $this->claimGuid ) ) {
			throw new ChangeOpException( "Entity does not have claim with GUID $this->claimGuid" );
		}

		$claim = $claims->getClaimWithGuid( $this->claimGuid );

		if ( ! ( $claim instanceof Statement ) ) {
			throw new ChangeOpException( 'The referenced claim is not a statement and thus cannot have references' );
		}

		$references = $claim->getReferences();

		if ( $this->referenceHash === '' ) {
			$this->addReference( $references, $summary );
		} else {
			if ( $this->reference != null ) {
				$this->setReference( $references, $summary );
			} else {
				$this->removeReference( $references, $summary );
			}
		}

		$claim->setReferences( $references );
		$entity->setClaims( $claims );

		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param References $references
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function addReference( References $references, Summary $summary = null ) {
		if ( $references->hasReference( $this->reference ) ) {
			throw new ChangeOpException( "Claim has already a reference with hash $this->reference->getHash()" );
		}
		$references->addReference( $this->reference );
		//TODO: add the mainsnak as autocomment-arg & change messages
		#$this->updateSummary( $summary, 'add', '', $this->getReferenceSummaryArgs( $this->reference ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param References $references
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function setReference( References $references, Summary $summary = null ) {
		if ( !$references->hasReferenceHash( $this->referenceHash ) ) {
			throw new ChangeOpException( "Reference with hash $this->referenceHash does not exist" );
		}
		if ( $references->hasReference( $this->reference ) ) {
			throw new ChangeOpException( "Claim has already a reference with hash $this->reference->getHash()" );
		}
		$references->removeReferenceHash( $this->referenceHash );
		$references->addReference( $this->reference );
		#$this->updateSummary( $summary, 'update', '', $this->getReferenceSummaryArgs( $this->reference ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param References $references
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function removeReference( References $references, Summary $summary = null ) {
		if ( !$references->hasReferenceHash( $this->referenceHash ) ) {
			throw new ChangeOpException( "Reference with hash $this->referenceHash does not exist" );
		}
		$removedReference = $references->getReference( $this->referenceHash );
		$references->removeReferenceHash( $this->referenceHash );
		$this->updateSummary( $summary, 'remove', '', $this->getReferenceSummaryArgs( $removedReference ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Reference $reference
	 *
	 * @return array
	 *
	 * @todo: REUSE!!
	 */
	protected function getReferenceSummaryArgs( Reference $reference ) {
		$args = array();
		$snaks = $reference->getSnaks();

		foreach ( $snaks as $snak ) {
			$propertyId = $this->idFormatter->format( $snak->getPropertyId() );

			if ( !array_key_exists( $propertyId, $args ) ) {
				$args[$propertyId] = array();
			}

			if ( $snak instanceof PropertyValueSnak ) {
				$value = $snak->getDataValue();
				// TODO: we should use value formatters here!
				if ( $value instanceof TimeValue ) {
					$value = $value->getTime();
				}
			} else {
				$value = '-'; // todo handle no values in general way (needed elsewhere)
			}

			$args[$propertyId][] = $value;
		}

		return array( $args );
	}
}
