<?php

namespace Wikibase\Repo\Api;

use ApiMain;
use DataValues\IllegalValueException;
use Deserializers\Deserializer;
use InvalidArgumentException;
use LogicException;
use MWException;
use SiteList;
use Title;
use UsageException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\EntityFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Derived class for API modules modifying a single entity identified by id xor a combination of
 * site and page title.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Addshore
 * @author Michał Łazowik
 */
class EditEntity extends ModifyEntity {

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
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var Deserializer
	 */
	private $statementDeserializer;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @var string[]
	 */
	private $enabledEntityTypes;

	/**
	 * @see ModifyEntity::__construct
	 *
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @throws MWException
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $this->getContext() );
		$this->termsLanguages = $wikibaseRepo->getTermsLanguages();
		$this->errorReporter = $apiHelperFactory->getErrorReporter( $this );
		$this->revisionLookup = $wikibaseRepo->getEntityRevisionLookup( 'uncached' );
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->statementDeserializer = $wikibaseRepo->getExternalFormatStatementDeserializer();
		$this->entityFactory = $wikibaseRepo->getEntityFactory();
		$this->enabledEntityTypes = $wikibaseRepo->getEnabledEntityTypes();

		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
		$this->siteLinkChangeOpFactory = $changeOpFactoryProvider->getSiteLinkChangeOpFactory();
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
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException
	 * @return string[] A list of permissions
	 */
	protected function getRequiredPermissions( EntityDocument $entity ) {
		$permissions = $this->isWriteMode() ? array( 'read', 'edit' ) : array( 'read' );

		if ( !$this->entityExists( $entity->getId() ) ) {
			$permissions[] = 'createpage';

			switch ( $entity->getType() ) {
				case 'property':
					$permissions[] = $entity->getType() . '-create'; //property-create
					break;
			}
		}

		return $permissions;
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

	/**
	 * @see ModifyEntity::createEntity
	 *
	 * @param string $entityType
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return EntityDocument
	 */
	protected function createEntity( $entityType ) {
		$this->flags |= EDIT_NEW;

		try {
			return $this->entityFactory->newEmpty( $entityType );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieError( "No such entity type: '$entityType'", 'no-such-entity-type' );
		}

		throw new LogicException( 'ApiErrorReporter::dieError did not throw an exception' );
	}

	/**
	 * @see ModifyEntity::validateParameters
	 */
	protected function validateParameters( array $params ) {
		$hasId = isset( $params['id'] );
		$hasNew = isset( $params['new'] );
		$hasSiteLink = isset( $params['site'] ) && isset( $params['title'] );
		$hasSiteLinkPart = isset( $params['site'] ) || isset( $params['title'] );

		if ( !( $hasId xor $hasSiteLink xor $hasNew ) ) {
			$this->errorReporter->dieError(
				'Either provide the item "id" or pairs of "site" and "title" or a "new" type for'
					. ' an entity',
				'param-missing'
			);
		}
		if ( $hasId && $hasSiteLink ) {
			$this->errorReporter->dieError(
				'Parameter "id" and "site", "title" combination are not allowed to be both set in'
					. ' the same request',
				'param-illegal'
			);
		}
		if ( ( $hasId || $hasSiteLinkPart ) && $hasNew ) {
			$this->errorReporter->dieError(
				'Parameters "id", "site", "title" and "new" are not allowed to be both set in the'
					. ' same request',
				'param-illegal'
			);
		}
	}

	/**
	 * @see ModifyEntity::modifyEntity
	 */
	protected function modifyEntity( EntityDocument &$entity, array $params, $baseRevId ) {
		$this->validateDataParameter( $params );
		$data = json_decode( $params['data'], true );
		$this->validateDataProperties( $data, $entity, $baseRevId );

		$exists = $this->entityExists( $entity->getId() );

		if ( $params['clear'] ) {
			if ( $params['baserevid'] && $exists ) {
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

			$entity = $this->clearEntity( $entity );
		}

		// if we create a new property, make sure we set the datatype
		if ( !$exists && $entity instanceof Property ) {
			if ( !isset( $data['datatype'] ) ) {
				$this->errorReporter->dieError( 'No datatype given', 'param-illegal' );
			} else {
				$entity->setDataTypeId( $data['datatype'] );
			}
		}

		$changeOps = $this->getChangeOps( $data, $entity );
		$summary = $this->getSummary( $params, $entity );

		$changeOpsArray = $changeOps->getChangeOps();
		if ( count( $changeOpsArray ) === 1 && $changeOpsArray[0]->getModuleName() !== null ) {
			$summary->setModuleName( $changeOpsArray[0]->getModuleName() );
			$this->applyChangeOp( $changeOpsArray[0], $entity, $summary );
		} else {
			$this->applyChangeOp( $changeOps, $entity );
		}

		$this->buildResult( $entity );
		return $summary;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return EntityDocument
	 */
	private function clearEntity( EntityDocument $entity ) {
		$newEntity = $this->entityFactory->newEmpty( $entity->getType() );
		$newEntity->setId( $entity->getId() );

		// FIXME how to avoid special case handling here?
		if ( $entity instanceof Property ) {
			/** @var Property $newEntity */
			$newEntity->setDataTypeId( $entity->getDataTypeId() );
		}

		return $newEntity;
	}

	/**
	 * @param array $params
	 *
	 * @return Summary
	 */
	private function getSummary( array $params ) {
		//TODO: Construct a nice and meaningful summary from the changes that get applied!
		//      Perhaps that could be based on the resulting diff?]
		$summary = $this->createSummary( $params );
		if ( isset( $params['id'] ) xor ( isset( $params['site'] ) && isset( $params['title'] ) ) ) {
			$summary->setAction( $params['clear'] === false ? 'update' : 'override' );
		} else {
			$summary->setAction( 'create' );
		}
		return $summary;
	}

	/**
	 * @param array $data
	 * @param EntityDocument $entity
	 *
	 * @throws UsageException
	 * @return ChangeOps
	 */
	private function getChangeOps( array $data, EntityDocument $entity ) {
		$changeOps = new ChangeOps();

		//FIXME: Use a ChangeOpBuilder so we can batch fingerprint ops etc,
		//       for more efficient validation!

		if ( array_key_exists( 'labels', $data ) ) {
			if ( !( $entity instanceof LabelsProvider ) ) {
				$this->errorReporter->dieError( 'The given entity cannot contain labels', 'not-supported' );
			}
			$this->assertArray( $data['labels'], 'List of labels must be an array' );
			$changeOps->add( $this->getLabelChangeOps( $data['labels'] ) );
		}

		if ( array_key_exists( 'descriptions', $data ) ) {
			if ( !( $entity instanceof DescriptionsProvider ) ) {
				$this->errorReporter->dieError( 'The given entity cannot contain descriptions', 'not-supported' );
			}
			$this->assertArray( $data['descriptions'], 'List of descriptions must be an array' );
			$changeOps->add( $this->getDescriptionChangeOps( $data['descriptions'] ) );
		}

		if ( array_key_exists( 'aliases', $data ) ) {
			if ( !( $entity instanceof AliasesProvider ) ) {
				$this->errorReporter->dieError( 'The given entity cannot contain aliases', 'not-supported' );
			}
			$this->assertArray( $data['aliases'], 'List of aliases must be an array' );
			$changeOps->add( $this->getAliasesChangeOps( $data['aliases'] ) );
		}

		if ( array_key_exists( 'sitelinks', $data ) ) {
			if ( !( $entity instanceof Item ) ) {
				$this->errorReporter->dieError( 'Non Items cannot have sitelinks', 'not-supported' );
			}
			$this->assertArray( $data['sitelinks'], 'List of sitelinks must be an array' );
			$changeOps->add( $this->getSiteLinksChangeOps( $data['sitelinks'], $entity ) );
		}

		if ( array_key_exists( 'claims', $data ) ) {
			if ( !( $entity instanceof StatementListProvider ) ) {
				$this->errorReporter->dieError( 'The given entity cannot contain statements', 'not-supported' );
			}
			$this->assertArray( $data['claims'], 'List of claims must be an array' );
			$changeOps->add( $this->getClaimsChangeOps( $data['claims'] ) );
		}

		return $changeOps;
	}

	/**
	 * @param array[] $labels
	 *
	 * @return ChangeOp[]
	 */
	private function getLabelChangeOps( array $labels ) {
		$labelChangeOps = array();

		foreach ( $labels as $langCode => $arg ) {
			$this->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newLabel = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newLabel === "" ) {
				$labelChangeOps[] = $this->termChangeOpFactory->newRemoveLabelOp( $language );
			} else {
				$labelChangeOps[] = $this->termChangeOpFactory->newSetLabelOp( $language, $newLabel );
			}
		}

		return $labelChangeOps;
	}

	/**
	 * @param array[] $descriptions
	 *
	 * @return ChangeOp[]
	 */
	private function getDescriptionChangeOps( array $descriptions ) {
		$descriptionChangeOps = array();

		foreach ( $descriptions as $langCode => $arg ) {
			$this->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newDescription = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newDescription === "" ) {
				$descriptionChangeOps[] = $this->termChangeOpFactory->newRemoveDescriptionOp( $language );
			} else {
				$descriptionChangeOps[] = $this->termChangeOpFactory->newSetDescriptionOp( $language, $newDescription );
			}
		}

		return $descriptionChangeOps;
	}

	/**
	 * @param array[] $aliases
	 *
	 * @return ChangeOp[]
	 */
	private function getAliasesChangeOps( array $aliases ) {
		$indexedAliases = $this->getIndexedAliases( $aliases );
		$aliasesChangeOps = $this->getIndexedAliasesChangeOps( $indexedAliases );

		return $aliasesChangeOps;
	}

	/**
	 * @param array[] $aliases
	 *
	 * @return array[]
	 */
	private function getIndexedAliases( array $aliases ) {
		$indexedAliases = array();

		foreach ( $aliases as $langCode => $arg ) {
			if ( !is_string( $langCode ) ) {
				$indexedAliases[] = ( array_values( $arg ) === $arg ) ? $arg : array( $arg );
			} else {
				$indexedAliases[$langCode] = ( array_values( $arg ) === $arg ) ? $arg : array( $arg );
			}
		}

		return $indexedAliases;
	}

	/**
	 * @param array[] $indexedAliases
	 *
	 * @return ChangeOp[]
	 */
	private function getIndexedAliasesChangeOps( array $indexedAliases ) {
		$aliasesChangeOps = array();
		foreach ( $indexedAliases as $langCode => $args ) {
			$aliasesToSet = array();
			$language = '';

			foreach ( $args as $arg ) {
				$this->validateMultilangArgs( $arg, $langCode );

				$alias = array( $this->stringNormalizer->trimToNFC( $arg['value'] ) );
				$language = $arg['language'];

				if ( array_key_exists( 'remove', $arg ) ) {
					$aliasesChangeOps[] = $this->termChangeOpFactory->newRemoveAliasesOp( $language, $alias );
				} elseif ( array_key_exists( 'add', $arg ) ) {
					$aliasesChangeOps[] = $this->termChangeOpFactory->newAddAliasesOp( $language, $alias );
				} else {
					$aliasesToSet[] = $alias[0];
				}
			}

			if ( $aliasesToSet !== array() ) {
				$aliasesChangeOps[] = $this->termChangeOpFactory->newSetAliasesOp( $language, $aliasesToSet );
			}
		}

		return $aliasesChangeOps;
	}

	/**
	 * @param array[] $siteLinks
	 * @param Item $item
	 *
	 * @return ChangeOp[]
	 */
	private function getSiteLinksChangeOps( array $siteLinks, Item $item ) {
		$siteLinksChangeOps = array();
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		foreach ( $siteLinks as $siteId => $arg ) {
			$this->checkSiteLinks( $arg, $siteId, $sites );
			$globalSiteId = $arg['site'];

			if ( !$sites->hasSite( $globalSiteId ) ) {
				$this->errorReporter->dieError( "There is no site for global site id '$globalSiteId'", 'no-such-site' );
			}

			$linkSite = $sites->getSite( $globalSiteId );
			$shouldRemove = array_key_exists( 'remove', $arg )
				|| ( !isset( $arg['title'] ) && !isset( $arg['badges'] ) )
				|| ( isset( $arg['title'] ) && $arg['title'] === '' );

			if ( $shouldRemove ) {
				$siteLinksChangeOps[] = $this->siteLinkChangeOpFactory->newRemoveSiteLinkOp( $globalSiteId );
			} else {
				$badges = ( isset( $arg['badges'] ) )
					? $this->parseSiteLinkBadges( $arg['badges'] )
					: null;

				if ( isset( $arg['title'] ) ) {
					$linkPage = $linkSite->normalizePageName( $this->stringNormalizer->trimWhitespace( $arg['title'] ) );

					if ( $linkPage === false ) {
						$this->errorReporter->dieMessage(
							'no-external-page',
							$globalSiteId,
							$arg['title']
						);
					}
				} else {
					$linkPage = null;

					if ( !$item->getSiteLinkList()->hasLinkWithSiteId( $globalSiteId ) ) {
						$this->errorReporter->dieMessage( 'no-such-sitelink', $globalSiteId );
					}
				}

				$siteLinksChangeOps[] = $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $globalSiteId, $linkPage, $badges );
			}
		}

		return $siteLinksChangeOps;
	}

	/**
	 * @param array[] $claims
	 *
	 * @return ChangeOp[]
	 */
	private function getClaimsChangeOps( array $claims ) {
		$changeOps = array();

		//check if the array is associative or in arrays by property
		if ( array_keys( $claims ) !== range( 0, count( $claims ) - 1 ) ) {
			foreach ( $claims as $subClaims ) {
				$changeOps = array_merge( $changeOps,
					$this->getRemoveStatementChangeOps( $subClaims ),
					$this->getModifyStatementChangeOps( $subClaims ) );
			}
		} else {
			$changeOps = array_merge( $changeOps,
				$this->getRemoveStatementChangeOps( $claims ),
				$this->getModifyStatementChangeOps( $claims ) );
		}

		return $changeOps;
	}

	/**
	 * @param array[] $statements array of serialized statements
	 *
	 * @return ChangeOp[]
	 */
	private function getModifyStatementChangeOps( array $statements ) {
		$opsToReturn = array();

		foreach ( $statements as $statementArray ) {
			if ( !array_key_exists( 'remove', $statementArray ) ) {
				try {
					$statement = $this->statementDeserializer->deserialize( $statementArray );

					if ( !( $statement instanceof Statement ) ) {
						throw new IllegalValueException( 'Statement serialization did not contained a Statement.' );
					}

					$opsToReturn[] = $this->statementChangeOpFactory->newSetStatementOp( $statement );
				} catch ( IllegalValueException $ex ) {
					$this->errorReporter->dieException( $ex, 'invalid-claim' );
				} catch ( MWException $ex ) {
					$this->errorReporter->dieException( $ex, 'invalid-claim' );
				}
			}
		}
		return $opsToReturn;
	}

	/**
	 * Get changeops that remove all claims that have the 'remove' key in the array
	 *
	 * @param array[] $claims array of serialized claims
	 *
	 * @return ChangeOp[]
	 */
	private function getRemoveStatementChangeOps( array $claims ) {
		$opsToReturn = array();
		foreach ( $claims as $claimArray ) {
			if ( array_key_exists( 'remove', $claimArray ) ) {
				if ( array_key_exists( 'id', $claimArray ) ) {
					$opsToReturn[] = $this->statementChangeOpFactory->newRemoveStatementOp( $claimArray['id'] );
				} else {
					$this->errorReporter->dieError( 'Cannot remove a claim with no GUID', 'invalid-claim' );
				}
			}
		}
		return $opsToReturn;
	}

	/**
	 * @param EntityDocument $entity
	 */
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
		if ( !isset( $params['data'] ) ) {
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

		$allowedProps = array(
			// ignored props
			'length',
			'count',
			'touched',
			// checked props
			'id',
			'type',
			'pageid',
			'ns',
			'title',
			'lastrevid',
			// useful props
			'labels',
			'descriptions',
			'aliases',
			'sitelinks',
			'claims',
			'datatype'
		);

		$this->checkValidJson( $data, $allowedProps );
		$this->checkEntityId( $data, $entityId );
		$this->checkEntityType( $data, $entity );
		$this->checkPageIdProp( $data, $title );
		$this->checkNamespaceProp( $data, $title );
		$this->checkTitleProp( $data, $title );
		$this->checkRevisionProp( $data, $revisionId );
	}

	/**
	 * @param mixed $data
	 * @param array $allowedProps
	 */
	private function checkValidJson( $data, array $allowedProps ) {
		if ( is_null( $data ) ) {
			$this->errorReporter->dieError( 'Invalid json: The supplied JSON structure could not be parsed or '
				. 'recreated as a valid structure', 'invalid-json' );
		}

		// NOTE: json_decode will decode any JS literal or structure, not just objects!
		$this->assertArray( $data, 'Top level structure must be a JSON object' );

		foreach ( $data as $prop => $args ) {
			// Catch json_decode returning an indexed array (list).
			$this->assertString( $prop, 'Top level structure must be a JSON object (no keys found)' );

			if ( !in_array( $prop, $allowedProps ) ) {
				$this->errorReporter->dieError( "Unknown key in json: $prop", 'not-recognized' );
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
			array(
				'data' => array(
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => true,
				),
				'clear' => array(
					self::PARAM_TYPE => 'boolean',
					self::PARAM_DFLT => false
				),
				'new' => array(
					self::PARAM_TYPE => $this->enabledEntityTypes,
				),
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
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
		);
	}

	/**
	 * Check some of the supplied data for multilang arg
	 *
	 * @param array $arg The argument array to verify
	 * @param string $langCode The language code used in the value part
	 */
	private function validateMultilangArgs( $arg, $langCode ) {
		$this->assertArray( $arg, 'An array was expected, but not found in the json for the '
			. "langCode $langCode" );

		if ( !array_key_exists( 'language', $arg ) ) {
			$this->errorReporter->dieError(
				"'language' was not found in the label or description json for $langCode",
					'missing-language' );
		}

		$this->assertString( $arg['language'], 'A string was expected, but not found in the json '
			. "for the langCode $langCode and argument 'language'" );
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$this->errorReporter->dieError(
					"inconsistent language: $langCode is not equal to {$arg['language']}",
					'inconsistent-language' );
			}
		}

		if ( !$this->termsLanguages->hasLanguage( $arg['language'] ) ) {
			$this->errorReporter->dieError( 'Unknown language: ' . $arg['language'], 'not-recognized-language' );
		}

		if ( !array_key_exists( 'remove', $arg ) ) {
			$this->assertString( $arg['value'], 'A string was expected, but not found in the json '
				. "for the langCode $langCode and argument 'value'" );
		}
	}

	/**
	 * Check some of the supplied data for sitelink arg
	 *
	 * @param array $arg The argument array to verify
	 * @param string $siteCode The site code used in the argument
	 * @param SiteList|null $sites The valid sites.
	 */
	private function checkSiteLinks( $arg, $siteCode, SiteList &$sites = null ) {
		$this->assertArray( $arg, 'An array was expected, but not found' );
		$this->assertString( $arg['site'], 'A string was expected, but not found' );

		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				$this->errorReporter->dieError( "inconsistent site: $siteCode is not equal to {$arg['site']}", 'inconsistent-site' );
			}
		}

		if ( $sites !== null && !$sites->hasSite( $arg['site'] ) ) {
			$this->errorReporter->dieError( 'Unknown site: ' . $arg['site'], 'not-recognized-site' );
		}

		if ( isset( $arg['title'] ) ) {
			$this->assertString( $arg['title'], 'A string was expected, but not found' );
		}

		if ( isset( $arg['badges'] ) ) {
			$this->assertArray( $arg['badges'], 'Badges: an array was expected, but not found' );
			foreach ( $arg['badges'] as $badge ) {
				$this->assertString( $badge, 'Badges: a string was expected, but not found' );
			}
		}
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

}
