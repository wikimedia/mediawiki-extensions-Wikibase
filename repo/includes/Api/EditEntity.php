<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use Deserializers\Deserializer;
use Title;
use ApiUsageException;
use Wikibase\DataModel\Entity\Clearable;
use Wikibase\DataModel\Entity\ClearableEntity;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\Repo\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\EntityFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Summary;

/**
 * Derived class for API modules modifying a single entity identified by id xor a combination of
 * site and page title.
 *
 * @license GPL-2.0-or-later
 */
class EditEntity extends ModifyEntity {

	const PARAM_DATA = 'data';

	const PARAM_CLEAR = 'clear';

	/**
	 * @var ContentLanguages
	 */
	private $termsLanguages;

	/**
	 * @var FingerprintChangeOpFactory
	 */
	private $termChangeOpFactory;

	/**
	 * @var StatementChangeOpFactory
	 */
	private $statementChangeOpFactory;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	private $siteLinkChangeOpFactory;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var string[]
	 */
	private $propertyDataTypes;

	/**
	 * @var EntityChangeOpProvider
	 */
	private $entityChangeOpProvider;

	/**
	 * @see ModifyEntity::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param ContentLanguages $termsLanguages
	 * @param EntityRevisionLookup $revisionLookup
	 * @param EntityIdParser $idParser
	 * @param EntityFactory $entityFactory
	 * @param Deserializer $statementDeserializer
	 * @param string[] $propertyDataTypes
	 * @param FingerprintChangeOpFactory $termChangeOpFactory
	 * @param StatementChangeOpFactory $statementChangeOpFactory
	 * @param SiteLinkChangeOpFactory $siteLinkChangeOpFactory
	 * @param EntityChangeOpProvider $entityChangeOpProvider
	 *
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		ContentLanguages $termsLanguages,
		EntityRevisionLookup $revisionLookup,
		EntityIdParser $idParser,
		EntityFactory $entityFactory,
		Deserializer $statementDeserializer,
		array $propertyDataTypes,
		FingerprintChangeOpFactory $termChangeOpFactory,
		StatementChangeOpFactory $statementChangeOpFactory,
		SiteLinkChangeOpFactory $siteLinkChangeOpFactory,
		EntityChangeOpProvider $entityChangeOpProvider
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->termsLanguages = $termsLanguages;
		$this->revisionLookup = $revisionLookup;
		$this->idParser = $idParser;
		$this->entityFactory = $entityFactory;
		$this->statementDeserializer = $statementDeserializer;
		$this->propertyDataTypes = $propertyDataTypes;

		$this->termChangeOpFactory = $termChangeOpFactory;
		$this->statementChangeOpFactory = $statementChangeOpFactory;
		$this->siteLinkChangeOpFactory = $siteLinkChangeOpFactory;
		$this->entityChangeOpProvider = $entityChangeOpProvider;
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
	 * @see ApiBase::isWriteMode()
	 *
	 * @return bool Always true.
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	private function entityExists( EntityId $entityId ) {
		$title = $entityId === null ? null : $this->getTitleLookup()->getTitleForId( $entityId );
		return ( $title !== null && $title->exists() );
	}

	protected function prepareParameters( array $params ) {
		$this->validateDataParameter( $params );
		$params[self::PARAM_DATA] = json_decode( $params[self::PARAM_DATA], true );
		return parent::prepareParameters( $params );
	}

	protected function validateEntitySpecificParameters(
		array $preparedParameters,
		EntityDocument $entity,
		$baseRevId
	) {
		$data = $preparedParameters[self::PARAM_DATA];
		$this->validateDataProperties( $data, $entity, $baseRevId );

		$exists = $this->entityExists( $entity->getId() );

		if ( $preparedParameters[self::PARAM_CLEAR] ) {
			if ( $preparedParameters['baserevid'] && $exists ) {
				$latestRevision = $this->revisionLookup->getLatestRevisionId(
					$entity->getId(),
					EntityRevisionLookup::LATEST_FROM_MASTER
				);

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

	/**
	 * @see ModifyEntity::modifyEntity
	 *
	 * @param EntityDocument|Clearable &$entity
	 * @param ChangeOp $changeOp
	 * @param array $preparedParameters
	 *
	 * @return Summary
	 */
	protected function modifyEntity( EntityDocument &$entity, ChangeOp $changeOp, array $preparedParameters ) {
		$data = $preparedParameters[self::PARAM_DATA];

		$exists = $this->entityExists( $entity->getId() );

		if ( $preparedParameters[self::PARAM_CLEAR] ) {
			$this->dieIfNotClearable( $entity );
			$entity->clear();

			$this->getStats()->increment( 'wikibase.api.EditEntity.modifyEntity.clear' );
		} else {
			$this->getStats()->increment( 'wikibase.api.EditEntity.modifyEntity.no-clear' );
		}

		if ( !$exists ) {
			// if we create a new property, make sure we set the datatype
			if ( $entity instanceof Property ) {
				$entity->setDataTypeId( $data['datatype'] );
			}

			$this->getStats()->increment( 'wikibase.api.EditEntity.modifyEntity.create' );
		}

		$this->applyChangeOp( $changeOp, $entity );

		$this->buildResult( $entity );
		return $this->getSummary( $preparedParameters );
	}

	/**
	 * @param array $preparedParameters
	 *
	 * @return Summary
	 */
	private function getSummary( array $preparedParameters ) {
		//TODO: Construct a nice and meaningful summary from the changes that get applied!
		//      Perhaps that could be based on the resulting diff?
		$summary = $this->createSummary( $preparedParameters );
		if ( isset( $preparedParameters['id'] ) xor ( isset( $preparedParameters['site'] ) && isset( $preparedParameters['title'] ) ) ) {
			$summary->setAction( $preparedParameters[self::PARAM_CLEAR] === false ? 'update' : 'override' );
		} else {
			$summary->setAction( 'create' );
		}
		return $summary;
	}

	/**
	 * @param array $preparedParameters
	 * @param EntityDocument $entity
	 *
	 * @throws ApiUsageException
	 * @return ChangeOp
	 */
	protected function getChangeOp( array $preparedParameters, EntityDocument $entity ) {
		$data = $preparedParameters[self::PARAM_DATA];

		try {
			return $this->entityChangeOpProvider->newEntityChangeOp( $entity->getType(), $data );
		} catch ( ChangeOpDeserializationException $exception ) {
			$this->errorReporter->dieException( $exception, $exception->getErrorCode() );
		}
	}

	private function buildResult( EntityDocument $entity ) {
		$builder = $this->getResultBuilder();

		if ( $entity instanceof LabelsProvider ) {
			$builder->addLabels( $entity->getLabels(), 'entity' );
		}

		if ( $entity instanceof DescriptionsProvider ) {
			$builder->addDescriptions( $entity->getDescriptions(), 'entity' );
		}

		if ( $entity instanceof AliasesProvider ) {
			$builder->addAliasGroupList( $entity->getAliasGroups(), 'entity' );
		}

		if ( $entity instanceof Item ) {
			$builder->addSiteLinkList( $entity->getSiteLinkList(), 'entity' );
		}

		if ( $entity instanceof StatementListProvider ) {
			$builder->addStatements( $entity->getStatements(), 'entity' );
		}
	}

	/**
	 * @param array $params
	 */
	private function validateDataParameter( array $params ) {
		if ( !isset( $params[self::PARAM_DATA] ) ) {
			$this->errorReporter->dieError( 'No data to operate upon', 'no-data' );
		}
	}

	/**
	 * @param mixed $data
	 * @param EntityDocument $entity
	 * @param int $revisionId
	 */
	private function validateDataProperties( $data, EntityDocument $entity, $revisionId = 0 ) {
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
	private function checkValidJson( $data ) {
		if ( is_null( $data ) ) {
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

	/**
	 * @param array $data
	 * @param Title|null $title
	 */
	private function checkPageIdProp( array $data, Title $title = null ) {
		if ( isset( $data['pageid'] )
			&& ( $title === null || $title->getArticleID() !== $data['pageid'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call, "pageid", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	/**
	 * @param array $data
	 * @param Title|null $title
	 */
	private function checkNamespaceProp( array $data, Title $title = null ) {
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

	/**
	 * @param array $data
	 * @param Title|null $title
	 */
	private function checkTitleProp( array $data, Title $title = null ) {
		if ( isset( $data['title'] )
			&& ( $title === null || $title->getPrefixedText() !== $data['title'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call: "title", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	/**
	 * @param array $data
	 * @param int|null $revisionId
	 */
	private function checkRevisionProp( array $data, $revisionId ) {
		if ( isset( $data['lastrevid'] )
			&& ( !is_int( $revisionId ) || $revisionId !== $data['lastrevid'] )
		) {
			$this->errorReporter->dieError(
				'Illegal field used in call: "lastrevid", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	/**
	 * @param array $data
	 * @param EntityId|null $entityId
	 */
	private function checkEntityId( array $data, EntityId $entityId = null ) {
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

	/**
	 * @param array $data
	 * @param EntityDocument $entity
	 */
	private function checkEntityType( array $data, EntityDocument $entity ) {
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
	 * @see ModifyEntity::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			[
				self::PARAM_DATA => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => true,
				],
				self::PARAM_CLEAR => [
					self::PARAM_TYPE => 'boolean',
					self::PARAM_DFLT => false
				],
			]
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
			// Creating new entites
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
			'action=wbeditentity&clear=true&id=Q42&data={}'
				=> 'apihelp-wbeditentity-example-4',
			'action=wbeditentity&clear=true&id=Q42&data={'
				. '"labels":{"en":{"language":"en","value":"en-value"}}}'
				=> 'apihelp-wbeditentity-example-5',
			// Adding term
			'action=wbeditentity&id=Q42&data='
				. '{"labels":[{"language":"no","value":"Bar","add":""}]}'
				=> 'apihelp-wbeditentity-example-11',
			// Removing term
			'action=wbeditentity&id=Q42&data='
				. '{"labels":[{"language":"en","value":"Foo","remove":""}]}'
				=> 'apihelp-wbeditentity-example-12',
			// Setting stuff
			'action=wbeditentity&id=Q42&data={'
				. '"sitelinks":{"nowiki":{"site":"nowiki","title":"København"}}}'
				=> 'apihelp-wbeditentity-example-6',
			'action=wbeditentity&id=Q42&data={'
				. '"descriptions":{"nb":{"language":"nb","value":"nb-Description-Here"}}}'
				=> 'apihelp-wbeditentity-example-7',
			'action=wbeditentity&id=Q42&data={"claims":[{"mainsnak":{"snaktype":"value",'
				. '"property":"P56","datavalue":{"value":"ExampleString","type":"string"}},'
				. '"type":"statement","rank":"normal"}]}'
				=> 'apihelp-wbeditentity-example-8',
			'action=wbeditentity&id=Q42&data={"claims":['
				. '{"id":"Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F","remove":""},'
				. '{"id":"Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","remove":""}]}'
				=> 'apihelp-wbeditentity-example-9',
			'action=wbeditentity&id=Q42&data={"claims":[{'
				. '"id":"Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","mainsnak":{"snaktype":"value",'
				. '"property":"P56","datavalue":{"value":"ChangedString","type":"string"}},'
				. '"type":"statement","rank":"normal"}]}'
				=> 'apihelp-wbeditentity-example-10',
		];
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertArray( $value, $message ) {
		$this->assertType( 'array', $value, $message );
	}

	/**
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertString( $value, $message ) {
		$this->assertType( 'string', $value, $message );
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @param string $message
	 */
	private function assertType( $type, $value, $message ) {
		if ( gettype( $value ) !== $type ) {
			$this->errorReporter->dieError( $message, 'not-recognized-' . $type );
		}
	}

	private function dieIfNotClearable( EntityDocument $entity ) {
		if ( !( $entity instanceof ClearableEntity ) ) {
			$this->errorReporter->dieError(
				'Cannot clear an entity of type ' . $entity->getType(),
				'param-illegal'
			);
		}
	}

}
