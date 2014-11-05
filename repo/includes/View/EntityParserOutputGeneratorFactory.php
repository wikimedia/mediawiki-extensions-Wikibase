<?php

namespace Wikibase;

use IContextSource;
use Language;
use ParserOptions;
use ParserOutput;
use RequestContext;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\SnakHtmlGenerator;
use Wikibase\Repo\WikibaseRepo;

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
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param ParserOptions|null $options
	 *
	 * @return EntityParserOutputGenerator
	 */
	public function getEntityParserOutputGenerator(
		EntityRevision $entityRevision,
		ParserOptions $options = null
	) {
		$languageCode = $this->getLanguageCode( $options );

		return $this->newEntityParserOutputGenerator( $entityRevision, $languageCode );
	}

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

	private function newParserOutputJsConfigBuilder( $languageCode ) {
		return new ParserOutputJsConfigBuilder(
			$this->entityInfoBuilderFactory,
			$this->entityIdParser,
			$this->entityTitleLookup,
			$this->referencedEntitiesFinder,
			$languageCode
		);
	}

	private function newEntityParserOutputGenerator(
		EntityRevision $entityRevision,
		$languageCode
	) {
		return new EntityParserOutputGenerator(
			$this->newEntityView( $entityRevision, $languageCode ),
			$this->newParserOutputJsConfigBuilder( $languageCode ),
			$this->makeSerializationOptions( $languageCode ),
			$this->entityTitleLookup,
			$this->propertyDataTypeLookup
		);
	}

	private function getLanguageCode( ParserOptions $options = null ) {
		$context = RequestContext::getMain();

		// NOTE: Parser Options language overrides context language!
		if ( $options !== null ) {
			$languageCode = $options->getUserLang();
		} else {
			$languageCode = $context->getLanguage()->getCode();
		}

		return $languageCode;
	}

	private function newClaimsView( $languageCode ) {
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
	private function newEntityView( EntityRevision $entityRevision, $languageCode ) {
		$fingerprintView = $this->newFingerprintView( $languageCode );
		$claimsView = $this->newClaimsView( $languageCode );
		$entityType = $entityRevision->getEntity()->getType();

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
