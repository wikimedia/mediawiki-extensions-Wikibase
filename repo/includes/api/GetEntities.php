<?php

namespace Wikibase\Api;

use ApiBase;
use ApiMain;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\EntityRevision;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * API module to get the data for one or more Wikibase entities.
 *
 * @since 0.1
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Michał Łazowik
 * @author Adam Shorland
 */
class GetEntities extends ApiWikibase {

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct( ApiMain $mainModule, $moduleName, $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->stringNormalizer = $wikibaseRepo->getStringNormalizer();
		$this->languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();

		$this->siteLinkTargetProvider = new SiteLinkTargetProvider(
			$wikibaseRepo->getSiteStore(),
			$wikibaseRepo->getSettings()->getSetting( 'specialSiteLinkGroups' )
		);

		$this->siteLinkGroups = $wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' );
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		if ( !isset( $params['ids'] ) && ( empty( $params['sites'] ) || empty( $params['titles'] ) ) ) {
			$this->dieError(
				'Either provide the item "ids" or pairs of "sites" and "titles" for corresponding pages',
				'param-missing'
			);
		}

		$resolveRedirects = $params['redirects'] === 'yes';

		$entityIds = $this->getEntityIdsFromParams( $params );
		$entityRevisions = $this->getEntityRevisionsFromEntityIds( $entityIds, $resolveRedirects );

		foreach( $entityRevisions as $key => $entityRevision ) {
			$this->handleEntity( $key, $entityRevision, $params );
		}

		//todo remove once result builder is used... (what exactly does this do....?)
		if ( $this->getResult()->getIsRawMode() ) {
			$this->getResult()->setIndexedTagName_internal( array( 'entities' ), 'entity' );
		}

		$this->getResultBuilder()->markSuccess( 1 );
	}

	/**
	 * Get a unique array of EntityIds from api request params
	 *
	 * @param array $params
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsFromParams( array $params ) {
		$fromIds = $this->getEntityIdsFromIdParam( $params );
		$fromSiteTitleCombinations = $this->getItemIdsFromSiteTitleParams( $params );
		$ids = array_merge( $fromIds, $fromSiteTitleCombinations );
		return array_unique( $ids );
	}

	/**
	 * @param array $params
	 * @return EntityId[]
	 */
	private function getEntityIdsFromIdParam( $params ) {
		$ids = array();
		if( isset( $params['ids'] ) ) {
			foreach( $params['ids'] as $id ) {
				try {
					$ids[] = $this->getIdParser()->parse( $id );
				} catch( EntityIdParsingException $e ) {
					$this->dieError( "Invalid id: $id", 'no-such-entity' );
				}
			}
		}
		return $ids;
	}

	/**
	 * @param array $params
	 * @return EntityId[]
	 */
	private function getItemIdsFromSiteTitleParams( $params ) {
		$ids = array();
		if ( !empty( $params['sites'] ) && !empty( $params['titles'] ) ) {
			$itemByTitleHelper = $this->getItemByTitleHelper();
			list( $ids, $missingItems ) =  $itemByTitleHelper->getItemIds( $params['sites'], $params['titles'], $params['normalize'] );
			$this->addMissingItemsToResult( $missingItems );
		}
		return $ids;
	}

	/**
	 * @return ItemByTitleHelper
	 */
	private function getItemByTitleHelper() {
		$siteLinkCache = WikibaseRepo::getDefaultInstance()->getStore()->newSiteLinkCache();
		$siteStore = WikibaseRepo::getDefaultInstance()->getSiteStore();
		return new ItemByTitleHelper(
			$this->getResultBuilder(),
			$siteLinkCache,
			$siteStore,
			$this->stringNormalizer
		);
	}

	/**
	 * @param array $missingItems Array of arrays, Each internal array has a key 'site' and 'title'
	 */
	private function addMissingItemsToResult( $missingItems ){
		foreach( $missingItems as $missingItem ) {
			$this->getResultBuilder()->addMissingEntity( null, $missingItem );
		}
	}

	/**
	 * Returns props based on request parameters
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	private function getPropsFromParams( $params ) {
		if ( in_array( 'sitelinks/urls', $params['props'] ) ) {
			$params['props'][] = 'sitelinks';
		}

		return $params['props'];
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param bool $resolveRedirects
	 *
	 * @return EntityRevision[]
	 */
	private function getEntityRevisionsFromEntityIds( $entityIds, $resolveRedirects = false ) {
		$revisionArray = array();

		foreach ( $entityIds as $entityId ) {
			$key = $entityId->getSerialization();
			$entityRevision = $this->getEntityRevision( $entityId, $resolveRedirects );

			$revisionArray[$key] = $entityRevision;
		}

		return $revisionArray;
	}

	/**
	 * @param EntityId $entityId
	 * @param bool $resolveRedirects
	 *
	 * @return null|EntityRevision
	 */
	private function getEntityRevision( EntityId $entityId, $resolveRedirects = false ) {
		$entityRevision = null;

		try {
			$entityRevision = $this->getEntityRevisionLookup()->getEntityRevision( $entityId );
		} catch ( UnresolvedRedirectException $ex ) {
			if ( $resolveRedirects ) {
				$entityId = $ex->getRedirectTargetId();
				$entityRevision = $this->getEntityRevision( $entityId, false );
			}
		}

		return $entityRevision;
	}

	/**
	 * Adds the given EntityRevision to the API result.
	 *
	 * @param string|null $key
	 * @param EntityRevision|null $entityRevision
	 * @param array $params
	 */
	private function handleEntity( $key, EntityRevision $entityRevision = null, array $params = array() ) {
		if ( $entityRevision === null ) {
			$this->getResultBuilder()->addMissingEntity( $key, array( 'id' => $key ) );
		} else {
			$props = $this->getPropsFromParams( $params );
			$options = $this->getSerializationOptions( $params, $props );
			$siteFilterIds = $params['sitefilter'];

			$this->getResultBuilder()->addEntityRevision( $key, $entityRevision, $options, $props, $siteFilterIds );
		}
	}

	/**
	 * @param array $params
	 * @param array $props
	 *
	 * @return SerializationOptions
	 */
	private function getSerializationOptions( $params, $props ){
		$options = new SerializationOptions();
		if ( $params['languagefallback'] ) {
			$languages = array();
			foreach ( $params['languages'] as $languageCode ) {
				// $languageCode is already filtered as valid ones
				$languages[$languageCode] = $this->languageFallbackChainFactory
					->newFromContextAndLanguageCode( $this, $languageCode );
			}
		} else {
			$languages = $params['languages'];
		}
		if( $params['ungroupedlist'] ) {
			$options->setOption(
					SerializationOptions::OPT_GROUP_BY_PROPERTIES,
					array()
				);
		}
		$options->setLanguages( $languages );
		$options->setOption( EntitySerializer::OPT_SORT_ORDER, EntitySerializer::SORT_ASC );
		$options->setOption( EntitySerializer::OPT_PARTS, $props );
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		return $options;
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );
		return array_merge( parent::getAllowedParams(), array(
			'ids' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sites' => array(
				ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_ALLOW_DUPLICATES => true
			),
			'titles' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_ALLOW_DUPLICATES => true
			),
			'redirects' => array(
				ApiBase::PARAM_TYPE => array( 'yes', 'no' ),
				ApiBase::PARAM_DFLT => 'yes',
			),
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'info', 'sitelinks', 'sitelinks/urls', 'aliases', 'labels',
					'descriptions', 'claims', 'datatype' ),
				ApiBase::PARAM_DFLT => 'info|sitelinks|aliases|labels|descriptions|claims|datatype',
				ApiBase::PARAM_ISMULTI => true,
			),
			'languages' => array(
				ApiBase::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
				ApiBase::PARAM_ISMULTI => true,
			),
			'languagefallback' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
			'normalize' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false
			),
			'ungroupedlist' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
			'sitefilter' => array(
				ApiBase::PARAM_TYPE => $sites->getGlobalIdentifiers(),
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_ALLOW_DUPLICATES => true
			),
		) );
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return array(
			"action=wbgetentities&ids=Q42"
			=> "apihelp-wbgetentities-example-1",
			"action=wbgetentities&ids=P17"
			=> "apihelp-wbgetentities-example-2",
			"action=wbgetentities&ids=Q42|P17"
			=> "apihelp-wbgetentities-example-3",
			"action=wbgetentities&ids=Q42&languages=en"
			=> "apihelp-wbgetentities-example-4",
			"action=wbgetentities&ids=Q42&languages=ii&languagefallback="
			=> "apihelp-wbgetentities-example-5",
			"action=wbgetentities&ids=Q42&props=labels"
			=> "apihelp-wbgetentities-example-6",
			"action=wbgetentities&ids=P17|P3&props=datatype"
			=> "apihelp-wbgetentities-example-7",
			"action=wbgetentities&ids=Q42&props=aliases&languages=en"
			=> "apihelp-wbgetentities-example-8",
			"action=wbgetentities&ids=Q1|Q42&props=descriptions&languages=en|de|fr"
			=> "apihelp-wbgetentities-example-9",
			'action=wbgetentities&sites=enwiki&titles=Berlin&languages=en'
			=> 'apihelp-wbgetentities-example-10',
			'action=wbgetentities&sites=enwiki&titles=berlin&normalize='
			=> 'apihelp-wbgetentities-example-11',
			'action=wbgetentities&ids=Q42&props=sitelinks'
			=> 'apihelp-wbgetentities-example-12',
			'action=wbgetentities&ids=Q42&sitefilter=enwiki'
			=> 'apihelp-wbgetentities-example-13'
		);
	}

}
