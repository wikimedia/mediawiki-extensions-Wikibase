<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Formatters\LatLongFormatter;
use InvalidArgumentException;
use MediaWiki\Languages\LanguageFactory;
use RequestContext;
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
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;

/**
 * Low level factory for ValueFormatters for well known data types.
 *
 * @warning: This is a low level factory for use by bootstrap code only!
 * Program logic should use an instance of OutputFormatValueFormatterFactory
 * resp. OutputFormatSnakFormatterFactory.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseValueFormatterBuilders {

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
	private $itemUriParser;

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
	 * @var RedirectResolvingLatestRevisionLookup
	 */
	private $redirectResolvingLatestRevisionLookup;

	/**
	 * @var TermFallbackCacheFacade
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
	 * @var bool
	 */
	private $useKartographerMaplinkInWikitext;

	/**
	 * @var int
	 */
	private $entitySchemaNamespace;

	/**
	 * @var array
	 */
	private $thumbLimits;

	/**
	 * @var EntityExistenceChecker
	 */
	private $entityExistenceChecker;

	/**
	 * @var EntityTitleTextLookup
	 */
	private $entityTitleTextLookup;

	/**
	 * @var EntityUrlLookup
	 */
	private $entityUrlLookup;

	/**
	 * @var EntityRedirectChecker
	 */
	private $entityRedirectChecker;

	/**
	 * @var LanguageFactory
	 */
	private $languageFactory;

	public function __construct(
		FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		LanguageNameLookup $languageNameLookup,
		EntityIdParser $itemUriParser,
		string $geoShapeStorageBaseUrl,
		string $tabularDataStorageBaseUrl,
		TermFallbackCacheFacade $termFallbackCacheFacade,
		EntityLookup $entityLookup,
		RedirectResolvingLatestRevisionLookup $redirectResolvingLatestRevisionLookup,
		int $entitySchemaNamespace,
		EntityExistenceChecker $entityExistenceChecker,
		EntityTitleTextLookup $entityTitleTextLookup,
		EntityUrlLookup $entityUrlLookup,
		EntityRedirectChecker $entityRedirectChecker,
		LanguageFactory $languageFactory,
		EntityTitleLookup $entityTitleLookup = null,
		CachingKartographerEmbeddingHandler $kartographerEmbeddingHandler = null,
		bool $useKartographerMaplinkInWikitext = false,
		array $thumbLimits = []
	) {
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->itemUriParser = $itemUriParser;
		$this->geoShapeStorageBaseUrl = $geoShapeStorageBaseUrl;
		$this->tabularDataStorageBaseUrl = $tabularDataStorageBaseUrl;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->redirectResolvingLatestRevisionLookup = $redirectResolvingLatestRevisionLookup;
		$this->entityLookup = $entityLookup;
		$this->cache = $termFallbackCacheFacade;
		$this->snakFormat = new SnakFormat();
		$this->kartographerEmbeddingHandler = $kartographerEmbeddingHandler;
		$this->useKartographerMaplinkInWikitext = $useKartographerMaplinkInWikitext;
		$this->entitySchemaNamespace = $entitySchemaNamespace;
		$this->thumbLimits = $thumbLimits;
		$this->entityExistenceChecker = $entityExistenceChecker;
		$this->entityTitleTextLookup = $entityTitleTextLookup;
		$this->entityUrlLookup = $entityUrlLookup;
		$this->entityRedirectChecker = $entityRedirectChecker;
		$this->languageFactory = $languageFactory;
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
				return new EscapingValueFormatter( $formatter, static function ( string $string ): string {
					return htmlspecialchars( $string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 );
				} );
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

	public function newPropertyIdHtmlLinkFormatter( FormatterOptions $options ) {
		return new ItemPropertyIdHtmlLinkFormatter(
			$this->getLabelDescriptionLookup( $options ),
			$this->entityTitleLookup,
			$this->languageNameLookup,
			new NonExistingEntityIdHtmlBrokenLinkFormatter(
				'wikibase-deletedentity-',
				$this->entityTitleTextLookup,
				$this->entityUrlLookup
			)
		);
	}

	public function newItemIdHtmlLinkFormatter( FormatterOptions $options ) {
		return new ItemPropertyIdHtmlLinkFormatter(
			$this->getLabelDescriptionLookup( $options ),
			$this->entityTitleLookup,
			$this->languageNameLookup,
			new NonExistingEntityIdHtmlFormatter( 'wikibase-deletedentity-' )
		);
	}

	private function getNonCachingLookup( FormatterOptions $options ) {
		return new LanguageFallbackLabelDescriptionLookup(
			new EntityRetrievingTermLookup( $this->entityLookup ),
			$options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN )
		);
	}

	private function getLabelDescriptionLookup( FormatterOptions $options ) {
		return new CachingFallbackLabelDescriptionLookup(
			$this->cache,
			$this->redirectResolvingLatestRevisionLookup,
			$this->getNonCachingLookup( $options ),
			$options->getOption( FormatterLabelDescriptionLookupFactory::OPT_LANGUAGE_FALLBACK_CHAIN )
		);
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @return ValueFormatter
	 */
	public function newStringFormatter( $format ) {
		return $this->escapeValueFormatter( $format, new StringFormatter() );
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
				return new StringFormatter();
			default:
				return $this->newStringFormatter( $format );
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
			return new CommonsInlineImageFormatter(
				RequestContext::getMain()->getOutput()->parserOptions(),
				$this->thumbLimits,
				$this->languageFactory,
				$options
			);
		}

		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new CommonsLinkFormatter( $options );
			case SnakFormatter::FORMAT_WIKI:
				return new CommonsThumbnailFormatter();
			default:
				return $this->newStringFormatter( $format );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @return ValueFormatter
	 */
	public function newGeoShapeFormatter( $format ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new InterWikiLinkHtmlFormatter( $this->geoShapeStorageBaseUrl );
			case SnakFormatter::FORMAT_WIKI:
				return new InterWikiLinkWikitextFormatter( $this->geoShapeStorageBaseUrl );
			default:
				return $this->newStringFormatter( $format );
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
				return $this->newStringFormatter( $format );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newEntitySchemaFormatter( $format, FormatterOptions $options ) {
		switch ( $this->snakFormat->getBaseFormat( $format ) ) {
			case SnakFormatter::FORMAT_HTML:
				return new WikiLinkHtmlFormatter( $this->entitySchemaNamespace );
			case SnakFormatter::FORMAT_WIKI:
				return new WikiLinkWikitextFormatter( $this->entitySchemaNamespace );
			default:
				return $this->newStringFormatter( $format );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
	 */
	public function newTimeFormatter( $format, FormatterOptions $options ) {
		if ( $this->snakFormat->isPossibleFormat( SnakFormatter::FORMAT_HTML_DIFF, $format ) ) {
			return new TimeDetailsFormatter(
				$options,
				new HtmlTimeFormatter(
					$options,
					new MwTimeIsoFormatter( $this->languageFactory, $options ),
					new ShowCalendarModelDecider()
				)
			);
		} elseif ( $this->isHtmlFormat( $format ) ) {
			return new HtmlTimeFormatter(
				$options,
				new MwTimeIsoFormatter( $this->languageFactory, $options ),
				new ShowCalendarModelDecider()
			);
		} else {
			return $this->escapeValueFormatter(
				$format,
				new PlaintextTimeFormatter(
					$options,
					new MwTimeIsoFormatter( $this->languageFactory, $options ),
					new ShowCalendarModelDecider()
				)
			);
		}
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @return MediaWikiNumberLocalizer
	 */
	private function getNumberLocalizer( FormatterOptions $options ) {
		$language = $this->languageFactory->getLanguage( $options->getOption( ValueFormatter::OPT_LANG ) );
		return new MediaWikiNumberLocalizer( $language );
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @return VocabularyUriFormatter
	 */
	private function getVocabularyUriFormatter( FormatterOptions $options ) {
		$labelLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
		return new VocabularyUriFormatter( $this->itemUriParser, $labelLookup, $this->unitOneUris );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter
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
				$this->languageFactory,
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
			LatLongFormatter::OPT_SPACE_LATLONG,
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
	 * @return MonolingualHtmlFormatter|MonolingualWikitextFormatter|MonolingualTextFormatter
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
			$this->languageNameLookup,
			$this->entityExistenceChecker,
			$this->entityTitleTextLookup,
			$this->entityUrlLookup,
			$this->entityRedirectChecker
		);
	}

}
