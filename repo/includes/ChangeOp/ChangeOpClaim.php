<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueValidators\Result;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Summary;
use Wikibase\Validators\SnakValidator;

/**
 * Class for claim modification operations
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author H. Snater < mediawiki@snater.com >
 */
class ChangeOpClaim extends ChangeOpBase {

	/**
	 * @var Claim
	 */
	private $claim;

	/**
	 * @var ClaimGuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @var ClaimGuidValidator
	 */
	private $guidValidator;

	/**
	 * @var ClaimGuidParser
	 */
	private $guidParser;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * @var int|null
	 */
	private $index;

	/**
	 * @param Claim $claim
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param ClaimGuidParser $guidParser
	 * @param SnakValidator $snakValidator
	 * @param int|null $index Where the claim should be placed among the other claims.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		Claim $claim,
		ClaimGuidGenerator $guidGenerator,
		ClaimGuidValidator $guidValidator,
		ClaimGuidParser $guidParser,
		SnakValidator $snakValidator,
		$index = null
	) {
		if( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( '$index needs to be null or an integer value' );
		}

		$this->claim = $claim;
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->snakValidator = $snakValidator;
		$this->index = $index;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if( $this->claim->getGuid() === null ){
			$this->claim->setGuid( $this->guidGenerator->newGuid( $entity->getId() ) );
		}
		$guid = $this->guidParser->parse( $this->claim->getGuid() );

		if ( $this->guidValidator->validate( $guid->getSerialization() ) === false ) {
			throw new ChangeOpException( "Claim does not have a valid GUID" );
		} elseif ( !$entity->getId()->equals( $guid->getEntityId() ) ){
			throw new ChangeOpException( "Claim GUID invalid for given entity" );
		}

		$entityClaims = $entity->getClaims();
		$claims = new Claims( $entityClaims );

		if( !$claims->hasClaimWithGuid( $this->claim->getGuid() ) ) {
			$newClaimList = $this->addClaim( $claims, $summary );
		} else {
			$newClaimList = $this->setClaim( $claims, $summary );
		}

		$claims = new Claims( $newClaimList );
		$entity->setStatements( new StatementList( iterator_to_array( $claims ) ) );

		return true;
	}

	/**
	 * @param Claims $claims
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 *
	 * @return Claim[]
	 */
	protected function addClaim( Claims $claims, Summary $summary = null ) {
		$this->updateSummary( $summary, 'create' );

		$indexedClaimList = new ByPropertyIdArray( (array)$claims );
		$indexedClaimList->buildIndex();

		try {
			$indexedClaimList->addObjectAtIndex( $this->claim, $this->index );
		}
		catch ( OutOfBoundsException $e ) {
			if ( $this->index < 0 ) {
				throw new ChangeOpException( 'Can not add claim at given index: '. $this->index );
			} else {
				// XXX: hack below to retry adding the object at a new index
				// If we fail with the user supplied index and the index is greater than 0
				// presume the user wants to have the index at the end of the list
				$this->addObjectAtEndOfList( $indexedClaimList );
			}
		}

		return $indexedClaimList->toFlatArray();
	}

	/**
	 * @param Claims $claims
	 * @param Summary $summary
	 *
	 * @return Claim[]
	 */
	protected function setClaim( Claims $claims, Summary $summary = null ) {
		$this->updateSummary( $summary, 'update' );

		$claimGuid = $this->claim->getGuid();
		$oldClaim = $claims->getClaimWithGuid( $claimGuid );
		$this->checkMainSnakUpdate( $oldClaim );

		if ( $this->index === null ) {
			$this->index = $claims->indexOf( $this->claim );
		}

		$claims->removeClaimWithGuid( $claimGuid );
		return $this->addClaim( $claims );
	}

	/**
	 * Checks that the update of the main snak is permissible.
	 *
	 * This checks that the main snaks of the old and the new claim
	 * refer to the same property.
	 *
	 * @param Claim $oldClaim
	 *
	 * @throws ChangeOpException If the main snak update is illegal.
	 */
	protected function checkMainSnakUpdate( Claim $oldClaim ) {
		$newMainSnak = $this->claim->getMainSnak();
		$oldPropertyId = $oldClaim->getMainSnak()->getPropertyId();

		if ( !$oldPropertyId->equals( $newMainSnak->getPropertyId() ) ) {
			$claimGuid = $this->claim->getGuid();
			throw new ChangeOpException( "Claim with GUID $claimGuid uses property "
				. $oldPropertyId . ", can't change to "
				. $newMainSnak->getPropertyId() );
		}
	}

	/**
	 * @see Bug 58394
	 * @param ByPropertyIdArray $indexedClaimList
	 */
	private function addObjectAtEndOfList( $indexedClaimList ) {
		$newIndex = $indexedClaimList->count() + 1;
		$indexedClaimList->addObjectAtIndex( $this->claim, $newIndex );
	}

	/**
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 *
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		return $this->snakValidator->validateClaimSnaks( $this->claim );
	}

}
