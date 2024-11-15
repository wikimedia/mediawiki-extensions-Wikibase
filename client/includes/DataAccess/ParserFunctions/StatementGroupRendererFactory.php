<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataAccess\ParserFunctions;

use MediaWiki\Language\Language;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookupFactory;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class StatementGroupRendererFactory {

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @var SnaksFinder
	 */
	private $snaksFinder;

	/**
	 * @var LanguageAwareRenderer[]
	 */
	private $languageAwareRenderers = [];

	/**
	 * @var RestrictedEntityLookupFactory
	 */
	private $restrictedEntityLookupFactory;

	/**
	 * @var DataAccessSnakFormatterFactory
	 */
	private $dataAccessSnakFormatterFactory;

	/**
	 * @var UsageAccumulatorFactory
	 */
	private $usageAccumulatorFactory;

	/**
	 * @var LanguageConverterFactory
	 */
	private $langConvFactory;

	/**
	 * @var LanguageFactory
	 */
	private $langFactory;

	/**
	 * @var bool
	 */
	private $allowDataAccessInUserLanguage;

	public function __construct(
		PropertyLabelResolver $propertyLabelResolver,
		SnaksFinder $snaksFinder,
		RestrictedEntityLookupFactory $restrictedEntityLookupFactory,
		DataAccessSnakFormatterFactory $dataAccessSnakFormatterFactory,
		UsageAccumulatorFactory $usageAccumulatorFactory,
		LanguageConverterFactory $langConvFactory,
		LanguageFactory $langFactory,
		bool $allowDataAccessInUserLanguage
	) {
		$this->propertyLabelResolver = $propertyLabelResolver;
		$this->snaksFinder = $snaksFinder;
		$this->restrictedEntityLookupFactory = $restrictedEntityLookupFactory;
		$this->dataAccessSnakFormatterFactory = $dataAccessSnakFormatterFactory;
		$this->usageAccumulatorFactory = $usageAccumulatorFactory;
		$this->langConvFactory = $langConvFactory;
		$this->langFactory = $langFactory;
		$this->allowDataAccessInUserLanguage = $allowDataAccessInUserLanguage;
	}

	/**
	 * @param Parser $parser
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 */
	public function newRendererFromParser(
		Parser $parser,
		string $type = DataAccessSnakFormatterFactory::TYPE_ESCAPED_PLAINTEXT
	): StatementGroupRenderer {
		$usageAccumulator = $this->usageAccumulatorFactory->newFromParser( $parser );

		if ( $this->allowDataAccessInUserLanguage ) {
			// Use the user's language.
			// Note: This splits the parser cache.
			$targetLanguage = $parser->getOptions()->getUserLangObj();
			return $this->newLanguageAwareRenderer(
				$type,
				$targetLanguage,
				$usageAccumulator,
				$parser,
				$parser->getOutput(),
				$parser->getTitle()
			);
		} elseif ( $this->useVariants( $parser ) ) {
			$variants = $this->langConvFactory->getLanguageConverter( $parser->getTargetLanguage() )->getVariants();
			return $this->newVariantsAwareRenderer(
				$type,
				$variants,
				$usageAccumulator,
				$parser,
				$parser->getOutput(),
				$parser->getTitle()
			);
		} else {
			$targetLanguage = $parser->getTargetLanguage();
			return $this->newLanguageAwareRenderer(
				$type,
				$targetLanguage,
				$usageAccumulator,
				$parser,
				$parser->getOutput(),
				$parser->getTitle()
			);
		}
	}

	/**
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 * @param Parser $parser
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 * @return LanguageAwareRenderer
	 */
	private function newLanguageAwareRenderer(
		string $type,
		Language $language,
		UsageAccumulator $usageAccumulator,
		Parser $parser,
		ParserOutput $parserOutput,
		Title $title
	): LanguageAwareRenderer {
		$snakFormatter = $this->dataAccessSnakFormatterFactory->newWikitextSnakFormatter(
			$language,
			$usageAccumulator,
			$type
		);

		$propertyIdResolver = new PropertyIdResolver(
			$this->restrictedEntityLookupFactory->getRestrictedEntityLookup( $parser ),
			$this->propertyLabelResolver,
			$usageAccumulator
		);

		$entityStatementsRenderer = new StatementTransclusionInteractor(
			$language,
			$propertyIdResolver,
			$this->snaksFinder,
			$snakFormatter,
			$this->restrictedEntityLookupFactory->getRestrictedEntityLookup( $parser ),
			$usageAccumulator
		);

		return new LanguageAwareRenderer(
			$language,
			$entityStatementsRenderer,
			$parserOutput,
			$title
		);
	}

	/**
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 * @param string $languageCode
	 * @param UsageAccumulator $usageAccumulator
	 * @param Parser $parser
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 * @return LanguageAwareRenderer
	 */
	private function getLanguageAwareRendererFromCode(
		string $type,
		string $languageCode,
		UsageAccumulator $usageAccumulator,
		Parser $parser,
		ParserOutput $parserOutput,
		Title $title
	): LanguageAwareRenderer {
		if ( !isset( $this->languageAwareRenderers[$languageCode] ) ) {
			$this->languageAwareRenderers[$languageCode] = $this->newLanguageAwareRenderer(
				$type,
				$this->langFactory->getLanguage( $languageCode ),
				$usageAccumulator,
				$parser,
				$parserOutput,
				$title
			);
		}

		return $this->languageAwareRenderers[$languageCode];
	}

	/**
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 * @param string[] $variants
	 * @param UsageAccumulator $usageAccumulator
	 * @param Parser $parser
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 *
	 * @return VariantsAwareRenderer
	 */
	private function newVariantsAwareRenderer(
		string $type,
		array $variants,
		UsageAccumulator $usageAccumulator,
		Parser $parser,
		ParserOutput $parserOutput,
		Title $title
	): VariantsAwareRenderer {
		$languageAwareRenderers = [];

		foreach ( $variants as $variant ) {
			$languageAwareRenderers[$variant] = $this->getLanguageAwareRendererFromCode(
				$type,
				$variant,
				$usageAccumulator,
				$parser,
				$parserOutput,
				$title
			);
		}

		return new VariantsAwareRenderer( $languageAwareRenderers, $variants );
	}

	/**
	 * Check whether variants are used in this parser run.
	 */
	private function isParserUsingVariants( Parser $parser ): bool {
		$parserOptions = $parser->getOptions();

		return $parser->getOutputType() === Parser::OT_HTML
			&& !$parserOptions->getInterfaceMessage()
			&& !$parserOptions->getDisableContentConversion();
	}

	private function useVariants( Parser $parser ): bool {
		return $this->isParserUsingVariants( $parser )
			&& $this->langConvFactory->getLanguageConverter( $parser->getTargetLanguage() )->hasVariants();
	}

}
