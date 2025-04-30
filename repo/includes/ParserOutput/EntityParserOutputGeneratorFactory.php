<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Cache\LinkBatchFactory;
use MediaWiki\Extension\Math\MathDataUpdater;
use MediaWiki\FileRepo\RepoGroup;
use MediaWiki\Language\Language;
use MediaWiki\Registration\ExtensionRegistry;
use PageImages\PageImages;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesUiEntityParserOutputGeneratorDecorator;
use Wikibase\Repo\Hooks\WikibaseRepoHookRunner;
use Wikibase\Repo\Hooks\WikibaseRepoOnParserOutputUpdaterConstructionHook;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Stats\StatsFactory;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactory {

	private DispatchingEntityViewFactory $entityViewFactory;
	private DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory;
	private EntityTitleLookup $entityTitleLookup;
	private LanguageFallbackChainFactory $languageFallbackChainFactory;
	private EntityDataFormatProvider $entityDataFormatProvider;
	private PropertyDataTypeLookup $propertyDataTypeLookup;

	/**
	 * @var string[]
	 */
	private $preferredGeoDataProperties;

	/**
	 * @var string[]
	 */
	private $preferredPageImagesProperties;

	/**
	 * @var string[] Mapping of globe URIs to canonical globe names, as recognized by the GeoData
	 *  extension.
	 */
	private $globeUris;

	private EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator;
	private ?CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler;
	private StatsFactory $statsFactory;
	private RepoGroup $repoGroup;
	private LinkBatchFactory $linkBatchFactory;
	private WikibaseRepoOnParserOutputUpdaterConstructionHook $hookRunner;
	private bool $isMobileView;

	/**
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator
	 * @param CachingKartographerEmbeddingHandler|null $kartographerEmbeddingHandler
	 * @param StatsFactory $statsFactory
	 * @param RepoGroup $repoGroup
	 * @param LinkBatchFactory $linkBatchFactory
	 * @param WikibaseRepoHookRunner $hookRunner
	 * @param bool $isMobileView
	 * @param string[] $preferredGeoDataProperties
	 * @param string[] $preferredPageImagesProperties
	 * @param string[] $globeUris Mapping of globe URIs to canonical globe names, as recognized by
	 *  the GeoData extension.
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory,
		EntityTitleLookup $entityTitleLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		EntityDataFormatProvider $entityDataFormatProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator,
		?CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler,
		StatsFactory $statsFactory,
		RepoGroup $repoGroup,
		LinkBatchFactory $linkBatchFactory,
		WikibaseRepoOnParserOutputUpdaterConstructionHook $hookRunner,
		bool $isMobileView,
		array $preferredGeoDataProperties = [],
		array $preferredPageImagesProperties = [],
		array $globeUris = []
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityMetaTagsCreatorFactory = $entityMetaTagsCreatorFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entityReferenceExtractorDelegator = $entityReferenceExtractorDelegator;
		$this->kartographerEmbeddingHandler = $kartographerEmbeddingHandler;
		$this->statsFactory = $statsFactory->withComponent( 'WikibaseRepo' );
		$this->repoGroup = $repoGroup;
		$this->linkBatchFactory = $linkBatchFactory;
		$this->hookRunner = $hookRunner;
		$this->preferredGeoDataProperties = $preferredGeoDataProperties;
		$this->preferredPageImagesProperties = $preferredPageImagesProperties;
		$this->globeUris = $globeUris;
		$this->isMobileView = $isMobileView;
	}

	public function getEntityParserOutputGenerator( Language $userLanguage ): EntityParserOutputGenerator {
		$pog = new FullEntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->entityMetaTagsCreatorFactory,
			new ParserOutputJsConfigBuilder(),
			$this->getLanguageFallbackChain( $userLanguage ),
			$this->entityDataFormatProvider,
			$this->getDataUpdaters(),
			$userLanguage,
			$this->isMobileView
		);

		$pog = new StatslibTimeRecordingEntityParserOutputGenerator(
			$pog,
			$this->statsFactory,
			'wikibase.repo.ParserOutputGenerator.timing',
			'ParserOutputGenerator'
		);

		if ( WikibaseRepo::getSettings()->getSetting( 'federatedPropertiesEnabled' ) ) {
			$pog = new FederatedPropertiesUiEntityParserOutputGeneratorDecorator(
				new FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator(
					$pog,
					WikibaseRepo::getFederatedPropertiesServiceFactory()->getApiEntityLookup()
				),
				$userLanguage
			);
		}

		return $pog;
	}

	private function getLanguageFallbackChain( Language $language ): TermLanguageFallbackChain {
		// Language fallback must depend ONLY on the target language,
		// so we don't confuse the parser cache with user specific HTML.
		return $this->languageFallbackChainFactory->newFromLanguage(
			$language
		);
	}

	/**
	 * @return EntityParserOutputUpdater[]
	 */
	private function getDataUpdaters(): array {
		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->propertyDataTypeLookup );

		$statementUpdater = new CompositeStatementDataUpdater(
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher, $this->repoGroup )
		);

		if ( $this->preferredPageImagesProperties
			&& ExtensionRegistry::getInstance()->isLoaded( 'PageImages' )
		) {
			$statementUpdater->addUpdater( $this->newPageImagesDataUpdater() );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'GeoData' ) ) {
			$statementUpdater->addUpdater( $this->newGeoDataDataUpdater( $propertyDataTypeMatcher ) );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Math' ) ) {
			$statementUpdater->addUpdater( new MathDataUpdater( $propertyDataTypeMatcher ) );
		}

		if ( $this->kartographerEmbeddingHandler ) {
			$statementUpdater->addUpdater(
				$this->newKartographerDataUpdater( $this->kartographerEmbeddingHandler ) );
		}

		$entityUpdaters = [
			new ItemParserOutputUpdater(
				$statementUpdater
			),
			new PropertyParserOutputUpdater(
				$statementUpdater
			),
			new ReferencedEntitiesDataUpdater(
				$this->entityReferenceExtractorDelegator,
				$this->entityTitleLookup,
				$this->linkBatchFactory
			),
		];

		$this->hookRunner->onWikibaseRepoOnParserOutputUpdaterConstruction(
			$statementUpdater,
			$entityUpdaters
		);

		return $entityUpdaters;
	}

	private function newPageImagesDataUpdater(): StatementDataUpdater {
		return new PageImagesDataUpdater(
			$this->preferredPageImagesProperties,
			PageImages::PROP_NAME_FREE
		);
	}

	private function newGeoDataDataUpdater( PropertyDataTypeMatcher $propertyDataTypeMatcher ): StatementDataUpdater {
		return new GeoDataDataUpdater(
			$propertyDataTypeMatcher,
			$this->preferredGeoDataProperties,
			$this->globeUris
		);
	}

	private function newKartographerDataUpdater(
		CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler
	): StatementDataUpdater {
		return new GlobeCoordinateKartographerDataUpdater( $kartographerEmbeddingHandler );
	}

}
