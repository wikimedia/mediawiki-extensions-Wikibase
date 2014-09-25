<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use DataValues\IllegalValueException;
use InvalidArgumentException;
use LogicException;
use MWException;
use Site;
use SiteList;
use Title;
use UsageException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ChangeOp\ClaimChangeOpFactory;
use Wikibase\ChangeOp\FingerprintChangeOpFactory;
use Wikibase\ChangeOp\SiteLinkChangeOpFactory;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityFactory;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\Utils;

/**
 * Derived class for API modules modifying a single entity identified by id xor a combination of site and page title.
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
	 * @since 0.4
	 *
	 * @var string[]
	 */
	protected $validLanguageCodes;

	/**
	 * @since 0.5
	 *
	 * @var EntityRevisionLookup
	 */
	protected $entityRevisionLookup;

	/**
	 * @var FingerprintChangeOpFactory
	 */
	protected $termChangeOpFactory;

	/**
	 * @var ClaimChangeOpFactory
	 */
	protected $claimChangeOpFactory;

	/**
	 * @var SiteLinkChangeOpFactory
	 */
	protected $siteLinkChangeOpFactory;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );

		$this->validLanguageCodes = array_flip( Utils::getLanguageCodes() );

		$repo = WikibaseRepo::getDefaultInstance();
		$changeOpFactoryProvider = $repo->getChangeOpFactoryProvider();
		$this->termChangeOpFactory = $changeOpFactoryProvider->getFingerprintChangeOpFactory();
		$this->claimChangeOpFactory = $changeOpFactoryProvider->getClaimChangeOpFactory();
		$this->siteLinkChangeOpFactory = $changeOpFactoryProvider->getSiteLinkChangeOpFactory();
	}

	/**
	 * @see ApiWikibase::getRequiredPermissions
	 *
	 * @param Entity $entity
	 * @param array $params
	 *
	 * @return string[]
	 */
	protected function getRequiredPermissions( Entity $entity, array $params ) {
		$permissions = parent::getRequiredPermissions( $entity, $params );

		if ( !$this->entityExists( $entity ) ) {
			$permissions[] = 'createpage';
			if ( $entity->getType() === 'property'  ) {
				$permissions[] = 'property-create';
			}
		}

		return $permissions;
	}

	/**
	 * @param array $params
	 *
	 * @throws UsageException
	 * @throws LogicException
	 * @return Entity
	 *
	 * @see ModifyEntity::createEntity
	 */
	protected function createEntity( array $params ) {
		$type = $params['new'];
		$this->flags |= EDIT_NEW;
		$entityFactory = EntityFactory::singleton();

		try {
			return $entityFactory->newEmpty( $type );
		} catch ( InvalidArgumentException $e ) {
			$this->dieError( "No such entity type: '$type'", 'no-such-entity-type' );
		}

		throw new LogicException( 'ApiBase::dieUsage did not throw a UsageException' );
	}

	/**
	 * @see ModifyEntity::validateParameters
	 */
	protected function validateParameters( array $params ) {
		$hasId = isset( $params['id'] );
		$hasNew = isset( $params['new'] );
		$hasSitelink = ( isset( $params['site'] ) && isset( $params['title'] ) );
		$hasSitelinkPart = ( isset( $params['site'] ) || isset( $params['title'] ) );

		if ( !( $hasId XOR $hasSitelink XOR $hasNew ) ) {
			$this->dieError( 'Either provide the item "id" or pairs of "site" and "title" or a "new" type for an entity' , 'param-missing' );
		}
		if( $hasId && $hasSitelink ){
			$this->dieError( "Parameter 'id' and 'site', 'title' combination are not allowed to be both set in the same request", 'param-illegal' );
		}
		if( ( $hasId || $hasSitelinkPart ) && $hasNew ){
			$this->dieError( "Parameters 'id', 'site', 'title' and 'new' are not allowed to be both set in the same request", 'param-illegal' );
		}
	}

	/**
	 * @see ModifyEntity::modifyEntity
	 */
	protected function modifyEntity( Entity &$entity, array $params, $baseRevId ) {
		wfProfileIn( __METHOD__ );

		$this->validateDataParameter( $params );
		$data = json_decode( $params['data'], true );
		$this->validateDataProperties( $data, $entity, $baseRevId );

		$revisionLookup = $this->getEntityRevisionLookup();
		$exists = $this->entityExists( $entity );

		if ( $params['clear'] ) {
			if( $params['baserevid'] && $exists ) {
				$latestRevision = $revisionLookup->getLatestRevisionId( $entity->getId() );
				if( !$baseRevId === $latestRevision ) {
					wfProfileOut( __METHOD__ );
					$this->dieError(
						'Tried to clear entity using baserevid of entity not equal to current revision',
						'editconflict'
					);
				}
			}
			$entity->clear();

			// bug 67791 (can be removed with DataModel 1.0)
			if ( method_exists( $entity, 'setClaims' ) ) {
				$entity->setClaims( new Claims() );
			}
		}

		// if we create a new property, make sure we set the datatype
		if( !$exists && $entity instanceof Property ){
			if ( !isset( $data['datatype'] ) ) {
				wfProfileOut( __METHOD__ );
				$this->dieError( 'No datatype given', 'param-illegal' );
			} else {
				$entity->setDataTypeId( $data['datatype'] );
			}
		}

		$changeOps = $this->getChangeOps( $data, $entity );

		$this->applyChangeOp( $changeOps, $entity );

		$this->buildResult( $entity );
		$summary = $this->getSummary( $params );

		wfProfileOut( __METHOD__ );
		return $summary;
	}

	/**
	 * @param array $params
	 * @return Summary
	 */
	private function getSummary( $params ) {
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
	 * @param Entity $entity
	 *
	 * @return ChangeOps
	 */
	protected function getChangeOps( array $data, Entity $entity ) {
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
			if ( $entity->getType() !== Item::ENTITY_TYPE ) {
				$this->dieError( "Non Items can not have sitelinks", 'not-recognized' );
			}

			$changeOps->add( $this->getSiteLinksChangeOps( $data['sitelinks'], $entity ) );
		}

		if( array_key_exists( 'claims', $data ) ) {
			$changeOps->add(
				$this->getClaimsChangeOps( $data['claims'] )
			);
		}

		return $changeOps;
	}

	/**
	 * @since 0.4
	 * @param array $labels
	 * @return ChangeOp[]
	 */
	protected function getLabelChangeOps( $labels  ) {
		$labelChangeOps = array();

		if ( !is_array( $labels ) ) {
			$this->dieError( "List of labels must be an array", 'not-recognized-array' );
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
	 * @since 0.4
	 * @param array $descriptions
	 * @return ChangeOp[]
	 */
	protected function getDescriptionChangeOps( $descriptions ) {
		$descriptionChangeOps = array();

		if ( !is_array( $descriptions ) ) {
			$this->dieError( "List of descriptions must be an array", 'not-recognized-array' );
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
	 * @since 0.4
	 * @param array $aliases
	 * @return ChangeOp[]
	 */
	protected function getAliasesChangeOps( $aliases ) {
		if ( !is_array( $aliases ) ) {
			$this->dieError( "List of aliases must be an array", 'not-recognized-array' );
		}

		$indexedAliases = $this->getIndexedAliases( $aliases );
		$aliasesChangeOps = $this->getIndexedAliasesChangeOps( $indexedAliases );

		return $aliasesChangeOps;
	}

	/**
	 * @param array $aliases
	 * @return array
	 */
	protected function getIndexedAliases( array $aliases ) {
		$indexedAliases = array();

		foreach ( $aliases as $langCode => $arg ) {
			if ( intval( $langCode ) ) {
				$indexedAliases[] = ( array_values($arg) === $arg ) ? $arg : array( $arg );
			} else {
				$indexedAliases[$langCode] = ( array_values($arg) === $arg ) ? $arg : array( $arg );
			}
		}

		return $indexedAliases;
	}

	/**
	 * @param array $indexedAliases
	 * @return ChangeOp[]
	 */
	protected function getIndexedAliasesChangeOps( array $indexedAliases ) {
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
	 * @since 0.4
	 *
	 * @param array $siteLinks
	 * @param Entity|Item $entity
	 *
	 * @return ChangeOp[]
	 */
	protected function getSiteLinksChangeOps( $siteLinks, Entity $entity ) {
		$siteLinksChangeOps = array();

		if ( !is_array( $siteLinks ) ) {
			$this->dieError( "List of sitelinks must be an array", 'not-recognized-array' );
		}

		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		foreach ( $siteLinks as $siteId => $arg ) {
			$this->checkSiteLinks( $arg, $siteId, $sites );
			$globalSiteId = $arg['site'];

			$shouldRemove = array_key_exists( 'remove', $arg )
				|| ( !isset( $arg['title'] ) && !isset( $arg['badges'] ) )
				|| ( isset( $arg['title'] ) && $arg['title'] === '' );

			if ( $sites->hasSite( $globalSiteId ) ) {
				$linkSite = $sites->getSite( $globalSiteId );
			} else {
				$this->dieError( "There is no site for global site id '$globalSiteId'", 'no-such-site' );
			}
			/** @var Site $linkSite */

			if ( $shouldRemove ) {
				$siteLinksChangeOps[] = $this->siteLinkChangeOpFactory->newRemoveSiteLinkOp( $globalSiteId );
			} else {
				$badges = ( isset( $arg['badges'] ) )
					? $this->parseSiteLinkBadges( $arg['badges'] )
					: null;

				if ( isset( $arg['title'] ) ) {
					$linkPage = $linkSite->normalizePageName( $this->stringNormalizer->trimWhitespace( $arg['title'] ) );

					if ( $linkPage === false ) {
						$this->dieError(
							"The external client site did not provide page information for site '{$globalSiteId}'",
							'no-external-page' );
					}
				} else {
					$linkPage = null;

					if ( !$entity->hasLinkToSite( $globalSiteId ) ) {
						$this->dieError( "Cannot modify badges: sitelink to '{$globalSiteId}' doesn't exist", 'no-such-sitelink' );
					}
				}

				$siteLinksChangeOps[] = $this->siteLinkChangeOpFactory->newSetSiteLinkOp( $globalSiteId, $linkPage, $badges );
			}
		}

		return $siteLinksChangeOps;
	}

	/**
	 * @since 0.5
	 *
	 * @param array $claims
	 * @return ChangeOp[]
	 */
	protected function getClaimsChangeOps( $claims ) {
		if ( !is_array( $claims ) ) {
			$this->dieError( "List of claims must be an array", 'not-recognized-array' );
		}
		$changeOps = array();

		//check if the array is associative or in arrays by property
		if( array_keys( $claims ) !== range( 0, count( $claims ) - 1 ) ){
			foreach( $claims as $subClaims ){
				$changeOps = array_merge( $changeOps,
					$this->getRemoveClaimsChangeOps( $subClaims ),
					$this->getModifyClaimsChangeOps( $subClaims ) );
			}
		} else {
			$changeOps = array_merge( $changeOps,
				$this->getRemoveClaimsChangeOps( $claims ),
				$this->getModifyClaimsChangeOps( $claims ) );
		}

		return $changeOps;
	}

	/**
	 * @param array $claims array of serialized claims
	 *
	 * @return ChangeOp[]
	 */
	private function getModifyClaimsChangeOps( $claims ){
		$opsToReturn = array();

		$serializerFactory = new SerializerFactory();
		$unserializer = $serializerFactory->newUnserializerForClass( 'Wikibase\DataModel\Claim\Claim' );

		foreach ( $claims as $claimArray ) {
			if( !array_key_exists( 'remove', $claimArray ) ){
				try {
					$claim = $unserializer->newFromSerialization( $claimArray );

					if ( !( $claim instanceof Claim ) ) {
						throw new IllegalValueException( 'Claim serialization did not contained a Claim.' );
					}

					$opsToReturn[] = $this->claimChangeOpFactory->newSetClaimOp( $claim );
				} catch ( IllegalValueException $ex ) {
					$this->dieException( $ex, 'invalid-claim' );
				} catch ( MWException $ex ) {
					$this->dieException( $ex, 'invalid-claim' );
				}
			}
		}
		return $opsToReturn;
	}

	/**
	 * Get changeops that remove all claims that have the 'remove' key in the array
	 *
	 * @param array $claims array of serialized claims
	 *
	 * @return ChangeOp[]
	 */
	private function getRemoveClaimsChangeOps( $claims ) {
		$opsToReturn = array();
		foreach ( $claims as $claimArray ) {
			if( array_key_exists( 'remove', $claimArray ) ){
				if( array_key_exists( 'id', $claimArray ) ){
					$opsToReturn[] = $this->claimChangeOpFactory->newRemoveClaimOp( $claimArray['id'] );
				} else {
					$this->dieError( 'Cannot remove a claim with no GUID', 'invalid-claim' );
				}
			}
		}
		return $opsToReturn;
	}

	/**
	 * @param Entity $entity
	 */
	protected function buildResult( Entity $entity ) {
		$this->getResultBuilder()->addLabels( $entity->getLabels(), 'entity' );
		$this->getResultBuilder()->addDescriptions( $entity->getDescriptions(), 'entity' );
		$this->getResultBuilder()->addAliases( $entity->getAllAliases(), 'entity' );

		if ( $entity instanceof Item ) {
			$this->getResultBuilder()->addSiteLinks( $entity->getSiteLinks(), 'entity' );
		}

		$this->getResultBuilder()->addClaims( $entity->getClaims(), 'entity' );
	}

	/**
	 * @param array $params
	 */
	private function validateDataParameter( $params ) {
		if ( !isset( $params['data'] ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieError( 'No data to operate upon', 'no-data' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param mixed $data
	 * @param Entity $entity
	 * @param int $revId
	 */
	protected function validateDataProperties( $data, Entity $entity, $revId = 0 ) {
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
		$this->checkRevisionProp( $data, $revId );
	}

	/**
	 * @param array $data
	 * @param array $allowedProps
	 */
	protected function checkValidJson( $data, array $allowedProps ) {
		if ( is_null( $data ) ) {
			$this->dieError( 'Invalid json: The supplied JSON structure could not be parsed or '
				. 'recreated as a valid structure' , 'invalid-json' );
		}

		// NOTE: json_decode will decode any JS literal or structure, not just objects!
		if ( !is_array( $data ) ) {
			$this->dieError( 'Top level structure must be a JSON object', 'not-recognized-array' );
		}

		foreach ( $data as $prop => $args ) {
			if ( !is_string( $prop ) ) { // NOTE: catch json_decode returning an indexed array (list)
				$this->dieError( 'Top level structure must be a JSON object, (no keys found)', 'not-recognized-string' );
			}

			if ( !in_array( $prop, $allowedProps ) ) {
				$this->dieError( "Unknown key in json: $prop", 'not-recognized' );
			}
		}
	}

	/**
	 * @param array $data
	 * @param Title|null $title
	 */
	protected function checkPageIdProp( $data, $title ) {
		if ( isset( $data['pageid'] )
			&& ( is_object( $title ) ? $title->getArticleID() !== $data['pageid'] : true ) ) {
			$this->dieError(
				'Illegal field used in call, "pageid", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	/**
	 * @param array $data
	 * @param Title|null $title
	 */
	protected function checkNamespaceProp( $data, $title ) {
		// not completely convinced that we can use title to get the namespace in this case
		if ( isset( $data['ns'] )
			&& ( is_object( $title ) ? $title->getNamespace() !== $data['ns'] : true ) ) {
			$this->dieError(
				'Illegal field used in call: "namespace", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	/**
	 * @param array $data
	 * @param Title|null $title
	 */
	protected function checkTitleProp( $data, $title ) {
		if ( isset( $data['title'] )
			&& ( is_object( $title ) ? $title->getPrefixedText() !== $data['title'] : true ) ) {
			$this->dieError(
				'Illegal field used in call: "title", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	/**
	 * @param array $data
	 * @param int|null $revisionId
	 */
	protected function checkRevisionProp( $data, $revisionId ) {
		if ( isset( $data['lastrevid'] )
			&& ( is_int( $revisionId ) ? $revisionId !== $data['lastrevid'] : true ) ) {
			$this->dieError(
				'Illegal field used in call: "lastrevid", must either be correct or not given',
				'param-illegal'
			);
		}
	}

	private function checkEntityId( $data, EntityId $entityId = null ) {
		if ( isset( $data['id'] ) ) {
			if ( !$entityId ) {
				$this->dieError(
					'Illegal field used in call: "id", must not be given when creating a new entity',
					'param-illegal'
				);
			}

			$dataId = $this->getIdParser()->parse( $data['id'] );
			if( !$entityId->equals( $dataId ) ) {
				$this->dieError(
					'Invalid field used in call: "id", must match id parameter',
					'param-invalid'
				);
			}
		}
	}

	private function checkEntityType( $data, Entity $entity ) {
		if ( isset( $data['type'] )
			&& $entity->getType() !== $data['type'] ) {
			$this->dieError(
				'Invalid field used in call: "type", must match type associated with id',
				'param-invalid'
			);
		}
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	public function getAllowedParams() {
		return array_merge(
			parent::getAllowedParams(),
			parent::getAllowedParamsForId(),
			parent::getAllowedParamsForSiteLink(),
			parent::getAllowedParamsForEntity(),
			array(
				'data' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'clear' => array(
					ApiBase::PARAM_TYPE => 'boolean',
					ApiBase::PARAM_DFLT => false
				),
				'new' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
				'type' => array(
					ApiBase::PARAM_TYPE => 'string',
				),
			)
		);
	}

	/**
	 * @see ApiBase::getParamDescription
	 */
	public function getParamDescription() {
		return array_merge(
			parent::getParamDescription(),
			parent::getParamDescriptionForId(),
			parent::getParamDescriptionForSiteLink(),
			parent::getParamDescriptionForEntity(),
			array(
				'data' => array( 'The serialized object that is used as the data source.',
					"A newly created entity will be assigned an 'id'."
				),
				'clear' => array( 'If set, the complete entity is emptied before proceeding.',
					'The entity will not be saved before it is filled with the "data", possibly with parts excluded.'
				),
				'new' => array( "If set, a new entity will be created.",
					"Set this to the type of the entity you want to create - currently 'item'|'property'.",
					"It is not allowed to have this set when 'id' is also set."
				),
				'type' => array( 'A specific type of entity.',
					"Will default to 'item' as this will be the most common type."
				),
			)
		);
	}

	/**
	 * @see ApiBase::getDescription
	 */
	public function getDescription() {
		return array(
			'API module to create a single new Wikibase entity and modify it with serialised information.'
		);
	}

	/**
	 * @see ApiBase::getExamples
	 */
	protected function getExamples() {
		return array(
			// Creating new entites
			'api.php?action=wbeditentity&new=item&data={}'
			=> 'Create a new empty item, return full entity structure',
			'api.php?action=wbeditentity&new=item&data={"labels":{"de":{"language":"de","value":"de-value"},"en":{"language":"en","value":"en-value"}}}'
			=> 'Create a new item and set labels for de and en',
			'api.php?action=wbeditentity&new=property&data={"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},"datatype":"string"}'
			=> 'Create a new property containing the json data, returns extended with the item structure',
			// Clearing entities
			'api.php?action=wbeditentity&clear=true&id=Q42&data={}'
			=> 'Clear all data from entity with id Q42',
			'api.php?action=wbeditentity&clear=true&id=Q42&data={"labels":{"en":{"language":"en","value":"en-value"}}}'
			=> 'Clear all data from entity with id Q42 and set a label for en',
			// Setting stuff
			'api.php?action=wbeditentity&id=Q42&data={"sitelinks":{"nowiki":{"site":"nowiki","title":"København"}}}'
			=> 'Sets sitelink for nowiki, overwriting it if it already exists',
			'api.php?action=wbeditentity&id=Q42&data={"descriptions":{"nb":{"language":"nb","value":"nb-Description-Here"}}}'
			=> 'Sets description for nb, overwriting it if it already exists',
			'api.php?action=wbeditentity&id=Q42&data={"claims":[{"mainsnak":{"snaktype":"value","property":"P56","datavalue":{"value":"ExampleString","type":"string"}},"type":"statement","rank":"normal"}]}'
			=> 'Creates a new claim on the item for the property P56 and a value of "ExampleString"',
			'api.php?action=wbeditentity&id=Q42&data={"claims":[{"id":"Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F","remove":""},{"id":"Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","remove":""}]}'
			=> 'Removes the claims from the item with the guids Q42$D8404CDA-25E4-4334-AF13-A3290BCD9C0F and Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X',
			'api.php?action=wbeditentity&id=Q42&data={"claims":[{"id":"Q42$GH678DSA-01PQ-28XC-HJ90-DDFD9990126X","mainsnak":{"snaktype":"value","property":"P56","datavalue":{"value":"ChangedString","type":"string"}},"type":"statement","rank":"normal"}]}'
			=> 'Sets the claim with the GUID to the value of the claim',
		);
	}

	/**
	 * Check some of the supplied data for multilang arg
	 * @param array $arg The argument array to verify
	 * @param string $langCode The language code used in the value part
	 */
	public function validateMultilangArgs( $arg, $langCode ) {
		if ( !is_array( $arg ) ) {
			$this->dieError(
				"An array was expected, but not found in the json for the langCode {$langCode}" ,
				'not-recognized-array' );
		}
		if ( !is_string( $arg['language'] ) ) {
			$this->dieError(
				"A string was expected, but not found in the json for the langCode {$langCode} and argument 'language'" ,
				'not-recognized-string' );
		}
		if ( !is_numeric( $langCode ) ) {
			if ( $langCode !== $arg['language'] ) {
				$this->dieError(
					"inconsistent language: {$langCode} is not equal to {$arg['language']}",
					'inconsistent-language' );
			}
		}
		if ( isset( $this->validLanguageCodes ) && !array_key_exists( $arg['language'], $this->validLanguageCodes ) ) {
			$this->dieError(
				"unknown language: {$arg['language']}",
				'not-recognized-language' );
		}
		if ( !array_key_exists( 'remove', $arg ) && !is_string( $arg['value'] ) ) {
			$this->dieError(
				"A string was expected, but not found in the json for the langCode {$langCode} and argument 'value'" ,
				'not-recognized-string' );
		}
	}

	/**
	 * Check some of the supplied data for sitelink arg
	 *
	 * @param array $arg The argument array to verify
	 * @param string $siteCode The site code used in the argument
	 * @param SiteList $sites The valid site codes as an assoc array
	 */
	public function checkSiteLinks( $arg, $siteCode, SiteList &$sites = null ) {
		if ( !is_array( $arg ) ) {
			$this->dieError( 'An array was expected, but not found' , 'not-recognized-array' );
		}
		if ( !is_string( $arg['site'] ) ) {
			$this->dieError( 'A string was expected, but not found' , 'not-recognized-string' );
		}
		if ( !is_numeric( $siteCode ) ) {
			if ( $siteCode !== $arg['site'] ) {
				$this->dieError( "inconsistent site: {$siteCode} is not equal to {$arg['site']}", 'inconsistent-site' );
			}
		}
		if ( isset( $sites ) && !$sites->hasSite( $arg['site'] ) ) {
			$this->dieError( "unknown site: {$arg['site']}", 'not-recognized-site' );
		}
		if ( isset( $arg['title'] ) && !is_string( $arg['title'] ) ) {
			$this->dieError( 'A string was expected, but not found' , 'not-recognized-string' );
		}
		if ( isset( $arg['badges'] ) ) {
			if ( !is_array( $arg['badges'] ) ) {
				$this->dieError( 'Badges: an array was expected, but not found' , 'not-recognized-array' );
			} else {
				foreach ( $arg['badges'] as $badge ) {
					if ( !is_string( $badge ) ) {
						$this->dieError( 'Badges: a string was expected, but not found' , 'not-recognized-string' );
					}
				}
			}
		}
	}

}
