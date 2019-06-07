<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Formatters\LatLongFormatter;
use InvalidArgumentException;
use Language;
use Psr\SimpleCache\CacheInterface;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\QuantityHtmlFormatter;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\CachingFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikimedia\Assert\Assert;

/**
 * Low level factory for ValueFormatters for well known data types.
 *
 * @warning: This is a low level factory for use by boostrap code only!
 * Program logic should use an instance of OutputFormatValueFormatterFactory
 * resp. OutputFormatSnakFormatterFactory.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseValueFormatterBuilders {

	/**
	 * @var Language
	 */
	private $defaultLanguage;

	/**
	 * @var FormatterLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var LanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @var EntityIdParser
	 */
	private $repoItemUriParser;

	/**
	 * @var string
	 */
	private $geoShapeStorageBaseUrl;

	/**
	 * @var EntityTitleLookup|null
	 */
	private $entityTitleLookup;

	/**
	 * Unit URIs that represent "unitless" or "one".
	 *
	 * @todo make this configurable
	 *
	 * @var string[]
	 */
	private $unitOneUris = [
		'http://www.wikidata.org/entity/Q199',
		'http://qudt.org/vocab/unit#Unitless',
	];

	/**
	 * @var string
	 */
	private $tabularDataStorageBaseUrl;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var SnakFormat
	 */
	private $snakFormat;

	/**
	 * @var CachingKartographerEmbeddingHandler|null
	 */
	private $kartographerEmbeddingHandler;

	/**
	 * @var int
	 */
	private $cacheTtlInSeconds;

	/**
	 * @var bool
	 */
	private $useKartographerMaplinkInWikitext;

	/**
	 * @param Language $defaultLanguage
	 * @param FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param EntityIdParser $repoItemUriParser
	 * @param string $geoShapeStorageBaseUrl
	 * @param string $tabularDataStorageBaseUrl
	 * @param CacheInterface $formatterCache
	 * @param int $cacheTtlInSeconds
	 * @param EntityLookup $entityLookup
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param EntityTitleLookup|null $entityTitleLookup
	 * @param CachingKartographerEmbeddingHandler|null $kartographerEmbeddingHandler
	 * @param bool $useKartographerMaplinkInWikitext
	 */
	public function __construct(
		Language $defaultLanguage,
		FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		LanguageNameLookup $languageNameLookup,
		EntityIdParser $repoItemUriParser,
		$geoShapeStorageBaseUrl,
		$tabularDataStorageBaseUrl,
		CacheInterface $formatterCache,
		$cacheTtlInSeconds,
		EntityLookup $entityLookup,
		EntityRevisionLookup $entityRevisionLookup,
		EntityTitleLookup $entityTitleLookup = null,
		CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler = null,
		$useKartographerMaplinkInWikitext = false
	) {
		Assert::parameterType(
			'string',
			$geoShapeStorageBaseUrl,
			'$geoShapeStorageBaseUrl'
		);

		Assert::parameterType(
			'string',
			$tabularDataStorageBaseUrl,
			'$tabularDataStorageBaseUrl'
		);

		Assert::parameterType(
			'integer',
			$cacheTtlInSeconds,
			'$cacheTtlInSeconds'
		);

		Assert::parameter(
			$cacheTtlInSeconds >= 0,
			'$cacheTtlInSeconds',
			"should be non-negative"
		);

		$this->defaultLanguage = $defaultLanguage;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->repoItemUriParser = $repoItemUriParser;
		$this->geoShapeStorageBaseUrl = $geoShapeStorageBaseUrl;
		$this->tabularDataStorageBaseUrl = $tabularDataStorageBaseUrl;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityLookup = $entityLookup;
		$this->cache = $formatterCache;
		$this->snakFormat = new SnakFormat();
		$this->cacheTtlInSeconds = $cacheTtlInSeconds;
		$this->kartographerEmbeddingHandler = $kartographerEmbeddingHandler;
		$this->useKartographerMaplinkInWikitext = $useKartographerMaplinkInWikitext;
	}

	private function newPlainEntityIdFormatter( FormatterOptions $options ) {
		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
		return new EntityIdValueFormatter(
			new EntityIdLabelFormatter( $labelDescriptionLookup )
		);
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @throws InvalidArgumentException
	 * @return bool True if $format is one of the SnakFormatter::FORMAT_HTML_XXX formats.
	 */
	private function isHtmlFormat( $format ) {
		return $this->snakFormat->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML;
	}

	/**
	 * Wraps the given formatter in an EscapingValueFormatter if necessary.
	 *
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param ValueFormatter $formatter The plain text formatter to wrap.
	 *
	 * @return ValueFormatter
	 */
	private function escapeValueFormatter( $format, ValueFormatter $formatter ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new EscapingValueFormatter( $formatter, 'htmlspecialchars' );
			case SnakFormatter::FORMAT_WIKI:
				return new EscapingValueFormatter( $formatter, 'wfEscapeWikiText' );
			default:
				return $formatter;
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newEntityIdFormatter( $format, FormatterOptions $options ) {
		if ( $this->isHtmlFormat( $format ) && $this->entityTitleLookup ) {
			return new EntityIdValueFormatter(
				$this->newLabelsProviderEntityIdHtmlLinkFormatter( $options )
			);
		} elseif ( $format === SnakFormatter::FORMAT_WIKI && $this->entityTitleLookup ) {
			return new EntityIdValueFormatter(
				new EntityIdSiteLinkFormatter(
					$this->entityTitleLookup,
					$this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options )
				)
			);
		}

		$plainFormatter = $this->newPlainEntityIdFormatter( $options );
		return $this->escapeValueFormatter( $format, $plainFormatter );
	}

	public function newItemPropertyIdHtmlLinkFormatter( FormatterOptions $options ) {
		$nonCachingLookup = new LanguageFallbackLabelDescriptionLookup(
			new EntityRetrievingTermLookup( $this->entityLookup ),
			$options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN )
		);

		$labelDescriptionLookup = new CachingFallbackLabelDescriptionLookup(
			$this->cache,
			$this->entityRevisionLookup,
			$nonCachingLookup,
			$options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN ),
			$this->cacheTtlInSeconds
		);

		return new ItemPropertyIdHtmlLinkFormatter(
			$labelDescriptionLookup,
			$this->entityTitleLookup,
			$this->languageNameLookup
		);
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newStringFormatter( $format, FormatterOptions $options ) {
		return $this->escapeValueFormatter( $format, new StringFormatter( $options ) );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newUnDeserializableValueFormatter( $format, FormatterOptions $options ) {
		return $this->escapeValueFormatter( $format, new UnDeserializableValueFormatter( $options ) );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newUrlFormatter( $format, FormatterOptions $options ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new HtmlUrlFormatter( $options );
			case SnakFormatter::FORMAT_WIKI:
				// Use the string formatter without escaping!
				return new StringFormatter( $options );
			default:
				return $this->newStringFormatter( $format, $options );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newCommonsMediaFormatter( $format, FormatterOptions $options ) {
		if ( $this->snakFormat->isPossibleFormat( SnakFormatter::FORMAT_HTML_VERBOSE, $format ) ) {
			return new CommonsInlineImageFormatter( $options );
		}

		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new CommonsLinkFormatter( $options );
			case SnakFormatter::FORMAT_WIKI:
				return new CommonsThumbnailFormatter();
			default:
				return $this->newStringFormatter( $format, $options );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newGeoShapeFormatter( $format, FormatterOptions $options ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new InterWikiLinkHtmlFormatter( $this->geoShapeStorageBaseUrl );
			case SnakFormatter::FORMAT_WIKI:
				return new InterWikiLinkWikitextFormatter( $this->geoShapeStorageBaseUrl );
			default:
				return $this->newStringFormatter( $format, $options );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newTabularDataFormatter( $format, FormatterOptions $options ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new InterWikiLinkHtmlFormatter( $this->tabularDataStorageBaseUrl );
			case SnakFormatter::FORMAT_WIKI:
				return new InterWikiLinkWikitextFormatter( $this->tabularDataStorageBaseUrl );
			default:
				return $this->newStringFormatter( $format, $options );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newTimeFormatter( $format, FormatterOptions $options ) {
		// TODO: Add a wikitext formatter that shows the calendar model
		if ( $this->snakFormat->isPossibleFormat( SnakFormatter::FORMAT_HTML_DIFF, $format ) ) {
			return new TimeDetailsFormatter(
				$options,
				new HtmlTimeFormatter( $options, new MwTimeIsoFormatter( $options ) )
			);
		} elseif ( $this->isHtmlFormat( $format ) ) {
			return new HtmlTimeFormatter( $options, new MwTimeIsoFormatter( $options ) );
		} else {
			return $this->escapeValueFormatter( $format, new MwTimeIsoFormatter( $options ) );
		}
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @return MediaWikiNumberLocalizer
	 */
	private function getNumberLocalizer( FormatterOptions $options ) {
		$language = Language::factory( $options->getOption( ValueFormatter::OPT_LANG ) );
		return new MediaWikiNumberLocalizer( $language );
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @return VocabularyUriFormatter
	 */
	private function getVocabularyUriFormatter( FormatterOptions $options ) {
		$labelLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
		return new VocabularyUriFormatter( $this->repoItemUriParser, $labelLookup, $this->unitOneUris );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return QuantityFormatter
	 */
	public function newQuantityFormatter( $format, FormatterOptions $options ) {
		$vocabularyUriFormatter = $this->getVocabularyUriFormatter( $options );

		if ( $this->snakFormat->isPossibleFormat( SnakFormatter::FORMAT_HTML_DIFF, $format ) ) {
			$localizer = $this->getNumberLocalizer( $options );
			return new QuantityDetailsFormatter( $localizer, $vocabularyUriFormatter, $options );
		} elseif ( $this->isHtmlFormat( $format ) ) {
			$decimalFormatter = new DecimalFormatter( $options, $this->getNumberLocalizer( $options ) );
			return new QuantityHtmlFormatter( $options, $decimalFormatter, $vocabularyUriFormatter );
		} else {
			$decimalFormatter = new DecimalFormatter( $options, $this->getNumberLocalizer( $options ) );
			$plainFormatter = new QuantityFormatter( $options, $decimalFormatter, $vocabularyUriFormatter );
			return $this->escapeValueFormatter( $format, $plainFormatter );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newGlobeCoordinateFormatter( $format, FormatterOptions $options ) {
		$isHtmlVerboseFormat = $this->snakFormat->isPossibleFormat( SnakFormatter::FORMAT_HTML_VERBOSE, $format );

		if ( $isHtmlVerboseFormat && $this->kartographerEmbeddingHandler ) {
			$isPreview = $format === SnakFormatter::FORMAT_HTML_VERBOSE_PREVIEW;

			return new GlobeCoordinateKartographerFormatter(
				$options,
				$this->newGlobeCoordinateFormatter( SnakFormatter::FORMAT_HTML, $options ),
				$this->kartographerEmbeddingHandler,
				$isPreview
			);
		}

		if ( $this->snakFormat->isPossibleFormat( SnakFormatter::FORMAT_HTML_DIFF, $format ) ) {
			return new GlobeCoordinateDetailsFormatter(
				$this->getVocabularyUriFormatter( $options ),
				$options
			);
		}

		$options->setOption( LatLongFormatter::OPT_FORMAT, LatLongFormatter::TYPE_DMS );
		$options->setOption( LatLongFormatter::OPT_SPACING_LEVEL, [
			LatLongFormatter::OPT_SPACE_LATLONG
		] );
		$options->setOption( LatLongFormatter::OPT_DIRECTIONAL, true );

		$plainFormatter = new GlobeCoordinateFormatter( $options );

		if ( $format === SnakFormatter::FORMAT_WIKI && $this->useKartographerMaplinkInWikitext ) {
			return new GlobeCoordinateInlineWikitextKartographerFormatter( $plainFormatter );
		} else {
			return $this->escapeValueFormatter( $format, $plainFormatter );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @return MonolingualHtmlFormatter
	 */
	public function newMonolingualFormatter( $format ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new MonolingualHtmlFormatter( $this->languageNameLookup );
			case SnakFormatter::FORMAT_WIKI:
				return new MonolingualWikitextFormatter();
			default:
				return new MonolingualTextFormatter();
		}
	}

	public function newLabelsProviderEntityIdHtmlLinkFormatter( FormatterOptions $options ) {
		$lookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
		return new LabelsProviderEntityIdHtmlLinkFormatter(
			$lookup,
			$this->entityTitleLookup,
			$this->languageNameLookup
		);
	}

}
