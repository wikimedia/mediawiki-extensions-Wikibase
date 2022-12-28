<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataAccess\ParserFunctions;

use Language;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageFactory;
use Parser;
use ParserOutput;
use Title;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
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
	 * @var EntityLookup
	 */
	private $entityLookup;

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
		EntityLookup $entityLookup,
		DataAccessSnakFormatterFactory $dataAccessSnakFormatterFactory,
		UsageAccumulatorFactory $usageAccumulatorFactory,
		LanguageConverterFactory $langConvFactory,
		LanguageFactory $langFactory,
		bool $allowDataAccessInUserLanguage
	) {
		$this->propertyLabelResolver = $propertyLabelResolver;
		$this->snaksFinder = $snaksFinder;
		$this->entityLookup = $entityLookup;
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
		$usageAccumulator = $this->usageAccumulatorFactory->newFromParserOutput( $parser->getOutput() );

		if ( $this->allowDataAccessInUserLanguage ) {
			// Use the user's language.
			// Note: This splits the parser cache.
			$targetLanguage = $parser->getOptions()->getUserLangObj();
			return $this->newLanguageAwareRenderer(
				$type,
				$targetLanguage,
				$usageAccumulator,
				$parser->getOutput(),
				$parser->getTitle()
			);
		} elseif ( $this->useVariants( $parser ) ) {
			$variants = $this->langConvFactory->getLanguageConverter( $parser->getTargetLanguage() )->getVariants();
			return $this->newVariantsAwareRenderer(
				$type,
				$variants,
				$usageAccumulator,
				$parser->getOutput(),
				$parser->getTitle()
			);
		} else {
			$targetLanguage = $parser->getTargetLanguage();
			return $this->newLanguageAwareRenderer(
				$type,
				$targetLanguage,
				$usageAccumulator,
				$parser->getOutput(),
				$parser->getTitle()
			);
		}
	}

	/**
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 * @param Language $language
	 * @param UsageAccumulator $usageAccumulator
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 */
	private function newLanguageAwareRenderer(
		string $type,
		Language $language,
		UsageAccumulator $usageAccumulator,
		ParserOutput $parserOutput,
		Title $title
	): LanguageAwareRenderer {
		$snakFormatter = $this->dataAccessSnakFormatterFactory->newWikitextSnakFormatter(
			$language,
			$usageAccumulator,
			$type
		);

		$propertyIdResolver = new PropertyIdResolver(
			$this->entityLookup,
			$this->propertyLabelResolver,
			$usageAccumulator
		);

		$entityStatementsRenderer = new StatementTransclusionInteractor(
			$language,
			$propertyIdResolver,
			$this->snaksFinder,
			$snakFormatter,
			$this->entityLookup,
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
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 */
	private function getLanguageAwareRendererFromCode(
		string $type,
		string $languageCode,
		UsageAccumulator $usageAccumulator,
		ParserOutput $parserOutput,
		Title $title
	): LanguageAwareRenderer {
		if ( !isset( $this->languageAwareRenderers[$languageCode] ) ) {
			$this->languageAwareRenderers[$languageCode] = $this->newLanguageAwareRenderer(
				$type,
				$this->langFactory->getLanguage( $languageCode ),
				$usageAccumulator,
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
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 *
	 * @return VariantsAwareRenderer
	 */
	private function newVariantsAwareRenderer(
		string $type,
		array $variants,
		UsageAccumulator $usageAccumulator,
		ParserOutput $parserOutput,
		Title $title
	): VariantsAwareRenderer {
		$languageAwareRenderers = [];

		foreach ( $variants as $variant ) {
			$languageAwareRenderers[$variant] = $this->getLanguageAwareRendererFromCode(
				$type,
				$variant,
				$usageAccumulator,
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
