<?php

namespace Wikibase\Client\DataAccess\ParserFunctions;

use Language;
use Parser;
use ParserOutput;
use Title;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulator;
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
	 * @var EntityUsageFactory
	 */
	private $entityUsageFactory;

	/**
	 * @var bool
	 */
	private $allowDataAccessInUserLanguage;

	/**
	 * @param PropertyLabelResolver $propertyLabelResolver
	 * @param SnaksFinder $snaksFinder
	 * @param EntityLookup $entityLookup
	 * @param DataAccessSnakFormatterFactory $dataAccessSnakFormatterFactory
	 * @param EntityUsageFactory $entityUsageFactory
	 * @param bool $allowDataAccessInUserLanguage
	 */
	public function __construct(
		PropertyLabelResolver $propertyLabelResolver,
		SnaksFinder $snaksFinder,
		EntityLookup $entityLookup,
		DataAccessSnakFormatterFactory $dataAccessSnakFormatterFactory,
		EntityUsageFactory $entityUsageFactory,
		$allowDataAccessInUserLanguage
	) {
		$this->propertyLabelResolver = $propertyLabelResolver;
		$this->snaksFinder = $snaksFinder;
		$this->entityLookup = $entityLookup;
		$this->dataAccessSnakFormatterFactory = $dataAccessSnakFormatterFactory;
		$this->entityUsageFactory = $entityUsageFactory;
		$this->allowDataAccessInUserLanguage = $allowDataAccessInUserLanguage;
	}

	/**
	 * @param Parser $parser
	 * @param string $type One of DataAccessSnakFormatterFactory::TYPE_*
	 *
	 * @return StatementGroupRenderer
	 */
	public function newRendererFromParser( Parser $parser, $type = DataAccessSnakFormatterFactory::TYPE_ESCAPED_PLAINTEXT ) {
		$usageAccumulator = new ParserOutputUsageAccumulator( $parser->getOutput(), $this->entityUsageFactory );

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
			$variants = $parser->getTargetLanguage()->getVariants();
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
	 *
	 * @return LanguageAwareRenderer
	 */
	private function newLanguageAwareRenderer(
		$type,
		Language $language,
		UsageAccumulator $usageAccumulator,
		ParserOutput $parserOutput,
		Title $title
	) {
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
	 *
	 * @return LanguageAwareRenderer
	 */
	private function getLanguageAwareRendererFromCode(
		$type,
		$languageCode,
		UsageAccumulator $usageAccumulator,
		ParserOutput $parserOutput,
		Title $title
	) {
		if ( !isset( $this->languageAwareRenderers[$languageCode] ) ) {
			$this->languageAwareRenderers[$languageCode] = $this->newLanguageAwareRenderer(
				$type,
				Language::factory( $languageCode ),
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
		$type,
		array $variants,
		UsageAccumulator $usageAccumulator,
		ParserOutput $parserOutput,
		Title $title
	) {
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
	 *
	 * @param Parser $parser
	 *
	 * @return bool
	 */
	private function isParserUsingVariants( Parser $parser ) {
		$parserOptions = $parser->getOptions();

		return $parser->getOutputType() === Parser::OT_HTML
			&& !$parserOptions->getInterfaceMessage()
			&& !$parserOptions->getDisableContentConversion();
	}

	/**
	 * @param Parser $parser
	 *
	 * @return bool
	 */
	private function useVariants( Parser $parser ) {
		return $this->isParserUsingVariants( $parser )
			&& $parser->getTargetLanguage()->hasVariants();
	}

}
