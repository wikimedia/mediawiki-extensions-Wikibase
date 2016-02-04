<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Wikibase\ChangeOp\ChangeOpQualifier;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\WikibaseRepo;

/**
 * API module for creating a qualifier or setting the value of an existing one.
 *
 * @since 0.3
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetQualifier extends ApiBase {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var StatementModificationHelper
	 */
	private $modificationHelper;

	/**
	 * @var StatementGuidParser
	 */
	private $guidParser;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntityLoadingHelper
	 */
	private $entityLoadingHelper;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();

		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();

		$this->modificationHelper = new StatementModificationHelper(
			$wikibaseRepo->getSnakFactory(),
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getStatementGuidValidator(),
			$apiHelperFactory->getErrorReporter( $this )
		);

		$this->guidParser = $wikibaseRepo->getStatementGuidParser();
		$this->resultBuilder = $apiHelperFactory->getResultBuilder( $this );
		$this->entityLoadingHelper = $apiHelperFactory->getEntityLoadingHelper( $this );
		$this->entitySavingHelper = $apiHelperFactory->getEntitySavingHelper( $this );
	}

	/**
	 * @see ApiBase::execute
	 *
	 * @since 0.3
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->guidParser->parse( $params['claim'] )->getEntityId();
		if ( isset( $params['baserevid'] ) ) {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision(
				$entityId,
				(int)$params['baserevid']
			);
		} else {
			$entityRevision = $this->entityLoadingHelper->loadEntityRevision( $entityId );
		}
		$entity = $entityRevision->getEntity();

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$statement = $this->modificationHelper->getStatementFromEntity( $params['claim'], $entity );

		if ( isset( $params['snakhash'] ) ) {
			$this->validateQualifierHash( $statement, $params['snakhash'] );
		}

		$changeOp = $this->getChangeOp();
		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, EDIT_UPDATE );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addStatement( $statement );
	}

	/**
	 * Checks if the required parameters are set and the ones that make no sense given the
	 * snaktype value are not set.
	 */
	private function validateParameters( array $params ) {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['claim'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}

		if ( !isset( $params['snakhash'] ) ) {
			if ( !isset( $params['snaktype'] ) ) {
				$this->errorReporter->dieError(
					'When creating a new qualifier (ie when not providing a snakhash) a snaktype should be specified',
					'param-missing'
				);
			}

			if ( !isset( $params['property'] ) ) {
				$this->errorReporter->dieError(
					'When creating a new qualifier (ie when not providing a snakhash) a property should be specified',
					'param-missing'
				);
			}
		}

		if ( isset( $params['snaktype'] ) && $params['snaktype'] === 'value' && !isset( $params['value'] ) ) {
			$this->errorReporter->dieError(
				'When setting a qualifier that is a PropertyValueSnak, the value needs to be provided',
				'param-missing'
			);
		}
	}

	/**
	 * @param Statement $statement
	 * @param string $qualifierHash
	 */
	private function validateQualifierHash( Statement $statement, $qualifierHash ) {
		if ( !$statement->getQualifiers()->hasSnakHash( $qualifierHash ) ) {
			$this->errorReporter->dieError(
				'Claim does not have a qualifier with the given hash',
				'no-such-qualifier'
			);
		}
	}

	/**
	 * @return ChangeOpQualifier
	 */
	private function getChangeOp() {
		$params = $this->extractRequestParams();

		$guid = $params['claim'];

		$propertyId = $this->modificationHelper->getEntityIdFromString( $params['property'] );
		if ( !( $propertyId instanceof PropertyId ) ) {
			$this->errorReporter->dieError(
				$propertyId->getSerialization() . ' does not appear to be a property ID',
				'param-illegal'
			);
		}
		$newQualifier = $this->modificationHelper->getSnakInstance( $params, $propertyId );

		$snakHash = isset( $params['snakhash'] ) ? $params['snakhash'] : '';
		$changeOp = $this->statementChangeOpFactory->newSetQualifierOp( $guid, $newQualifier, $snakHash );

		return $changeOp;
	}

	/**
	 * @see ApiBase::isWriteMode
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			array(
				'claim' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				),
				'property' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => false,
				),
				'value' => array(
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => false,
				),
				'snaktype' => array(
					self::PARAM_TYPE => array( 'value', 'novalue', 'somevalue' ),
					self::PARAM_REQUIRED => false,
				),
				'snakhash' => array(
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => false,
				),
				'summary' => array(
					self::PARAM_TYPE => 'string',
				),
				'token' => null,
				'baserevid' => array(
					self::PARAM_TYPE => 'integer',
				),
				'bot' => false,
			),
			parent::getAllowedParams()
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			'action=wbsetqualifier&claim=Q2$4554c0f4-47b2-1cd9-2db9-aa270064c9f3&property=P1'
				. '&value="GdyjxP8I6XB3"&snaktype=value&token=foobar'
				=> 'apihelp-wbsetqualifier-example-1',
		);
	}

}
