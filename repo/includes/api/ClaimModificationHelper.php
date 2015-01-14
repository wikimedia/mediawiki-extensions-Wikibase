<?php

namespace Wikibase\Api;

use ApiBase;
use DataValues\IllegalValueException;
use FormatJson;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use UsageException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\PropertyNotFoundException;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Lib\SnakConstructionService;
use Wikibase\Summary;

/**
 * Helper class for modifying claims
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 * @author Daniel Kinzler
 */
class ClaimModificationHelper {

	/**
	 * @since 0.4
	 *
	 * @var SnakConstructionService
	 */
	protected $snakConstructionService;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @since 0.4
	 *
	 * @var ClaimGuidValidator
	 */
	protected $claimGuidValidator;

	/**
	 * @since 0.4
	 *
	 * @var ApiErrorReporter
	 * @param SnakConstructionService $snakConstructionService
	 * @param EntityIdParser $entityIdParser
	 * @param ClaimGuidValidator $claimGuidValidator
	 * @param ApiErrorReporter $errorReporter
	 */
	public function __construct(
		SnakConstructionService $snakConstructionService,
		EntityIdParser $entityIdParser,
		ClaimGuidValidator $claimGuidValidator,
		ApiErrorReporter $errorReporter
	) {
		$this->snakConstructionService = $snakConstructionService;
		$this->entityIdParser = $entityIdParser;
		$this->claimGuidValidator = $claimGuidValidator;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 *
	 * @throws UsageException
	 * @return bool
	 */
	public function validateClaimGuid( $claimGuid ) {
		return $this->claimGuidValidator->validate( $claimGuid );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Entity $entity
	 *
	 * @throws UsageException
	 * @return Claim
	 */
	public function getClaimFromEntity( $claimGuid, Entity $entity ) {
		$claims = new Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $claimGuid ) ) {
			$this->errorReporter->dieError( 'Could not find the claim' , 'no-such-claim' );
		}

		return $claims->getClaimWithGuid( $claimGuid );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @param PropertyId $propertyId
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return Snak
	 */
	public function getSnakInstance( $params, PropertyId $propertyId ) {
		$valueData = null;
		if ( isset( $params['value'] ) ) {
			$valueData = FormatJson::decode( $params['value'], true );
			if ( $valueData === null ) {
				$this->errorReporter->dieError( 'Could not decode snak value', 'invalid-snak' );
			}
		}

		try {
			$snak = $this->snakConstructionService->newSnak( $propertyId, $params['snaktype'], $valueData );
			return $snak;
		} catch ( IllegalValueException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-snak' );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-snak' );
		} catch ( OutOfBoundsException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-snak' );
		} catch ( PropertyNotFoundException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-snak' );
		}

		throw new LogicException( 'ClaimModificationHelper::throwUsageException did not throw a UsageException.' );
	}

	/**
	 * Parses an entity id string coming from the user
	 *
	 * @since 0.4
	 *
	 * @param string $entityIdParam
	 *
	 * @throws UsageException
	 * @return EntityId
	 * @todo this could go into an EntityModificationHelper or even in a ApiWikibaseHelper
	 */
	public function getEntityIdFromString( $entityIdParam ) {
		try {
			$entityId = $this->entityIdParser->parse( $entityIdParam );
		} catch ( EntityIdParsingException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-entity-id' );
		}

		/** @var EntityId $entityId */
		return $entityId;
	}

	/**
	 * Creates a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @since 0.4
	 *
	 * @param array $params
	 * @param ApiBase $module
	 *
	 * @return Summary
	 */
	public function createSummary( array $params, ApiBase $module ) {
		$summary = new Summary( $module->getModuleName() );
		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * Applies the given ChangeOp to the given Entity.
	 * Any ChangeOpException is converted into a UsageException with the code 'modification-failed'.
	 *
	 * @param ChangeOp $changeOp
	 * @param Entity $entity
	 * @param Summary $summary The summary object to update with information about the change.
	 */
	public function applyChangeOp( ChangeOp $changeOp, Entity $entity, Summary $summary = null ) {
		try {
			$result = $changeOp->validate( $entity );

			if ( !$result->isValid() ) {
				throw new ChangeOpValidationException( $result );
			}

			$changeOp->apply( $entity, $summary );
		} catch ( ChangeOpException $ex ) {
			$this->errorReporter->dieException( $ex, 'modification-failed' );
		}
	}

}
