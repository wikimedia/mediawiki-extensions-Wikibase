<?php

namespace Wikibase;

use ParserOptions;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\DataValue\ValuesFinder;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;
use Wikibase\Repo\DataUpdates\EntityParserOutputDataUpdater;
use Wikibase\Repo\DataUpdates\ExternalLinksDataUpdate;
use Wikibase\Repo\DataUpdates\ImageLinksDataUpdate;
use Wikibase\Repo\DataUpdates\ReferencedEntitiesDataUpdate;
use Wikibase\Repo\DataUpdates\StatementDataUpdate;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\View\EntityViewFactory;
use Wikibase\View\Template\TemplateFactory;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactory {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EntityViewFactory
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

	public function __construct(
		EntityViewFactory $entityViewFactory,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		TemplateFactory $templateFactory,
		EntityDataFormatProvider $entityDataFormatProvider,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		EntityIdParser $externalEntityIdParser
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->templateFactory = $templateFactory;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->externalEntityIdParser = $externalEntityIdParser;
	}

	/**
	 * Creates an EntityParserOutputGenerator to create the ParserOutput for the entity
	 *
	 * @param ParserOptions $options
	 *
	 * @return EntityParserOutputGenerator
	 */
	public function getEntityParserOutputGenerator( ParserOptions $options ) {
		$languageCode = $options->getUserLang();

		return new EntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->newParserOutputJsConfigBuilder(),
			$this->entityTitleLookup,
			$this->entityInfoBuilderFactory,
			$this->getLanguageFallbackChain( $languageCode ),
			$this->templateFactory,
			$this->entityDataFormatProvider,
			new EntityParserOutputDataUpdater( $this->getDataUpdates() ),
			$languageCode
		);
	}

	/**
	 * @return ParserOutputJsConfigBuilder
	 */
	private function newParserOutputJsConfigBuilder() {
		return new ParserOutputJsConfigBuilder();
	}

	/**
	 * @param string $languageCode
	 *
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain( $languageCode ) {
		// Language fallback must depend ONLY on the target language,
		// so we don't confuse the parser cache with user specific HTML.
		return $this->languageFallbackChainFactory->newFromLanguageCode(
			$languageCode
		);
	}

	/**
	 * @return StatementDataUpdate[]
	 */
	private function getStatementDataUpdates() {
		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->propertyDataTypeLookup );

		return array(
			new ReferencedEntitiesDataUpdate(
				$this->entityTitleLookup,
				$this->externalEntityIdParser
			),
			new ExternalLinksDataUpdate( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdate( $propertyDataTypeMatcher )
		);
	}

}
