<?php

namespace Wikibase\Repo\ParserOutput;

use Language;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\EntityView;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\ParserOutputJsConfigBuilder;
use Wikibase\ReferencedEntitiesFinder;
use Wikibase\Utils;

/**
 * Factory to create HtmlParserOutputGeneratorFactory objects.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class HtmlParserOutputGeneratorFactory {

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

	public function __construct(
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $propertyDataTypeLookup
	) {
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * Create an HtmlParserOutputGenerator object.
	 *
	 * @since 0.5
	 *
	 * @param Language $language
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param EntityView $entityView
	 * @return HtmlParserOutputGenerator
	 */
	public function createHtmlParserOutputGenerator( Language $language, LanguageFallbackChain $languageFallbackChain, EntityView $entityView ) {
		$configBuilder = $this->createConfigBuilder( $language->getCode() );
		$serializationOptions = $this->createSerializationOptions( $language->getCode(), $languageFallbackChain );

		return new HtmlParserOutputGenerator(
			$entityView,
			$configBuilder,
			$serializationOptions,
			$this->entityTitleLookup,
			$this->propertyDataTypeLookup
		);
	}

	private function createConfigBuilder( $languageCode ) {
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

	private function createSerializationOptions( $languageCode, LanguageFallbackChain $languageFallbackChain ) {
		$languageCodes = Utils::getLanguageCodes() + array( $languageCode => $languageFallbackChain );

		$options = new SerializationOptions();
		$options->setLanguages( $languageCodes );

		return $options;
	}

}
