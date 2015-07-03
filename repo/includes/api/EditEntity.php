<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use LogicException;
use MWException;
use SiteList;
use Title;
use UsageException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\StatementChangeOpFactory;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Lib\Serializers\LibSerializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Derived class for API modules modifying a single entity identified by id xor a combination of
 * site and page title.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
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

		$changeOpFactoryProvider = $wikibaseRepo->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->statementChangeOpFactory = $changeOpFactoryProvider->getStatementChangeOpFactory();
		$this->siteLinkChangeOpFactory = $changeOpFactoryProvider->getSiteLinkChangeOpFactory();
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
	 * @return Entity
	 */
	protected function createEntity( $entityType ) {
		$this->flags |= EDIT_NEW;
		$entityFactory = WikibaseRepo::getDefaultInstance()->getEntityFactory();

		try {
			return $entityFactory->newEmpty( $entityType );
		} catch ( InvalidArgumentException $ex ) {
			$this->errorReporter->dieError( "No such entity type: '$entityType'", 'no-such-entity-type' );
		}

		throw new LogicException( 'ApiBase::dieUsage did not throw a UsageException' );
	}

	/**
	 * @see ModifyEntity::validateParameters
	 */
	protected function validateParameters( array $params ) {
		$hasId = isset( $params['id'] );
		$hasNew = isset( $params['new'] );
		$hasSiteLink = isset( $params['site'] ) && isset( $params['title'] );
		$hasSiteLinkPart = isset( $params['site'] ) || isset( $params['title'] );

		if ( !( $hasId XOR $hasSiteLink XOR $hasNew ) ) {
			$this->errorReporter->dieError( 'Either provide the item "id" or pairs of "site" and "title" or a "new" type for an entity', 'param-missing' );
		}
		if ( $hasId && $hasSiteLink ) {
			$this->errorReporter->dieError( "Parameter 'id' and 'site', 'title' combination are not allowed to be both set in the same request", 'param-illegal' );
		}
		if ( ( $hasId || $hasSiteLinkPart ) && $hasNew ) {
			$this->errorReporter->dieError( "Parameters 'id', 'site', 'title' and 'new' are not allowed to be both set in the same request", 'param-illegal' );
		}
	}

	/**
	 * @see ModifyEntity::modifyEntity
	 */
	protected function modifyEntity( Entity &$entity, array $params, $baseRevId ) {
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
			$entity->clear();
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

		$this->applyChangeOp( $changeOps, $entity );

		$this->buildResult( $entity );
		return $this->getSummary( $params );
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
		if ( isset( $params['id'] ) XOR ( isset( $params['site'] ) && isset( $params['title'] ) ) ) {
			$summary->setAction( $params['clear'] === false ? 'update' : 'override' );
		} else {
			$summary->setAction( 'create' );
		}#
		return $summary;
	}

	/**
	 * @param array $data
	 * @param EntityDocument $entity
	 *
	 * @return ChangeOps
	 */
	private function getChangeOps( array $data, EntityDocument $entity ) {
		$changeOps = new ChangeOps();

		//FIXME: Use a ChangeOpBuilder so we can batch fingerprint ops etc,
		//       for more efficient validation!

		if ( array_key_exists( 'labels', $data ) ) {
			$changeOps->add( $this->getLabelChangeOps( $data['labels'] ) );
		}

		if ( array_key_exists( 'descriptions', $data ) ) {
			$changeOps->add( $this->getDescriptionChangeOps( $data['descriptions'] ) );
		}

		if ( array_key_exists( 'aliases', $data ) ) {
			$changeOps->add( $this->getAliasesChangeOps( $data['aliases'] ) );
		}

		if ( array_key_exists( 'sitelinks', $data ) ) {
			if ( !( $entity instanceof Item ) ) {
				$this->errorReporter->dieError( 'Non Items cannot have sitelinks', 'not-recognized' );
			}

			$changeOps->add( $this->getSiteLinksChangeOps( $data['sitelinks'], $entity ) );
		}

		if ( array_key_exists( 'claims', $data ) ) {
			$changeOps->add(
				$this->getClaimsChangeOps( $data['claims'] )
			);
		}

		return $changeOps;
	}

	/**
	 * @param array[] $labels
	 *
	 * @return ChangeOp[]
	 */
	private function getLabelChangeOps( $labels ) {
		$labelChangeOps = array();

		if ( !is_array( $labels ) ) {
			$this->errorReporter->dieError( "List of labels must be an array", 'not-recognized-array' );
		}

		foreach ( $labels as $langCode => $arg ) {
			$this->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newLabel = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newLabel === "" ) {
				$labelChangeOps[] = $this->termChangeOpFactory->newRemoveLabelOp( $language );
			}
			else {
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
	private function getDescriptionChangeOps( $descriptions ) {
		$descriptionChangeOps = array();

		if ( !is_array( $descriptions ) ) {
			$this->errorReporter->dieError( "List of descriptions must be an array", 'not-recognized-array' );
		}

		foreach ( $descriptions as $langCode => $arg ) {
			$this->validateMultilangArgs( $arg, $langCode );

			$language = $arg['language'];
			$newDescription = ( array_key_exists( 'remove', $arg ) ? '' :
				$this->stringNormalizer->trimToNFC( $arg['value'] ) );

			if ( $newDescription === "" ) {
				$descriptionChangeOps[] = $this->termChangeOpFactory->newRemoveDescriptionOp( $language );
			}
			else {
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
	private function getAliasesChangeOps( $aliases ) {
		if ( !is_array( $aliases ) ) {
			$this->errorReporter->dieError( "List of aliases must be an array", 'not-recognized-array' );
		}

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
				}  else {
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
	private function getSiteLinksChangeOps( $siteLinks, Item $item ) {
		$siteLinksChangeOps = array();

		if ( !is_array( $siteLinks ) ) {
			$this->errorReporter->dieError( 'List of sitelinks must be an array', 'not-recognized-array' );
		}

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
	private function getClaimsChangeOps( $claims ) {
		if ( !is_array( $claims ) ) {
			$this->errorReporter->dieError( "List of claims must be an array", 'not-recognized-array' );
		}
		$changeOps = array();

		//check if the array is associative or in arrays by property
		if ( array_keys( $claims ) !== range( 0, count( $claims ) - 1 ) ) {
			foreach ( $claims as $subClaims ) {
				$changeOps = array_merge( $changeOps,
					$this->getRemoveStatementChangeOps( $subClaims ),
					$this->getModifyClaimsChangeOps( $subClaims ) );
			}
		} else {
			$changeOps = array_merge( $changeOps,
				$this->getRemoveStatementChangeOps( $claims ),
				$this->getModifyClaimsChangeOps( $claims ) );
		}

		return $changeOps;
	}

	/**
	 * @param array[] $claims array of serialized claims
	 *
	 * @return ChangeOp[]
	 */
	private function getModifyClaimsChangeOps( array $claims ) {
		$opsToReturn = array();

		$serializerFactory = new LibSerializerFactory();
		$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\DataModel\Claim\Claim' );

		foreach ( $claims as $claimArray ) {
			if ( !array_key_exists( 'remove', $claimArray ) ) {
				try {
					$claim = $unserializer->newFromSerialization( $claimArray );

					if ( !( $claim instanceof Claim ) ) {
						throw new IllegalValueException( 'Claim serialization did not contained a Claim.' );
					}

					$opsToReturn[] = $this->statementChangeOpFactory->newSetStatementOp( $claim );
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
	 * @param Entity $entity
	 */
	private function buildResult( Entity $entity ) {
		$builder = $this->getResultBuilder();

		if ( $entity instanceof FingerprintProvider ) {
			$fingerprint = $entity->getFingerprint();

			$builder->addLabels( $fingerprint->getLabels(), 'entity' );
			$builder->addDescriptions( $fingerprint->getDescriptions(), 'entity' );
			$builder->addAliasGroupList( $fingerprint->getAliasGroups(), 'entity' );
		}

		if ( $entity instanceof Item ) {
			$builder->addSiteLinks( $entity->getSiteLinkList()->toArray(), 'entity' );
		}

		$builder->addClaims( $entity->getClaims(), 'entity' );
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
		if ( !is_array( $data ) ) {
			$this->errorReporter->dieError( 'Top level structure must be a JSON object', 'not-recognized-array' );
		}

		foreach ( $data as $prop => $args ) {
			if ( !is_string( $prop ) ) { // NOTE: catch json_decode returning an indexed array (list)
				$this->errorReporter->dieError( 'Top level structure must be a JSON object, (no keys found)', 'not-recognized-string' );
			}

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
					ApiBase::PARAM_TYPE => 'text',
				),
				'clear' => array(
					ApiBase::PARAM_TYPE => 'boolean',
					ApiBase::PARAM_DFLT => false
				),
				'new' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
			)
		);
	}

	/**
	 * @see ApiBase:getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			// Creating new entites
			'action=wbeditentity&new=item&data={}'
			=> 'apihelp-wbeditentity-example-1',
			'action=wbeditentity&new=item&data={"labels":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'apihelp-wbeditentity-example-2',
			'action=wbeditentity&new=property&data={"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},"datatype":"string"}'
			=> 'apihelp-wbeditentity-example-3',
			// Clearing entities
			'action=wbeditentity&clear=true&id=Q42&data={}'
			=> 'apihelp-wbeditentity-example-4',
			'action=wbeditentity&clear=true&id=Q42&data={"labels":{"en":{"language":"en","value":"en-value"}}}'
			=> 'apihelp-wbeditentity-example-5',
			// Setting stuff
			'action=wbeditentity&id=Q42&data={"sitelinks":{"nowiki":{"site":"nowiki","title":"København"}}}'
			=> 'apihelp-wbeditentity-example-6',
			'action=wbeditentity&id=Q42&data={"descriptions":{"nb":{"language":"nb","value":"nb-Description-Here"}}}'
			=> 'apihelp-wbeditentity-example-7',
			'action=wbeditentity&id=Q42&data={"claims":[{"mainsnak":{"snaktype":"value","property":"P56","datavalue":{"value":"ExampleString","type":"string"}},"type":"statement","rank":"normal"}]}'
			=> 'apihelp-wbeditentity-example-8',
			'action=wbeditentity&id=Q42&data={"claims":[{"id":"Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F","remove":""},{"id":"Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","remove":""}]}'
			=> 'apihelp-wbeditentity-example-9',
			'action=wbeditentity&id=Q42&data={"claims":[{"id":"Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","mainsnak":{"snaktype":"value","property":"P56","datavalue":{"value":"ChangedString","type":"string"}},"type":"statement","rank":"normal"}]}'
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
		if ( !is_array( $arg ) ) {
			$this->errorReporter->dieError(
				"An array was expected, but not found in the json for the langCode {$langCode}",
				'not-recognized-array' );
		}

		if ( !array_key_exists( 'language', $arg ) ) {
			$this->errorReporter->dieError(
				"'language' was not found in the label or description json for {$langCode}",
					'missing-language' );
		}

		if ( !is_string( $arg['language'] ) ) {
			$this->errorReporter->dieError(
				"A string was expected, but not found in the json for the langCode {$langCode} and argument 'language'",
				'not-recognized-string' );
		}
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$this->errorReporter->dieError(
					"inconsistent language: {$langCode} is not equal to {$arg['language']}",
					'inconsistent-language' );
			}
		}

		if ( !$this->termsLanguages->hasLanguage( $arg['language'] ) ) {
			$this->errorReporter->dieError( 'Unknown language: ' . $arg['language'], 'not-recognized-language' );
		}

		if ( !array_key_exists( 'remove', $arg ) && !is_string( $arg['value'] ) ) {
			$this->errorReporter->dieError(
				"A string was expected, but not found in the json for the langCode {$langCode} and argument 'value'",
				'not-recognized-string' );
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
		if ( !is_array( $arg ) ) {
			$this->errorReporter->dieError( 'An array was expected, but not found', 'not-recognized-array' );
		}
		if ( !is_string( $arg['site'] ) ) {
			$this->errorReporter->dieError( 'A string was expected, but not found', 'not-recognized-string' );
		}
		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				$this->errorReporter->dieError( "inconsistent site: {$siteCode} is not equal to {$arg['site']}", 'inconsistent-site' );
			}
		}
		if ( $sites !== null && !$sites->hasSite( $arg['site'] ) ) {
			$this->errorReporter->dieError( 'Unknown site: ' . $arg['site'], 'not-recognized-site' );
		}
		if ( isset( $arg['title'] ) && !is_string( $arg['title'] ) ) {
			$this->errorReporter->dieError( 'A string was expected, but not found', 'not-recognized-string' );
		}
		if ( isset( $arg['badges'] ) ) {
			if ( !is_array( $arg['badges'] ) ) {
				$this->errorReporter->dieError( 'Badges: an array was expected, but not found', 'not-recognized-array' );
			} else {
				foreach ( $arg['badges'] as $badge ) {
					if ( !is_string( $badge ) ) {
						$this->errorReporter->dieError( 'Badges: a string was expected, but not found', 'not-recognized-string' );
					}
				}
			}
		}
	}

}
