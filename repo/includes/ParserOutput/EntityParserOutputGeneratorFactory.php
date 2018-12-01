<?php

namespace Wikibase\Repo\ParserOutput;

use ExtensionRegistry;
use Language;
use PageImages;
use Serializers\Serializer;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
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
	 * @var EntityInfoBuilder
	 */
	private $entityInfoBuilder;

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
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param TemplateFactory $templateFactory
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param Serializer $entitySerializer
	 * @param EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator
	 * @param CachingKartographerEmbeddingHandler|null $kartographerEmbeddingHandler
	 * @param string[] $preferredGeoDataProperties
	 * @param string[] $preferredPageImagesProperties
	 * @param string[] $globeUris Mapping of globe URIs to canonical globe names, as recognized by
	 *  the GeoData extension.
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory,
		EntityInfoBuilder $entityInfoBuilder,
		EntityTitleLookup $entityTitleLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TemplateFactory $templateFactory,
		EntityDataFormatProvider $entityDataFormatProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		Serializer $entitySerializer,
		EntityReferenceExtractorDelegator $entityReferenceExtractorDelegator,
		CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler = null,
		array $preferredGeoDataProperties = [],
		array $preferredPageImagesProperties = [],
		array $globeUris = []
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityMetaTagsCreatorFactory = $entityMetaTagsCreatorFactory;
		$this->entityInfoBuilder = $entityInfoBuilder;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->templateFactory = $templateFactory;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entitySerializer = $entitySerializer;
		$this->preferredGeoDataProperties = $preferredGeoDataProperties;
		$this->preferredPageImagesProperties = $preferredPageImagesProperties;
		$this->globeUris = $globeUris;
		$this->entityReferenceExtractorDelegator = $entityReferenceExtractorDelegator;
		$this->kartographerEmbeddingHandler = $kartographerEmbeddingHandler;
	}

	/**
	 * Creates an EntityParserOutputGenerator to create the ParserOutput for the entity
	 *
	 * @param Language $userLanguage
	 *
	 * @return EntityParserOutputGenerator
	 */
	public function getEntityParserOutputGenerator( Language $userLanguage ) {
		return new EntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->entityMetaTagsCreatorFactory,
			$this->newParserOutputJsConfigBuilder(),
			$this->entityTitleLookup,
			$this->entityInfoBuilder,
			$this->getLanguageFallbackChain( $userLanguage ),
			$this->templateFactory,
			new MediaWikiLocalizedTextProvider( $userLanguage ),
			$this->entityDataFormatProvider,
			$this->getDataUpdaters(),
			$userLanguage
		);
	}

	/**
	 * @return ParserOutputJsConfigBuilder
	 */
	private function newParserOutputJsConfigBuilder() {
		return new ParserOutputJsConfigBuilder( $this->entitySerializer );
	}

	/**
	 * @param Language $language
	 *
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain( Language $language ) {
		// Language fallback must depend ONLY on the target language,
		// so we don't confuse the parser cache with user specific HTML.
		return $this->languageFallbackChainFactory->newFromLanguage(
			$language
		);
	}

	/**
	 * @return EntityParserOutputUpdater[]
	 */
	private function getDataUpdaters() {
		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->propertyDataTypeLookup );

		$statementUpdater = new CompositeStatementDataUpdater(
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher )
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

		return [
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
