<?php

namespace Wikibase\Repo\ParserOutput;

use GeoData\GeoData;
use Language;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @var EntityInfoBuilderFactory
	 */
	private $entityInfoBuilderFactory;

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
	 * @var EntityIdParser
	 */
	private $externalEntityIdParser;

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
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param EntityInfoBuilderFactory $entityInfoBuilderFactory
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param TemplateFactory $templateFactory
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param EntityIdParser $externalEntityIdParser
	 * @param Serializer $entitySerializer
	 * @param string[] $preferredGeoDataProperties
	 * @param string[] $preferredPageImagesProperties
	 * @param string[] $globeUris Mapping of globe URIs to canonical globe names, as recognized by
	 *  the GeoData extension.
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TemplateFactory $templateFactory,
		EntityDataFormatProvider $entityDataFormatProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityIdParser $externalEntityIdParser,
		Serializer $entitySerializer,
		array $preferredGeoDataProperties = array(),
		array $preferredPageImagesProperties = array(),
		array $globeUris = array()
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->templateFactory = $templateFactory;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->externalEntityIdParser = $externalEntityIdParser;
		$this->entitySerializer = $entitySerializer;
		$this->preferredGeoDataProperties = $preferredGeoDataProperties;
		$this->preferredPageImagesProperties = $preferredPageImagesProperties;
		$this->globeUris = $globeUris;
	}

	/**
	 * Creates an EntityParserOutputGenerator to create the ParserOutput for the entity
	 *
	 * @param string $userLanguageCode
	 * @param bool $editable
	 *
	 * @return EntityParserOutputGenerator
	 */
	public function getEntityParserOutputGenerator( $userLanguageCode, $editable ) {

		$userLanguage = Language::factory( $userLanguageCode );
		return new EntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->newParserOutputJsConfigBuilder(),
			$this->entityTitleLookup,
			$this->entityInfoBuilderFactory,
			$this->getLanguageFallbackChain( $userLanguage ),
			$this->templateFactory,
			new MediaWikiLocalizedTextProvider( $userLanguageCode ),
			$this->entityDataFormatProvider,
			$this->getDataUpdaters(),
			$userLanguageCode,
			$editable
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
	 * @return ParserOutputDataUpdater[]
	 */
	private function getDataUpdaters() {
		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->propertyDataTypeLookup );

		$updaters = array(
			new ReferencedEntitiesDataUpdater(
				$this->entityTitleLookup,
				$this->externalEntityIdParser
			),
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher )
		);

		if ( !empty( $this->preferredPageImagesProperties ) ) {
			$updaters[] = new PageImagesDataUpdater( $this->preferredPageImagesProperties );
		}

		if ( class_exists( GeoData::class ) ) {
			$updaters[] = new GeoDataDataUpdater(
				$propertyDataTypeMatcher,
				$this->preferredGeoDataProperties,
				$this->globeUris
			);
		}

		return $updaters;
	}

}
