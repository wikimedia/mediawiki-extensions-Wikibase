<?php

namespace Wikibase\Repo\ParserOutput;

use ExtensionRegistry;
use Hooks;
use Language;
use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use PageImages\PageImages;
use RepoGroup;
use Serializers\Serializer;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntityParserOutputGenerator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Template\TemplateFactory;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactory {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var DispatchingEntityViewFactory
	 */
	private $entityViewFactory;

	/**
	 * @var DispatchingEntityMetaTagsCreatorFactory
	 */
	private $entityMetaTagsCreatorFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

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

	/**
	 * @var EntityReferenceExtractorDelegator
	 */
	private $entityReferenceExtractorDelegator;

	/**
	 * @var CachingKartographerEmbeddingHandler|null
	 */
	private $kartographerEmbeddingHandler;

	/**
	 * @var StatsdDataFactoryInterface
	 */
	private $stats;

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param TemplateFactory $templateFactory
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param Serializer $entitySerializer
	 * @param EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator
	 * @param CachingKartographerEmbeddingHandler|null $kartographerEmbeddingHandler
	 * @param StatsdDataFactoryInterface $stats
	 * @param RepoGroup $repoGroup
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
		TemplateFactory $templateFactory,
		EntityDataFormatProvider $entityDataFormatProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		Serializer $entitySerializer,
		EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator,
		?CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler,
		StatsdDataFactoryInterface $stats,
		RepoGroup $repoGroup,
		array $preferredGeoDataProperties = [],
		array $preferredPageImagesProperties = [],
		array $globeUris = []
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityMetaTagsCreatorFactory = $entityMetaTagsCreatorFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->templateFactory = $templateFactory;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entitySerializer = $entitySerializer;
		$this->entityReferenceExtractorDelegator = $entityReferenceExtractorDelegator;
		$this->kartographerEmbeddingHandler = $kartographerEmbeddingHandler;
		$this->stats = $stats;
		$this->repoGroup = $repoGroup;
		$this->preferredGeoDataProperties = $preferredGeoDataProperties;
		$this->preferredPageImagesProperties = $preferredPageImagesProperties;
		$this->globeUris = $globeUris;
	}

	public function getEntityParserOutputGenerator( Language $userLanguage ): EntityParserOutputGenerator {
		$pog = new FullEntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->entityMetaTagsCreatorFactory,
			new ParserOutputJsConfigBuilder(),
			$this->entityTitleLookup,
			$this->getLanguageFallbackChain( $userLanguage ),
			$this->templateFactory,
			new MediaWikiLocalizedTextProvider( $userLanguage ),
			$this->entityDataFormatProvider,
			$this->getDataUpdaters(),
			$userLanguage
		);

		$pog = new StatsdTimeRecordingEntityParserOutputGenerator(
			$pog,
			$this->stats,
			'wikibase.repo.ParserOutputGenerator.timing'
		);

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		if ( $wikibaseRepo->getSettings()->getSetting( 'federatedPropertiesEnabled' ) ) {
			$pog = new FederatedPropertiesEntityParserOutputGenerator(
				$pog,
				$userLanguage,
				$wikibaseRepo->newFederatedPropertiesServiceFactory()->getApiEntityLookup()
			);
		}

		return $pog;
	}

	private function getLanguageFallbackChain( Language $language ): LanguageFallbackChain {
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

		if ( !empty( $this->preferredPageImagesProperties )
			&& ExtensionRegistry::getInstance()->isLoaded( 'PageImages' )
		) {
			$statementUpdater->addUpdater( $this->newPageImagesDataUpdater() );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'GeoData' ) ) {
			$statementUpdater->addUpdater( $this->newGeoDataDataUpdater( $propertyDataTypeMatcher ) );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Math' ) ) {
			$statementUpdater->addUpdater( new \MathDataUpdater( $propertyDataTypeMatcher ) );
		}

		// FIXME: null implementation of KartographerEmbeddingHandler would seem better than null pointer
		// in general, and would also remove the need for the check here
		if ( $this->kartographerEmbeddingHandler ) {
			$statementUpdater->addUpdater( $this->newKartographerDataUpdater() );
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
				$this->entityTitleLookup
			)
		];

		// TODO: do not use global state
		Hooks::run(
			'WikibaseRepoOnParserOutputUpdaterConstruction',
			[
				$statementUpdater,
				&$entityUpdaters
			]
		);

		return $entityUpdaters;
	}

	private function newPageImagesDataUpdater(): StatementDataUpdater {
		return new PageImagesDataUpdater(
			$this->preferredPageImagesProperties,
			PageImages::PROP_NAME_FREE
		);
	}

	private function newGeoDataDataUpdater( $propertyDataTypeMatcher ): StatementDataUpdater {
		return new GeoDataDataUpdater(
			$propertyDataTypeMatcher,
			$this->preferredGeoDataProperties,
			$this->globeUris
		);
	}

	private function newKartographerDataUpdater(): StatementDataUpdater {
		return new GlobeCoordinateKartographerDataUpdater(
			$this->kartographerEmbeddingHandler
		);
	}

}
