<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use Deserializers\Exceptions\DeserializationException;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\Repo\SnakFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module for creating a reference or setting the value of an existing one.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class SetReference extends ApiBase {

	use FederatedPropertyApiValidatorTrait;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var ApiErrorReporter
	 */
	protected $errorReporter;

	/**
	 * @var DeserializerFactory
	 */
	private $deserializerFactory;

	/**
	 * @var StatementModificationHelper
	 */
	private $modificationHelper;

	/**
	 * @var StatementGuidParser
	 */
	private $guidParser;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntitySavingHelper
	 */
	private $entitySavingHelper;

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		DeserializerFactory $deserializerFactory,
		ApiErrorReporter $errorReporter,
		StatementChangeOpFactory $statementChangeOpFactory,
		StatementModificationHelper $modificationHelper,
		StatementGuidParser $guidParser,
		LoggerInterface $logger,
		callable $resultBuilderInstantiator,
		callable $entitySavingHelperInstantiator,
		bool $federatedPropertiesEnabled,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->deserializerFactory = $deserializerFactory;
		$this->errorReporter = $errorReporter;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->modificationHelper = $modificationHelper;
		$this->guidParser = $guidParser;
		$this->logger = $logger;
		$this->resultBuilder = $resultBuilderInstantiator( $this );
		$this->entitySavingHelper = $entitySavingHelperInstantiator( $this );
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		ApiHelperFactory $apiHelperFactory,
		DeserializerFactory $deserializerFactory,
		ChangeOpFactoryProvider $changeOpFactoryProvider,
		EntityIdParser $entityIdParser,
		LoggerInterface $logger,
		SettingsArray $repoSettings,
		SnakFactory $snakFactory,
		StatementGuidParser $statementGuidParser,
		StatementGuidValidator $statementGuidValidator
	): self {
		$modificationHelper = new StatementModificationHelper(
			$snakFactory,
			$entityIdParser,
			$statementGuidValidator,
			$apiHelperFactory->getErrorReporter( $mainModule )
		);

		return new self(
			$mainModule,
			$moduleName,
			$deserializerFactory,
			$apiHelperFactory->getErrorReporter( $mainModule ),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$modificationHelper,
			$statementGuidParser,
			$logger,
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getResultBuilder( $module );
			},
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getEntitySavingHelper( $module );
			},
			$repoSettings->getSetting( 'federatedPropertiesEnabled' ),
			$repoSettings->getSetting( 'sandboxEntityIds' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
		$params = $this->extractRequestParams();
		$this->validateParameters( $params );

		$entityId = $this->guidParser->parse( $params['statement'] )->getEntityId();
		$this->validateAlteringEntityById( $entityId );

		$entity = $this->entitySavingHelper->loadEntity( $params, $entityId );

		$summary = $this->modificationHelper->createSummary( $params, $this );

		$claim = $this->modificationHelper->getStatementFromEntity( $params['statement'], $entity );

		if ( isset( $params['reference'] ) ) {
			$this->validateReferenceHash( $claim, $params['reference'] );
		}

		if ( isset( $params['snaks-order' ] ) ) {
			$snaksOrder = $this->getArrayFromParam( $params['snaks-order'], 'snaks-order' );
		} else {
			$snaksOrder = [];
		}

		$deserializer = $this->deserializerFactory->newSnakListDeserializer();
		/** @var SnakList $snakList */
		try {
			$snakList = $deserializer->deserialize( $this->getArrayFromParam( $params['snaks'], 'snaks' ) );
		} catch ( DeserializationException $e ) {
			$this->errorReporter->dieError(
				'Failed to get reference from reference Serialization ' . $e->getMessage(),
				'snak-instantiation-failure'
			);
		}
		$snakList->orderByProperty( $snaksOrder );

		$newReference = new Reference( $snakList );
		$changeOp = $this->getChangeOp( $newReference );

		$newReference = $this->applyChangeOpAndReturnChangedReference(
			$changeOp, $entity, $summary, $claim, $newReference );

		$status = $this->entitySavingHelper->attemptSaveEntity( $entity, $summary, $params, $this->getContext() );
		$this->resultBuilder->addRevisionIdFromStatusToResult( $status, 'pageinfo' );
		$this->resultBuilder->markSuccess();
		$this->resultBuilder->addReference( $newReference );
	}

	private function validateParameters( array $params ): void {
		if ( !( $this->modificationHelper->validateStatementGuid( $params['statement'] ) ) ) {
			$this->errorReporter->dieError( 'Invalid claim guid', 'invalid-guid' );
		}
	}

	private function validateReferenceHash( Statement $statement, string $referenceHash ): void {
		if ( !$statement->getReferences()->hasReferenceHash( $referenceHash ) ) {
			$this->errorReporter->dieError(
				'Statement does not have a reference with the given hash',
				'no-such-reference'
			);
		}
	}

	private function getArrayFromParam( string $arrayParam, string $parameter ): array {
		$rawArray = json_decode( $arrayParam, true );

		if ( !is_array( $rawArray ) || !count( $rawArray ) ) {
			$this->errorReporter->dieError(
				'No array or invalid JSON given for parameter: ' . $parameter,
				'invalid-json'
			);
		}

		return $rawArray;
	}

	private function getChangeOp( Reference $reference ): ChangeOp {
		$params = $this->extractRequestParams();

		$guid = $params['statement'];
		$hash = $params['reference'] ?? '';
		$index = $params['index'] ?? null;

		return $this->statementChangeOpFactory->newSetReferenceOp( $guid, $reference, $hash, $index );
	}

	/**
	 * Apply $changeop to $entity (updating $summary) and return the Reference that was changed.
	 * (Due to data value normalization in the ChangeOp factory,
	 * this may not be the exact same reference as $newReference.)
	 */
	private function applyChangeOpAndReturnChangedReference(
		ChangeOp $changeOp,
		EntityDocument $entity,
		Summary $summary,
		Statement $statement,
		Reference $newReference
	): Reference {
		$oldReferences = clone $statement->getReferences();

		$this->modificationHelper->applyChangeOp( $changeOp, $entity, $summary );

		$changedReferences = [];
		foreach ( $statement->getReferences()->getIterator() as $reference ) {
			if ( !$oldReferences->hasReference( $reference ) ) {
				$changedReferences[] = $reference;
			}
		}

		switch ( count( $changedReferences ) ) {
			case 0:
				// no reference changed hash, return original $newReference
				// (could be a null edit, or its index or snaks-order could have changed)
				return $newReference;
			case 1:
				return $changedReferences[0];
			default:
				// this should never happen, but let’s warn instead of crashing
				$this->logger->warning( __METHOD__ . ': changed {count} references, expected 0-1', [
					'count' => count( $changedReferences ),
					'oldReferences' => $oldReferences->serialize(),
					'newReferences' => $statement->getReferences()->serialize(),
					'changedReferences' => ( new ReferenceList( $changedReferences ) )->serialize(),
					'entityId' => $entity->getId()->getSerialization(),
				] );
				return $newReference; // it’s the best we have
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken(): string {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return array_merge(
			[
				'statement' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'snaks' => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => true,
				],
				'snaks-order' => [
					ParamValidator::PARAM_TYPE => 'string',
				],
				'reference' => [
					ParamValidator::PARAM_TYPE => 'string',
				],
				'index' => [
					ParamValidator::PARAM_TYPE => 'integer',
				],
				'summary' => [
					ParamValidator::PARAM_TYPE => 'string',
				],
				'tags' => [
					ParamValidator::PARAM_TYPE => 'tags',
					ParamValidator::PARAM_ISMULTI => true,
				],
				'token' => null,
				'baserevid' => [
					ParamValidator::PARAM_TYPE => 'integer',
				],
				'bot' => false,
			],
			parent::getAllowedParams()
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$guid = $this->sandboxEntityIds[ 'mainItem' ] . '$D4FDE516-F20C-4154-ADCE-7C5B609DFDFF';
		$hash = '1eb8793c002b1d9820c833d234a1b54c8e94187e';

		return [
			'action=wbsetreference&statement=' . $guid . '&snaks='
				. '{"P212":[{"snaktype":"value","property":"P212","datavalue":{"type":"string",'
				. '"value":"foo"}}]}&baserevid=7201010&token=foobar'
				=> [ 'apihelp-wbsetreference-example-1', $guid ],
			'action=wbsetreference&statement=' . $guid . ''
				. '&reference=' . $hash . '&snaks='
				. '{"P212":[{"snaktype":"value","property":"P212","datavalue":{"type":"string",'
				. '"value":"bar"}}]}&baserevid=7201010&token=foobar'
				=> [ 'apihelp-wbsetreference-example-2', $guid, $hash ],
			'action=wbsetreference&statement=' . $guid . '&snaks='
				. '{"P212":[{"snaktype":"novalue","property":"P212"}]}'
				. '&index=0&baserevid=7201010&token=foobar'
				=> [ 'apihelp-wbsetreference-example-3', $guid ],
		];
	}

}
