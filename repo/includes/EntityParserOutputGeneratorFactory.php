<?php

namespace Wikibase;

use InvalidArgumentException;
use Language;
use ParserOptions;
use RequestContext;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
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
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactory {

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
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;

	/**
	 * @var ReferencedEntitiesFinder
	 */
	private $referencedEntitiesFinder;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	public function __construct(
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		EntityIdParser $entityIdParser,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		ReferencedEntitiesFinder $referencedEntitiesFinder
	) {
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityIdParser = $entityIdParser;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->referencedEntitiesFinder = $referencedEntitiesFinder;
		$this->sectionEditLinkGenerator = new SectionEditLinkGenerator();
	}

	/**
	 * Creates an EntityParserOutputGenerator to create the ParserOutput for the entity
	 *
	 * @param EntityRevision $entityRevision
	 * @param ParserOptions|null $options
	 *
	 * @return EntityParserOutputGenerator
	 */
	public function getEntityParserOutputGenerator( $entityType, ParserOptions $options = null ) {
		$languageCode = $this->getLanguageCode( $options );

		return new EntityParserOutputGenerator(
			$this->newEntityView( $entityType, $languageCode ),
			$this->newParserOutputJsConfigBuilder( $languageCode ),
			$this->makeSerializationOptions( $languageCode ),
			$this->entityTitleLookup,
			$this->propertyDataTypeLookup,
			$this->entityInfoBuilderFactory,
			$languageCode
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $languageCode ) {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $languageCode );

		// @fixme don't get fallback chain twice and it's also probably not needed here.
		$languageFallbackChain = $this->getLanguageFallbackChain( $languageCode );
		$formatterOptions->setOption( 'languages', $languageFallbackChain );

		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$formatterOptions
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return ParserOutputJsConfigBuilder
	 */
	private function newParserOutputJsConfigBuilder( $languageCode ) {
		return new ParserOutputJsConfigBuilder(
			$this->entityIdParser,
			$this->entityTitleLookup,
			$languageCode
		);
	}

	/**
	 * @param ParserOptions|null $options
	 *
	 * @return string
	 */
	private function getLanguageCode( ParserOptions $options = null ) {
		// NOTE: Parser Options language overrides context language!
		if ( $options !== null ) {
			$languageCode = $options->getUserLang();
		} else {
			// @todo do we still need to fallback to context here?
			// if needed, then maybe inject some 'default' in the constructor.
			$context = RequestContext::getMain();
			$languageCode = $context->getLanguage()->getCode();
		}

		return $languageCode;
	}

	/**
	 * @param string $languageCode
	 *
	 * @return ClaimsView
	 */
	private function newClaimsView( $languageCode ) {
		// @fixme SnakFormatterFactory needs to be injected into ClaimsView,
		// and also the entity info records via a TermLookup or such.
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$this->getSnakFormatter( $languageCode ),
			$this->entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$this->entityTitleLookup
		);

		return new ClaimsView(
			$this->entityInfoBuilderFactory,
			$this->entityTitleLookup,
			$this->sectionEditLinkGenerator,
			$claimHtmlGenerator,
			$languageCode
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return FingerprintView
	 */
	private function newFingerprintView( $languageCode ) {
		return new FingerprintView(
			$this->sectionEditLinkGenerator,
			$languageCode
		);
	}

	/**
	 * Creates an EntityView suitable for rendering the entity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param string $languageCode
	 *
	 * @return EntityView
	 */
	private function newEntityView( $entityType, $languageCode ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $languageCode );

		// @fixme all that seems needed in EntityView is language code and dir.
		$language = Language::factory( $languageCode );

		// @fixme support more entity types
		if ( $entityType === 'item' ) {
			return new ItemView( $fingerprintView, $claimsView, $language );
		} elseif ( $entityType === 'property' ) {
			return new PropertyView( $fingerprintView, $claimsView, $language );
		}

		throw new InvalidArgumentException( 'No EntityView for entity type: ' . $entityType );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return LanguageFallbackChain
	 */
	private function getLanguageFallbackChain( $languageCode ) {
		// @fixme inject User
		$context = RequestContext::getMain();

		return $this->languageFallbackChainFactory->newFromUserAndLanguageCodeForPageView(
			$context->getUser(),
			$languageCode
		);
	}

	/**
	 * @param string $languageCode
	 *
	 * @return SerializationOptions
	 */
	private function makeSerializationOptions( $languageCode ) {
		$fallbackChain = $this->getLanguageFallbackChain( $languageCode );
		$languageCodes = Utils::getLanguageCodes() + array( $languageCode => $fallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $languageCodes );

		return $options;
	}

}
