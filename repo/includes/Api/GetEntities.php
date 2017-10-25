<?php

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StringNormalizer;

/**
 * API module to get the data for one or more Wikibase entities.
 *
 * @license GPL-2.0+
 */
class GetEntities extends ApiBase {

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
	 * @var EntityPrefetcher
	 */
	private $entityPrefetcher;

	/**
	 * @var string[]
	 */
	private $siteLinkGroups;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var ResultBuilder
	 */
	private $resultBuilder;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param StringNormalizer $stringNormalizer
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param SiteLinkTargetProvider $siteLinkTargetProvider
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param string[] $siteLinkGroups
	 * @param ApiErrorReporter $errorReporter
	 * @param ResultBuilder $resultBuilder
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityIdParser $idParser
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		StringNormalizer $stringNormalizer,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		SiteLinkTargetProvider $siteLinkTargetProvider,
		EntityPrefetcher $entityPrefetcher,
		array $siteLinkGroups,
		ApiErrorReporter $errorReporter,
		ResultBuilder $resultBuilder,
		EntityRevisionLookup $entityRevisionLookup,
		EntityIdParser $idParser
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->stringNormalizer = $stringNormalizer;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->siteLinkTargetProvider = $siteLinkTargetProvider;
		$this->entityPrefetcher = $entityPrefetcher;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->errorReporter = $errorReporter;
		$this->resultBuilder = $resultBuilder;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->idParser = $idParser;
	}

	/**
	 * @see ApiBase::execute()
	 */
	public function execute() {
		$this->getMain()->setCacheMode( 'public' );

		$params = $this->extractRequestParams();

		if ( !isset( $params['ids'] ) && ( empty( $params['sites'] ) || empty( $params['titles'] ) ) ) {
			$this->errorReporter->dieWithError(
				'wikibase-api-illegal-ids-or-sites-titles-selector',
				'param-missing'
			);
		}

		$resolveRedirects = $params['redirects'] === 'yes';

		$entityIds = $this->getEntityIdsFromParams( $params );

		$stats = MediaWikiServices::getInstance()->getStatsdDataFactory();
		$stats->updateCount( 'wikibase.repo.api.getentities.entities', count( $entityIds ) );

		$entityRevisions = $this->getEntityRevisionsFromEntityIds( $entityIds, $resolveRedirects );

		foreach ( $entityRevisions as $sourceEntityId => $entityRevision ) {
			$this->handleEntity( $sourceEntityId, $entityRevision, $params );
		}

		$this->resultBuilder->markSuccess( 1 );
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
		$fromSiteTitleCombinations = $this->getEntityIdsFromSiteTitleParams( $params );
		$ids = array_merge( $fromIds, $fromSiteTitleCombinations );
		return array_unique( $ids );
	}

	/**
	 * @param array $params
	 *
	 * @return EntityId[]
	 */
	private function getEntityIdsFromIdParam( array $params ) {
		if ( !isset( $params['ids'] ) ) {
			return [];
		}

		$ids = [];
		foreach ( $params['ids'] as $id ) {
			try {
				$ids[] = $this->idParser->parse( $id );
			} catch ( EntityIdParsingException $e ) {
				$this->errorReporter->dieWithError(
					[ 'wikibase-api-no-such-entity', $id ],
					'no-such-entity',
					0,
					[ 'id' => $id ]
				);
			}
		}
		return $ids;
	}

	/**
	 * @param array $params
	 * @return EntityId[]
	 */
	private function getEntityIdsFromSiteTitleParams( array $params ) {
		$ids = [];
		if ( !empty( $params['sites'] ) && !empty( $params['titles'] ) ) {
			$entityByTitleHelper = $this->getItemByTitleHelper();

			list( $ids, $missingItems ) = $entityByTitleHelper->getEntityIds(
				$params['sites'],
				$params['titles'],
				$params['normalize']
			);

			$this->addMissingItemsToResult( $missingItems );
		}
		return $ids;
	}

	/**
	 * @return EntityByTitleHelper
	 */
	private function getItemByTitleHelper() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$siteLinkStore = $wikibaseRepo->getStore()->getEntityByLinkedTitleLookup();
		return new EntityByTitleHelper(
			$this,
			$this->resultBuilder,
			$siteLinkStore,
			$wikibaseRepo->getSiteLookup(),
			$this->stringNormalizer
		);
	}

	/**
	 * @param array[] $missingItems Array of arrays, Each internal array has a key 'site' and 'title'
	 */
	private function addMissingItemsToResult( array $missingItems ) {
		foreach ( $missingItems as $missingItem ) {
			$this->resultBuilder->addMissingEntity( null, $missingItem );
		}
	}

	/**
	 * Returns props based on request parameters
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	private function getPropsFromParams( array $params ) {
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
	private function getEntityRevisionsFromEntityIds( array $entityIds, $resolveRedirects = false ) {
		$revisionArray = [];

		$this->entityPrefetcher->prefetch( $entityIds );

		foreach ( $entityIds as $entityId ) {
			$sourceEntityId = $entityId->getSerialization();
			$entityRevision = $this->getEntityRevision( $entityId, $resolveRedirects );

			$revisionArray[$sourceEntityId] = $entityRevision;
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
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			if ( $resolveRedirects ) {
				$entityId = $ex->getRedirectTargetId();
				$entityRevision = $this->getEntityRevision( $entityId, false );
			}
		} catch ( InvalidArgumentException $ex ) {
			// InvalidArgumentException is thrown when the repository $entityId is from other
			// repository than the entityRevisionLookup was configured to read from.
			// Such cases are input errors (e.g. specifying non-existent repository prefix)
			// and should be ignored and treated as non-existing entities.
		}

		return $entityRevision;
	}

	/**
	 * Adds the given EntityRevision to the API result.
	 *
	 * @param string|null $sourceEntityId
	 * @param EntityRevision|null $entityRevision
	 * @param array $params
	 */
	private function handleEntity(
		$sourceEntityId,
		EntityRevision $entityRevision = null,
		array $params = []
	) {
		if ( $entityRevision === null ) {
			$this->resultBuilder->addMissingEntity( $sourceEntityId, [ 'id' => $sourceEntityId ] );
		} else {
			list( $languageCodeFilter, $fallbackChains ) = $this->getLanguageCodesAndFallback( $params );
			$this->resultBuilder->addEntityRevision(
				$sourceEntityId,
				$entityRevision,
				$this->getPropsFromParams( $params ),
				$params['sitefilter'],
				$languageCodeFilter,
				$fallbackChains
			);
		}
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 *     0 => string[] languageCodes that the user wants returned
	 *     1 => LanguageFallbackChain[] Keys are requested lang codes
	 */
	private function getLanguageCodesAndFallback( array $params ) {
		$languageCodes = ( is_array( $params['languages'] ) ? $params['languages'] : [] );
		$fallbackChains = [];

		if ( $params['languagefallback'] ) {
			$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL;
			foreach ( $languageCodes as $languageCode ) {
				$fallbackChains[$languageCode] = $this->languageFallbackChainFactory
					->newFromLanguageCode( $languageCode, $fallbackMode );
			}
		}

		return [ array_unique( $languageCodes ), $fallbackChains ];
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		$sites = $this->siteLinkTargetProvider->getSiteList( $this->siteLinkGroups );

		return array_merge( parent::getAllowedParams(), [
			'ids' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_ISMULTI => true,
			],
			'sites' => [
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
				self::PARAM_ISMULTI => true,
				self::PARAM_ALLOW_DUPLICATES => true
			],
			'titles' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_ISMULTI => true,
				self::PARAM_ALLOW_DUPLICATES => true
			],
			'redirects' => [
				self::PARAM_TYPE => [ 'yes', 'no' ],
				self::PARAM_DFLT => 'yes',
			],
			'props' => [
				self::PARAM_TYPE => [ 'info', 'sitelinks', 'sitelinks/urls', 'aliases', 'labels',
					'descriptions', 'claims', 'datatype' ],
				self::PARAM_DFLT => 'info|sitelinks|aliases|labels|descriptions|claims|datatype',
				self::PARAM_ISMULTI => true,
			],
			'languages' => [
				self::PARAM_TYPE => WikibaseRepo::getDefaultInstance()->getTermsLanguages()->getLanguages(),
				self::PARAM_ISMULTI => true,
			],
			'languagefallback' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false
			],
			'normalize' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false
			],
			'sitefilter' => [
				self::PARAM_TYPE => $sites->getGlobalIdentifiers(),
				self::PARAM_ISMULTI => true,
				self::PARAM_ALLOW_DUPLICATES => true
			],
		] );
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 */
	protected function getExamplesMessages() {
		return [
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
		];
	}

}
