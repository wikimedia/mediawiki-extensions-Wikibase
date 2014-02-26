<?php

namespace Wikibase\Api;

use DataValues\IllegalValueException;
use FormatJson;
use InvalidArgumentException;
use OutOfBoundsException;
use Profiler;
use Title;
use UsageException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\PropertyNotFoundException;
use Wikibase\Lib\SnakConstructionService;
use ApiBase;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Lib\ClaimGuidValidator;

/**
 * Helper class for modifying claims
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
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
	 * @var SnakValidationHelper
	 */
	protected $snakValidation;

	/**
	 * @since 0.4
	 *
	 * @param SnakConstructionService $snakConstructionService
	 * @param EntityIdParser $entityIdParser
	 * @param ClaimGuidValidator $claimGuidValidator
	 * @param SnakValidationHelper $snakValidation
	 */
	public function __construct(
		SnakConstructionService $snakConstructionService,
		EntityIdParser $entityIdParser,
		ClaimGuidValidator $claimGuidValidator,
		SnakValidationHelper $snakValidation
	) {
		$this->snakConstructionService = $snakConstructionService;
		$this->entityIdParser = $entityIdParser;
		$this->claimGuidValidator = $claimGuidValidator;
		$this->snakValidation = $snakValidation;
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @throws UsageException
	 * @return Title
	 *
	 * TODO: this could go into a ApiWikibaseHelper as it is useful for almost all API modules
	 */
	public function getEntityTitle( EntityId $entityId ) {
		$entityTitle = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->throwUsageException( 'No such entity' , 'no-such-entity' );
		}

		return $entityTitle;
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
			$this->throwUsageException( 'Could not find the claim' , 'no-such-claim' );
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
	 * @return Snak
	 */
	public function getSnakInstance( $params, PropertyId $propertyId ) {
		$valueData = null;
		if ( isset( $params['value'] ) ) {
			$valueData = FormatJson::decode( $params['value'], true );
			if ( $valueData === null ) {
				$this->throwUsageException( 'Could not decode snak value', 'invalid-snak' );
			}
		}

		try {
			$snak = $this->snakConstructionService->newSnak( $propertyId, $params['snaktype'], $valueData );
		}
		catch ( IllegalValueException $ex ) {
			$this->throwUsageException( 'Invalid snak: IllegalValueException', 'invalid-snak' );
		}
		catch ( InvalidArgumentException $ex ) {
			// shouldn't happen, but might.
			$this->throwUsageException( 'Invalid snak: InvalidArgumentException', 'invalid-snak' );
		}
		catch ( OutOfBoundsException $ex ) {
			$this->throwUsageException( 'Invalid snak: OutOfBoundsException' . $ex->getMessage(), 'invalid-snak' );
		}
		catch ( PropertyNotFoundException $ex ) {
			$this->throwUsageException( 'Invalid snak: PropertyNotFoundException' . $ex->getMessage(), 'invalid-snak' );
		}

		/** @var Snak $snak */
		$this->snakValidation->validateSnak( $snak );
		return $snak;
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
		} catch ( EntityIdParsingException $parseException ) {
			$this->throwUsageException( 'Invalid entity ID: ParseException', 'invalid-entity-id' );
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
	 * @param $message
	 * @param $code
	 *
	 * @throws UsageException
	 */
	private function throwUsageException( $message, $code ) {
		Profiler::instance()->close();
		throw new UsageException( $message, $code );
	}

}
