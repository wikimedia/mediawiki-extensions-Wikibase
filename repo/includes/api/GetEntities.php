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
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;
use Wikibase\Utils;

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
	protected $stringNormalizer;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	protected $languageFallbackChainFactory;

	/**
	 * @var SiteLinkTargetProvider
	 */
	private $siteLinkTargetProvider;

	/**
	 * @since 0.5
	 *
	 * @var array
	 */
	protected $siteLinkGroups;

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
		$this->siteLinkTargetProvider = new SiteLinkTargetProvider( $wikibaseRepo->getSiteStore() );
		$this->siteLinkGroups = $wikibaseRepo->getSettings()->getSetting( 'siteLinkGroups' );
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		wfProfileIn( __METHOD__ );
		$params = $this->extractRequestParams();

		if ( !isset( $params['ids'] ) && ( empty( $params['sites'] ) || empty( $params['titles'] ) ) ) {
			wfProfileOut( __METHOD__ );
			$this->dieUsage(
				'Either provide the item "ids" or pairs of "sites" and "titles" for corresponding pages',
				'param-missing'
			);
		}

		$entityIds = $this->getEntityIdsFromParams( $params );
		$entityRevisions = $this->getEntityRevisionsFromEntityIds( $entityIds );
		foreach( $entityRevisions as $entityRevision ) {
			$this->handleEntity( $entityRevision, $params );
		}

		//todo remove once result builder is used... (what exactly does this do....?)
		if ( $this->getResult()->getIsRawMode() ) {
			$this->getResult()->setIndexedTagName_internal( array( 'entities' ), 'entity' );
		}

		$this->getResultBuilder()->markSuccess( 1 );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Get a unique array of EntityIds from api request params
	 *
	 * @param array $params
	 *
	 * @return EntityId[]
	 */
	protected function getEntityIdsFromParams( array $params ) {
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
					$ids[] = $this->idParser->parse( $id );
				} catch( EntityIdParsingException $e ) {
					wfProfileOut( __METHOD__ );
					$this->dieUsage( "Invalid id: $id", 'no-such-entity' );
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
			$this->getResultBuilder()->addMissingEntity( $missingItem );
		}
	}

	/**
	 * Returns props based on request parameters
	 *
	 * @since 0.5
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	protected function getPropsFromParams( $params ) {
		if ( in_array( 'sitelinks/urls', $params['props'] ) ) {
			$params['props'][] = 'sitelinks';
		}

		return $params['props'];
	}

	/**
	 * @param EntityId[] $entityIds
	 * @return EntityRevision[]
	 */
	protected function getEntityRevisionsFromEntityIds( $entityIds ) {
		$revisionArray = array();

		foreach ( $entityIds as $entityId ) {
			$entityRevision = $this->entityLookup->getEntityRevision( $entityId );
			if ( is_null( $entityRevision ) ) {
				$this->getResultBuilder()->addMissingEntity( array( 'id' => $entityId->getSerialization() ) );
			} else {
				$revisionArray[] = $entityRevision;
			}
		}
		return $revisionArray;
	}

	/**
	 * Fetches the entity with provided id and adds its serialization to the output.
	 *
	 * @since 0.2
	 *
	 * @param EntityRevision $entityRevision
	 * @param array $params
	 */
	protected function handleEntity( EntityRevision $entityRevision, array $params ) {
		wfProfileIn( __METHOD__ );
		$props = $this->getPropsFromParams( $params );
		$options = $this->getSerializationOptions( $params, $props );
		$siteFilterIds = $params['sitefilter'];
		$this->getResultBuilder()->addEntityRevision( $entityRevision, $options, $props, $siteFilterIds );
		wfProfileOut( __METHOD__ );
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
					->newFromContextAndLanguageCode( $this->getContext(), $languageCode );
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
		$options->setOption( EntitySerializer::OPT_SORT_ORDER, $params['dir'] );
		$options->setOption( EntitySerializer::OPT_PARTS, $props );
		$options->setIndexTags( $this->getResult()->getIsRawMode() );
		return $options;
	}

	/**
	 * @see ApiBase::getAllowedParams()
	 */
	public function getAllowedParams() {
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
			'props' => array(
				ApiBase::PARAM_TYPE => array( 'info', 'sitelinks', 'sitelinks/urls', 'aliases', 'labels',
					'descriptions', 'claims', 'datatype' ),
				ApiBase::PARAM_DFLT => 'info|sitelinks|aliases|labels|descriptions|claims|datatype',
				ApiBase::PARAM_ISMULTI => true,
			),
			'sort' => array(
				// This could be done like the urls, where sitelinks/title sort on the title field
				// and sitelinks/site sort on the site code.
				ApiBase::PARAM_TYPE => array( 'sitelinks' ),
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'dir' => array(
				ApiBase::PARAM_TYPE => array(
					EntitySerializer::SORT_ASC,
					EntitySerializer::SORT_DESC,
					EntitySerializer::SORT_NONE
				),
				ApiBase::PARAM_DFLT => EntitySerializer::SORT_ASC,
				ApiBase::PARAM_ISMULTI => false,
			),
			'languages' => array(
				ApiBase::PARAM_TYPE => Utils::getLanguageCodes(),
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
	 * @see ApiBase::getParamDescription()
	 */
	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'ids' => 'The IDs of the entities to get the data from',
			'sites' => array( 'Identifier for the site on which the corresponding page resides',
				"Use together with 'title', but only give one site for several titles or several sites for one title."
			),
			'titles' => array( 'The title of the corresponding page',
				"Use together with 'sites', but only give one site for several titles or several sites for one title."
			),
			'props' => array( 'The names of the properties to get back from each entity.',
				"Will be further filtered by any languages given."
			),
			'sort' => array( 'The names of the properties to sort.',
				"Use together with 'dir' to give the sort order.",
				"Note that this will change due to name clash (ie. sort should work on all entities)."
			),
			'dir' => array( 'The sort order for the given properties.',
				"Use together with 'sort' to give the properties to sort.",
				"Note that this will change due to name clash (ie. dir should work on all entities)."
			),
			'languages' => array( 'By default the internationalized values are returned in all available languages.',
				'This parameter allows filtering these down to one or more languages by providing one or more language codes.'
			),
			'languagefallback' => array( 'Apply language fallback for languages defined in the "languages" parameter,',
				'with the current context of API call.'
			),
			'normalize' => array( 'Try to normalize the page title against the client site.',
				'This only works if exactly one site and one page have been given.'
			),
			'ungroupedlist' => array( 'Do not group snaks by property id.' ),
			'sitefilter' => array( 'Filter sitelinks in entities to those with these siteids.' ),
		) );
	}

	/**
	 * @see ApiBase::getDescription()
	 */
	public function getDescription() {
		return array(
			'API module to get the data for multiple Wikibase entities.'
		);
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'param-missing', 'info' => $this->msg( 'wikibase-api-param-missing' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->msg( 'wikibase-api-no-such-entity' )->text() ),
			array(
				'code' => 'normalize-only-once',
				'info' => 'Normalize is only allowed if exactly one site and one page have been given'
			),
		) );
	}

	/**
	 * @see ApiBase::getExamples()
	 */
	protected function getExamples() {
		return array(
			"api.php?action=wbgetentities&ids=Q42"
			=> "Get entities with ID Q42 with all available attributes in all available languages",
			"api.php?action=wbgetentities&ids=P17"
			=> "Get entities with ID P17 with all available attributes in all available languages",
			"api.php?action=wbgetentities&ids=Q42|P17"
			=> "Get entities with IDs Q42 and P17 with all available attributes in all available languages",
			"api.php?action=wbgetentities&ids=Q42&languages=en"
			=> "Get entities with ID Q42 with all available attributes in English language",
			"api.php?action=wbgetentities&ids=Q42&languages=ii&languagefallback="
			=> "Get entities with ID Q42 with all available attributes in any possible fallback language for the ii language",
			"api.php?action=wbgetentities&ids=Q42&props=labels"
			=> "Get entities with ID Q42 showing all labels in all available languages",
			"api.php?action=wbgetentities&ids=P17|P3&props=datatype"
			=> "Get entities with IDs P17 and P3 showing only datatypes",
			"api.php?action=wbgetentities&ids=Q42&props=aliases&languages=en"
			=> "Get entities with ID Q42 showing all aliases in English language",
			"api.php?action=wbgetentities&ids=Q1|Q42&props=descriptions&languages=en|de|fr"
			=> "Get entities with IDs Q1 and Q42 showing descriptions in English, German and French languages",
			'api.php?action=wbgetentities&sites=enwiki&titles=Berlin&languages=en'
			=> 'Get the item for page "Berlin" on the site "enwiki", with language attributes in English language',
			'api.php?action=wbgetentities&sites=enwiki&titles=berlin&normalize='
			=> 'Get the item for page "Berlin" on the site "enwiki" after normalizing the title from "berlin"',
			'api.php?action=wbgetentities&ids=Q42&props=sitelinks&sort&dir=descending'
			=> 'Get the sitelinks for item Q42 sorted in a descending order"',
			'api.php?action=wbgetentities&ids=Q42&sitefilter=enwiki'
			=> 'Get entities with ID Q42 showing only sitelinks from enwiki'
		);
	}

}
