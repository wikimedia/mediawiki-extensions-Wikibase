<?php

namespace Wikibase;

use IContextSource;
use Language;
use ParserOptions;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;

/**
 * Factory to create EntityParserOutputGenerator objects.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutputGeneratorFactory {

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var EntityInfoBuilderFactory
	 */
	private $entityInfoBuilderFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var string
	 */
	private $entityViewClass;

	public function __construct(
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		$entityViewClass
	) {
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->entityViewClass = $entityViewClass;
	}

	/**
	 * Creates an EntityParserOutputGenerator to create the ParserOutput for the entity.
	 *
	 * @since 0.5
	 *
	 * @param IContextSource $context
	 * @param ParserOptions|null $options
	 *
	 * @return EntityParserOutputGenerator
	 */
	public function getEntityParserOutputGenerator( IContextSource $context, ParserOptions $options = null ) {
		if ( $options !== null ) {
			// Parser Options language overrides context language
			$context = clone $context;
			$languageCode = $options->getUserLang();
			$context->setLanguage( $languageCode );
		} else {
			$languageCode = $context->getLanguage()->getCode();
		}

		$languageFallbackChain = $this->languageFallbackChainFactory->newFromContextForPageView( $context );
		$snakFormatter = $this->makeSnakFormatter( $languageCode, $languageFallbackChain );
		$entityView = $this->makeEntityView( $context->getLanguage(), $snakFormatter );
		$configBuilder = $this->makeConfigBuilder( $languageCode );
		$serializationOptions = $this->makeSerializationOptions( $languageCode, $languageFallbackChain );

		return new EntityParserOutputGenerator(
			$entityView,
			$configBuilder,
			$serializationOptions,
			$this->entityTitleLookup,
			$this->propertyDataTypeLookup
		);
	}

	private function makeSnakFormatter( $languageCode, LanguageFallbackChain $languageFallbackChain ) {
		$formatterOptions = new FormatterOptions();

		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );
		$formatterOptions->setOption( 'languages', $languageFallbackChain );

		return $this->snakFormatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_WIDGET, $formatterOptions );
	}

	private function makeEntityView( Language $language, SnakFormatter $snakFormatter ) {
		$sectionEditLinkGenerator = new SectionEditLinkGenerator();

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$this->entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$this->entityTitleLookup
		);

		$claimsView =  new ClaimsView(
			$this->entityInfoBuilderFactory,
			$this->entityTitleLookup,
			$sectionEditLinkGenerator,
			$claimHtmlGenerator,
			$language->getCode()
		);

		$fingerprintView = new FingerprintView(
			$sectionEditLinkGenerator,
			$language->getCode()
		);

		return new $this->entityViewClass(
			$fingerprintView,
			$claimsView,
			$language
		);
	}

	private function makeConfigBuilder( $languageCode ) {
		$idParser = new BasicEntityIdParser();
		$referencedEntitiesFinder = new ReferencedEntitiesFinder();

		$configBuilder = new ParserOutputJsConfigBuilder(
			$this->entityInfoBuilderFactory,
			$idParser,
			$this->entityTitleLookup,
			$referencedEntitiesFinder,
			$languageCode
		);

		return $configBuilder;
	}

	private function makeSerializationOptions( $languageCode, LanguageFallbackChain $languageFallbackChain ) {
		$languageCodes = Utils::getLanguageCodes() + array( $languageCode => $languageFallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $languageCodes );

		return $options;
	}

}
