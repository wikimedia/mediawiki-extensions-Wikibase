<?php

namespace Wikibase;

use ParserOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
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
	 * @var ValuesFinder
	 */
	private $valuesFinder;

	/**
	 * @var ReferencedEntitiesFinder
	 */
	private $referencedEntitiesFinder;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	public function __construct(
		EntityViewFactory $entityViewFactory,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		ValuesFinder $valuesFinder,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		ReferencedEntitiesFinder $referencedEntitiesFinder,
		TemplateFactory $templateFactory
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->valuesFinder = $valuesFinder;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->referencedEntitiesFinder = $referencedEntitiesFinder;
		$this->templateFactory = $templateFactory;
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
			$this->newParserOutputJsConfigBuilder( $languageCode ),
			$this->entityTitleLookup,
			$this->valuesFinder,
			$this->entityInfoBuilderFactory,
			$this->getLanguageFallbackChain( $languageCode ),
			$languageCode,
			$this->referencedEntitiesFinder,
			$this->templateFactory
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return ParserOutputJsConfigBuilder
	 */
	private function newParserOutputJsConfigBuilder( $languageCode ) {
		return new ParserOutputJsConfigBuilder(
			$this->makeJsConfigSerializationOptions( $languageCode )
		);
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
	 * @param string $languageCode
	 *
	 * @return SerializationOptions
	 */
	private function makeJsConfigSerializationOptions( $languageCode ) {
		// NOTE: when serializing the full entity to be stored in the
		// wbEntity JS config variable, we currently do not want any
		// language fallback to be applied.

		$options = new SerializationOptions();
		$options->setOption( ValueFormatter::OPT_LANG, $languageCode );

		return $options;
	}

}
