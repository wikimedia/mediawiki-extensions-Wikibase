<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiMain;
use ApiUsageException;
use IBufferingStatsdDataFactory;
use Serializers\Exceptions\SerializationException;
use Title;
use Wikibase\DataModel\Entity\ClearableEntity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCollector;
use Wikibase\Repo\ChangeOp\ChangedLanguagesCounter;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpException;
use Wikibase\Repo\ChangeOp\ChangeOpResult;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\ChangeOp\NonLanguageBoundChangesCounter;
use Wikibase\Repo\Store\Store;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Derived class for API modules modifying a single entity identified by id xor a combination of
 * site and page title.
 *
 * @license GPL-2.0-or-later
 */
class EditEntity extends ModifyEntity {

	public const PARAM_DATA = 'data';

	public const PARAM_CLEAR = 'clear';

	/**
	 * @var IBufferingStatsdDataFactory
	 */
	private $statsdDataFactory;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string[]
	 */
	private $propertyDataTypes;

	/**
	 * @var EntityChangeOpProvider
	 */
	private $entityChangeOpProvider;

	/**
	 * @var EditSummaryHelper
	 */
	private $editSummaryHelper;

	/**
	 * @var string[]
	 */
	private $sandboxEntityIds;

	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		IBufferingStatsdDataFactory $statsdDataFactory,
		EntityRevisionLookup $revisionLookup,
		EntityIdParser $idParser,
		array $propertyDataTypes,
		EntityChangeOpProvider $entityChangeOpProvider,
		EditSummaryHelper $editSummaryHelper,
		bool $federatedPropertiesEnabled,
		array $sandboxEntityIds
	) {
		parent::__construct( $mainModule, $moduleName, $federatedPropertiesEnabled );

		$this->statsdDataFactory = $statsdDataFactory;
		$this->revisionLookup = $revisionLookup;
		$this->idParser = $idParser;
		$this->propertyDataTypes = $propertyDataTypes;

		$this->entityChangeOpProvider = $entityChangeOpProvider;
		$this->editSummaryHelper = $editSummaryHelper;
		$this->sandboxEntityIds = $sandboxEntityIds;
	}

	public static function factory(
		ApiMain $mainModule,
		string $moduleName,
		IBufferingStatsdDataFactory $statsdDataFactory,
		DataTypeDefinitions $dataTypeDefinitions,
		EntityChangeOpProvider $entityChangeOpProvider,
		EntityIdParser $entityIdParser,
		SettingsArray $settings,
		Store $store
	): self {
		return new self(
			$mainModule,
			$moduleName,
			$statsdDataFactory,
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$entityIdParser,
			$dataTypeDefinitions->getTypeIds(),
			$entityChangeOpProvider,
			new EditSummaryHelper(
				new ChangedLanguagesCollector(),
				new ChangedLanguagesCounter(),
				new NonLanguageBoundChangesCounter()
			),
			$settings->getSetting( 'federatedPropertiesEnabled' ),
			$settings->getSetting( 'sandboxEntityIds' )
		);
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
	 * @see ApiBase::isWriteMode()
	 *
	 * @return bool Always true.
	 */
	public function isWriteMode(): bool {
		return true;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	private function entityExists( EntityId $entityId ): bool {
		$title = $this->getTitleLookup()->getTitleForId( $entityId );
		return ( $title !== null && $title->exists() );
	}

	protected function prepareParameters( array $params ): array {
		$this->validateDataParameter( $params );
		$params[self::PARAM_DATA] = json_decode( $params[self::PARAM_DATA], true );
		return parent::prepareParameters( $params );
	}

	protected function validateEntitySpecificParameters(
		array $preparedParameters,
		EntityDocument $entity,
		int $baseRevId
	): void {
		$data = $preparedParameters[self::PARAM_DATA];
		$this->validateDataProperties( $data, $entity, $baseRevId );

		$exists = $this->entityExists( $entity->getId() );

		if ( $preparedParameters[self::PARAM_CLEAR] ) {
			if ( $preparedParameters['baserevid'] && $exists ) {
				$latestRevisionResult = $this->revisionLookup->getLatestRevisionId(
					$entity->getId(),
					 LookupConstants::LATEST_FROM_MASTER
				);

				$returnFalse = function () {
					return false;
				};
				$latestRevision = $latestRevisionResult->onConcreteRevision( function ( $revId ) {
					return $revId;
				} )
					->onRedirect( $returnFalse )
					->onNonexistentEntity( $returnFalse )
					->map();

				if ( !$baseRevId === $latestRevision ) {
					$this->errorReporter->dieError(
						'Tried to clear entity using baserevid of entity not equal to current revision',
						'editconflict'
					);
				}
			}
		}

		// if we create a new property, make sure we set the datatype
		if ( !$exists && $entity instanceof Property ) {
			if ( !isset( $data['datatype'] )
				|| !in_array( $data['datatype'], $this->propertyDataTypes )
			) {
				$this->errorReporter->dieWithError(
					'wikibase-api-not-recognized-datatype',
					'param-illegal'
				);
			}
		}
	}

	protected function modifyEntity( EntityDocument $entity, ChangeOp $changeOp, array $preparedParameters ): Summary {
		$data = $preparedParameters[self::PARAM_DATA];

		$exists = $this->entityExists( $entity->getId() );

		if ( $preparedParameters[self::PARAM_CLEAR] ) {
			$this->dieIfNotClearable( $entity );
			$this->statsdDataFactory->increment( 'wikibase.api.EditEntity.modifyEntity.clear' );
		} else {
			$this->statsdDataFactory->increment( 'wikibase.api.EditEntity.modifyEntity.no-clear' );
		}

		if ( !$exists ) {
			// if we create a new property, make sure we set the datatype
			if ( $entity instanceof Property ) {
				$entity->setDataTypeId( $data['datatype'] );
			}

			$this->statsdDataFactory->increment( 'wikibase.api.EditEntity.modifyEntity.create' );
		}

		if ( $preparedParameters[self::PARAM_CLEAR] ) {
			$oldEntity = clone $entity;
			$entity->clear();

			// Validate it only by applying the changeOp on the current entity
			// instead of an empty one due avoid issues like T243158.
			// We are going to save the cleared entity instead,
			$changeOpResult = $this->applyChangeOp( $changeOp, $oldEntity );

			try {
				$changeOp->apply( $entity );
			} catch ( ChangeOpException $ex ) {
				$this->errorReporter->dieException( $ex, 'modification-failed' );
			}

		} else {
			$changeOpResult = $this->applyChangeOp( $changeOp, $entity );
		}

		try {
			$this->getResult()->addValue( null, 'entity',
				$this->getResultBuilder()->getModifiedEntityArray( $entity, 'all', null, [], [] ) );
		} catch ( SerializationException $e ) {
			$this->addWarning(
			'wikibase-editentity-warning-serializeresult',
				null,
				[ 'exceptionMessage' => $e->getMessage() ]
			);
		}

		return $this->getSummary( $preparedParameters, $entity, $changeOpResult );
	}

	private function getSummary(
		array $preparedParameters,
		EntityDocument $entity,
		ChangeOpResult $changeOpResult
	): Summary {
		$summary = $this->createSummary( $preparedParameters );

		if ( $this->isUpdatingExistingEntity( $preparedParameters ) ) {
			if ( $preparedParameters[self::PARAM_CLEAR] !== false ) {
				$summary->setAction( 'override' );
			} else {
				$this->editSummaryHelper->prepareEditSummary( $summary, $changeOpResult );
			}
		} else {
			$summary->setAction( 'create-' . $entity->getType() );
		}

		return $summary;
	}

	private function isUpdatingExistingEntity( array $preparedParameters ): bool {
		$isTargetingEntity = isset( $preparedParameters['id'] );
		$isTargetingPage = isset( $preparedParameters['site'] ) && isset( $preparedParameters['title'] );

		return $isTargetingEntity xor $isTargetingPage;
	}

	/**
	 * @param array $preparedParameters
	 * @param EntityDocument $entity
	 *
	 * @throws ApiUsageException
	 * @return ChangeOp
	 */
	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ): ChangeOp {
		$data = $preparedParameters[self::PARAM_DATA];

		if ( isset( $preparedParameters['id'] ) || $entity->getId() ) {
			$data['id'] = $preparedParameters['id'] ?? $entity->getId()->getSerialization();
		}

		try {
			return $this->entityChangeOpProvider->newEntityChangeOp( $entity->getType(), $data );
		} catch ( ChangeOpDeserializationException $exception ) {
			$this->errorReporter->dieException( $exception, $exception->getErrorCode() );
		}
	}

	private function validateDataParameter( array $params ): void {
		if ( !isset( $params[self::PARAM_DATA] ) ) {
			$this->errorReporter->dieError( 'No data to operate upon', 'no-data' );
		}
	}

	/**
	 * @param mixed $data
	 * @param EntityDocument $entity
	 * @param int $revisionId
	 */
	private function validateDataProperties( $data, EntityDocument $entity, int $revisionId ): void {
		$entityId = $entity->getId();
		$title = $entityId === null ? null : $this->getTitleLookup()->getTitleForId( $entityId );

		$this->checkValidJson( $data );
		$this->checkEntityId( $data, $entityId );
		$this->checkEntityType( $data, $entity );
		$this->checkPageIdProp( $data, $title );
		$this->checkNamespaceProp( $data, $title );
		$this->checkTitleProp( $data, $title );
		$this->checkRevisionProp( $data, $revisionId );
	}

	/**
	 * @param mixed $data
	 */
	private function checkValidJson( $data ): void {
		if ( $data === null ) {
			$this->errorReporter->dieError( 'Invalid json: The supplied JSON structure could not be parsed or '
				. 'recreated as a valid structure', 'invalid-json' );
		}

		// NOTE: json_decode will decode any JS literal or structure, not just objects!
		$this->assertArray( $data, 'Top level structure must be a JSON object' );

		foreach ( $data as $prop => $args ) {
			// Catch json_decode returning an indexed array (list).
			$this->assertString( $prop, 'Top level structure must be a JSON object (no keys found)' );

			if ( $prop === 'remove' ) {
				$this->errorReporter->dieWithError(
					'wikibase-api-illegal-entity-remove',
					'not-recognized'
				);
			}
		}
	}

	private function checkPageIdProp( array $data, ?Title $title ): void {
		if ( isset( $data['pageid'] )
			&& ( $title === null || $title->getArticleID() !== $data['pageid'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call, "pageid", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	private function checkNamespaceProp( array $data, ?Title $title ): void {
		// not completely convinced that we can use title to get the namespace in this case
		if ( isset( $data['ns'] )
			&& ( $title === null || $title->getNamespace() !== $data['ns'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call: "namespace", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	private function checkTitleProp( array $data, ?Title $title ): void {
		if ( isset( $data['title'] )
			&& ( $title === null || $title->getPrefixedText() !== $data['title'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call: "title", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	private function checkRevisionProp( array $data, int $revisionId ): void {
		if ( isset( $data['lastrevid'] )
			&& ( $revisionId !== $data['lastrevid'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call: "lastrevid", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	private function checkEntityId( array $data, ?EntityId $entityId ): void {
		if ( isset( $data['id'] ) ) {
			if ( !$entityId ) {
				$this->errorReporter->dieError(
					'Illegal field used in call: "id", must not be given when creating a new entity',
					'param-illegal'
				);
			}

			$dataId = $this->idParser->parse( $data['id'] );
			if ( !$entityId->equals( $dataId ) ) {
				$this->errorReporter->dieError(
					'Invalid field used in call: "id", must match id parameter',
					'param-invalid'
				);
			}
		}
	}

	private function checkEntityType( array $data, EntityDocument $entity ): void {
		if ( isset( $data['type'] )
			&& $entity->getType() !== $data['type']
		) {
			$this->errorReporter->dieError(
				'Invalid field used in call: "type", must match type associated with id',
				'param-invalid'
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return array_merge(
			parent::getAllowedParams(),
			[
				self::PARAM_DATA => [
					ParamValidator::PARAM_TYPE => 'text',
					ParamValidator::PARAM_REQUIRED => true,
				],
				self::PARAM_CLEAR => [
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_DEFAULT => false,
				],
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
		$id = $this->sandboxEntityIds[ 'mainItem' ];

		return [
			// Creating new entities
			'action=wbeditentity&new=item&data={}'
				=> 'apihelp-wbeditentity-example-1',
			'action=wbeditentity&new=item&data={"labels":{'
				. '"de":{"language":"de","value":"de-value"},'
				. '"en":{"language":"en","value":"en-value"}}}'
				=> 'apihelp-wbeditentity-example-2',
			'action=wbeditentity&new=property&data={'
				. '"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},'
				. '"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},'
				. '"datatype":"string"}'
				=> 'apihelp-wbeditentity-example-3',
			// Clearing entities
			'action=wbeditentity&clear=true&id=' . $id . '&data={}'
				=> [ 'apihelp-wbeditentity-example-4', $id ],
			'action=wbeditentity&clear=true&id=' . $id . '&data={'
				. '"labels":{"en":{"language":"en","value":"en-value"}}}'
				=> [ 'apihelp-wbeditentity-example-5', $id ],
			// Adding term
			'action=wbeditentity&id=' . $id . '&data='
				. '{"labels":[{"language":"no","value":"Bar","add":""}]}'
				=> 'apihelp-wbeditentity-example-11',
			// Removing term
			'action=wbeditentity&id=' . $id . '&data='
				. '{"labels":[{"language":"en","value":"Foo","remove":""}]}'
				=> 'apihelp-wbeditentity-example-12',
			// Setting stuff
			'action=wbeditentity&id=' . $id . '&data={'
				. '"sitelinks":{"nowiki":{"site":"nowiki","title":"København"}}}'
				=> 'apihelp-wbeditentity-example-6',
			'action=wbeditentity&id=' . $id . '&data={'
				. '"descriptions":{"nb":{"language":"nb","value":"nb-Description-Here"}}}'
				=> 'apihelp-wbeditentity-example-7',
			'action=wbeditentity&id=' . $id . '&data={"claims":[{"mainsnak":{"snaktype":"value",'
				. '"property":"P56","datavalue":{"value":"ExampleString","type":"string"}},'
				. '"type":"statement","rank":"normal"}]}'
				=> 'apihelp-wbeditentity-example-8',
			'action=wbeditentity&id=' . $id . '&data={"claims":['
				. '{"id":"' . $id . '$D8404CDA-25E4-4334-AF13-A3290BCD9C0F","remove":""},'
				. '{"id":"' . $id . '$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","remove":""}]}'
				=> 'apihelp-wbeditentity-example-9',
			'action=wbeditentity&id=' . $id . '&data={"claims":[{'
				. '"id":"' . $id . '$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","mainsnak":{"snaktype":"value",'
				. '"property":"P56","datavalue":{"value":"ChangedString","type":"string"}},'
				. '"type":"statement","rank":"normal"}]}'
				=> 'apihelp-wbeditentity-example-10',
		];
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertArray( $value, string $message ): void {
		$this->assertType( 'array', $value, $message );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertString( $value, string $message ): void {
		$this->assertType( 'string', $value, $message );
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertType( string $type, $value, string $message ): void {
		if ( gettype( $value ) !== $type ) {
			$this->errorReporter->dieError( $message, 'not-recognized-' . $type );
		}
	}

	private function dieIfNotClearable( EntityDocument $entity ): void {
		if ( !( $entity instanceof ClearableEntity ) ) {
			$this->errorReporter->dieError(
				'Cannot clear an entity of type ' . $entity->getType(),
				'param-illegal'
			);
		}
	}

}
