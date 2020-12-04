<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiUsageException;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpValidationException;
use Wikibase\Repo\SnakFactory;

/**
 * Helper class for modifying an entities statements.
 *
 * @license GPL-2.0-or-later
 */
class StatementModificationHelper {

	/**
	 * @var SnakFactory
	 */
	private $snakFactory;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var StatementGuidValidator
	 */
	private $guidValidator;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	public function __construct(
		SnakFactory $snakFactory,
		EntityIdParser $entityIdParser,
		StatementGuidValidator $guidValidator,
		ApiErrorReporter $errorReporter
	) {
		$this->snakFactory = $snakFactory;
		$this->entityIdParser = $entityIdParser;
		$this->guidValidator = $guidValidator;
		$this->errorReporter = $errorReporter;
	}

	/**
	 * @param string $guid
	 *
	 * @return bool
	 */
	public function validateStatementGuid( $guid ) {
		return $this->guidValidator->validate( $guid );
	}

	/**
	 * @param string $guid
	 * @param EntityDocument $entity
	 *
	 * @throws ApiUsageException
	 * @return Statement
	 */
	public function getStatementFromEntity( $guid, EntityDocument $entity ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			$this->errorReporter->dieError( 'Entity type does not support statements', 'no-such-claim' );
		}

		$statement = $entity->getStatements()->getFirstStatementWithGuid( $guid );

		if ( $statement === null ) {
			$this->errorReporter->dieError( 'Could not find the statement', 'no-such-claim' );
		}

		return $statement;
	}

	/**
	 * @param string[] $params Array with a 'snaktype' and an optional 'value' element.
	 * @param PropertyId $propertyId
	 *
	 * @throws ApiUsageException
	 * @throws LogicException
	 * @return Snak
	 */
	public function getSnakInstance( array $params, PropertyId $propertyId ) {
		$valueData = null;

		if ( isset( $params['value'] ) ) {
			$valueData = json_decode( $params['value'], true );

			if ( $valueData === null ) {
				$this->errorReporter->dieError( 'Could not decode snak value', 'invalid-snak' );
			}
		}

		try {
			$snak = $this->snakFactory->newSnak( $propertyId, $params['snaktype'], $valueData );
			return $snak;
		} catch ( PropertyDataTypeLookupException $ex ) {
			$this->errorReporter->dieException( $ex, 'no-such-entity' );
		} catch ( InvalidArgumentException | OutOfBoundsException $ex ) {
			$this->errorReporter->dieException( $ex, 'invalid-snak' );
		}

		throw new LogicException( 'ApiErrorReporter::dieException did not throw an exception' );
	}

	/**
	 * Parses an entity id string coming from the user
	 *
	 * @param string $entityIdParam
	 *
	 * @throws ApiUsageException
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
	 * Any ChangeOpException is converted into an ApiUsageException with the code 'modification-failed'.
	 *
	 * @param ChangeOp $changeOp
	 * @param EntityDocument $entity
	 * @param Summary|null $summary The summary object to update with information about the change.
	 */
	public function applyChangeOp( ChangeOp $changeOp, EntityDocument $entity, Summary $summary = null ) {
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
