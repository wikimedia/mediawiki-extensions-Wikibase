<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
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
class ConfigVarsParserOutputGenerator {

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

	public function assignToParserOutput( ParserOutput $pout, Entity $entity, LanguageFallbackChain $languageFallbackChain, $languageCode ) {
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$serializationOptions = $this->createSerializationOptions( $languageCode, $languageFallbackChain );
		$configVars = $this->createConfigBuilder( $languageCode )->build( $entity, $serializationOptions, $isExperimental );
		$pout->addJsConfigVars( $configVars );
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
