<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use ApiBase;
use ApiMain;
use IBufferingStatsdDataFactory;
use SiteLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\DivergingEntityIdException;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to get the data for one or more Wikibase entities.
 *
 * @license GPL-2.0-or-later
 */
class GetEntities extends ApiBase {

	use FederatedPropertyApiValidatorTrait;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var SiteLinkGlobalIdentifiersProvider
	 */
	private $siteLinkGlobalIdentifiersProvider;

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
	protected $errorReporter;

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

	/** @var SiteLookup */
	private $siteLookup;

	/** @var IBufferingStatsdDataFactory */
	private $stats;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param StringNormalizer $stringNormalizer
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider
	 * @param EntityPrefetcher $entityPrefetcher
	 * @param string[] $siteLinkGroups
	 * @param ApiErrorReporter $errorReporter
	 * @param ResultBuilder $resultBuilder
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityIdParser $idParser
	 * @param IBufferingStatsdDataFactory $stats
	 * @param bool $federatedPropertiesEnabled
	 *
	 * @see ApiBase::__construct
	 */
	public function __construct(
		ApiMain $mainModule,
		string $moduleName,
		StringNormalizer $stringNormalizer,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		EntityPrefetcher $entityPrefetcher,
		array $siteLinkGroups,
		ApiErrorReporter $errorReporter,
		ResultBuilder $resultBuilder,
		EntityRevisionLookup $entityRevisionLookup,
		EntityIdParser $idParser,
		SiteLookup $siteLookup,
		IBufferingStatsdDataFactory $stats,
		bool $federatedPropertiesEnabled
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->stringNormalizer = $stringNormalizer;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->siteLinkGlobalIdentifiersProvider = $siteLinkGlobalIdentifiersProvider;
		$this->entityPrefetcher = $entityPrefetcher;
		$this->siteLinkGroups = $siteLinkGroups;
		$this->errorReporter = $errorReporter;
		$this->resultBuilder = $resultBuilder;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->idParser = $idParser;
		$this->siteLookup = $siteLookup;
		$this->stats = $stats;
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
	}

	public static function factory(
		ApiMain $apiMain,
		string $moduleName,
		SiteLookup $siteLookup,
		IBufferingStatsdDataFactory $stats,
		ApiHelperFactory $apiHelperFactory,
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		SettingsArray $repoSettings,
		SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		Store $store,
		StringNormalizer $stringNormalizer
	): self {
		return new self(
			$apiMain,
			$moduleName,
			$stringNormalizer,
			$languageFallbackChainFactory,
			$siteLinkGlobalIdentifiersProvider,
			// TODO move EntityPrefetcher to service container and inject that directly
			$store->getEntityPrefetcher(),
			$repoSettings->getSetting( 'siteLinkGroups' ),
			$apiHelperFactory->getErrorReporter( $apiMain ),
			$apiHelperFactory->getResultBuilder( $apiMain ),
			$entityRevisionLookup,
			$entityIdParser,
			$siteLookup,
			$stats,
			$repoSettings->getSetting( 'federatedPropertiesEnabled' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(): void {
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
		foreach ( $entityIds as $entityId ) {
			$this->validateAlteringEntityById( $entityId );
		}

		$this->stats->updateCount( 'wikibase.repo.api.getentities.entities', count( $entityIds ) );

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
	private function getEntityIdsFromParams( array $params ): array {
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
	private function getEntityIdsFromIdParam( array $params ): array {
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
	private function getEntityIdsFromSiteTitleParams( array $params ): array {
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

	private function getItemByTitleHelper(): EntityByTitleHelper {
		// TODO inject Store/EntityByLinkedTitleLookup as services
		$siteLinkStore = WikibaseRepo::getStore()->getEntityByLinkedTitleLookup();
		return new EntityByTitleHelper(
			$this,
			$this->resultBuilder,
			$siteLinkStore,
			$this->siteLookup,
			$this->stringNormalizer
		);
	}

	/**
	 * @param array[] $missingItems Array of arrays, Each internal array has a key 'site' and 'title'
	 */
	private function addMissingItemsToResult( array $missingItems ): void {
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
	private function getPropsFromParams( array $params ): array {
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
	private function getEntityRevisionsFromEntityIds( array $entityIds, bool $resolveRedirects = false ): array {
		$revisionArray = [];

		$this->entityPrefetcher->prefetch( $entityIds );

		foreach ( $entityIds as $entityId ) {
			$sourceEntityId = $entityId->getSerialization();
			$entityRevision = $this->getEntityRevision( $entityId, $resolveRedirects );

			$revisionArray[$sourceEntityId] = $entityRevision;
		}

		return $revisionArray;
	}

	private function getEntityRevision( EntityId $entityId, bool $resolveRedirects = false ): ?EntityRevision {
		$entityRevision = null;

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			if ( $resolveRedirects ) {
				$entityId = $ex->getRedirectTargetId();
				$entityRevision = $this->getEntityRevision( $entityId, false );
			}
		} catch ( DivergingEntityIdException $ex ) {
			// DivergingEntityIdException is thrown when the repository $entityId is from other
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
		?string $sourceEntityId,
		EntityRevision $entityRevision = null,
		array $params = []
	): void {
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
	 *     1 => TermLanguageFallbackChain[] Keys are requested lang codes
	 */
	private function getLanguageCodesAndFallback( array $params ): array {
		$languageCodes = ( is_array( $params['languages'] ) ? $params['languages'] : [] );
		$fallbackChains = [];

		if ( $params['languagefallback'] ) {
			foreach ( $languageCodes as $languageCode ) {
				$fallbackChains[$languageCode] = $this->languageFallbackChainFactory
					->newFromLanguageCode( $languageCode );
			}
		}

		return [ array_unique( $languageCodes ), $fallbackChains ];
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		$siteIds = $this->siteLinkGlobalIdentifiersProvider->getList( $this->siteLinkGroups );

		return array_merge( parent::getAllowedParams(), [
			'ids' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
			],
			'sites' => [
				ParamValidator::PARAM_TYPE => $siteIds,
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_ALLOW_DUPLICATES => true,
			],
			'titles' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_ALLOW_DUPLICATES => true,
			],
			'redirects' => [
				ParamValidator::PARAM_TYPE => [ 'yes', 'no' ],
				ParamValidator::PARAM_DEFAULT => 'yes',
			],
			'props' => [
				ParamValidator::PARAM_TYPE => [ 'info', 'sitelinks', 'sitelinks/urls', 'aliases', 'labels',
					'descriptions', 'claims', 'datatype' ],
				ParamValidator::PARAM_DEFAULT => 'info|sitelinks|aliases|labels|descriptions|claims|datatype',
				ParamValidator::PARAM_ISMULTI => true,
			],
			'languages' => [
				// TODO inject TermsLanguages as a service
				ParamValidator::PARAM_TYPE => WikibaseRepo::getTermsLanguages()->getLanguages(),
				ParamValidator::PARAM_ISMULTI => true,
			],
			'languagefallback' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
			],
			'normalize' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_DEFAULT => false,
			],
			'sitefilter' => [
				ParamValidator::PARAM_TYPE => $siteIds,
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_ALLOW_DUPLICATES => true,
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages(): array {
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
			=> 'apihelp-wbgetentities-example-13',
		];
	}

}
