<?php

namespace Wikibase\Lib;

use DataValues\Geo\Formatters\LatLongFormatter;
use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use InvalidArgumentException;
use Language;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\QuantityHtmlFormatter;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\Formatters\MonolingualHtmlFormatter;
use Wikibase\Formatters\MonolingualTextFormatter;
use Wikibase\Lib\Formatters\CommonsThumbnailFormatter;
use Wikibase\Lib\Formatters\EntityIdSiteLinkFormatter;
use Wikibase\Lib\Formatters\InterWikiLinkHtmlFormatter;
use Wikibase\Lib\Formatters\InterWikiLinkWikitextFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikimedia\Assert\Assert;

/**
 * Low level factory for ValueFormatters for well known data types.
 *
 * @warning: This is a low level factory for use by boostrap code only!
 * Program logic should use an instance of OutputFormatValueFormatterFactory
 * resp. OutputFormatSnakFormatterFactory.
 *
 * @license GPL-2.0+
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
	 * @todo: make this configurable
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
	 * @param Language $defaultLanguage
	 * @param FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param EntityIdParser $repoItemUriParser
	 * @param string $geoShapeStorageBaseUrl
	 * @param string $tabularDataStorageBaseUrl
	 * @param EntityTitleLookup|null $entityTitleLookup
	 */
	public function __construct(
		Language $defaultLanguage,
		FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		LanguageNameLookup $languageNameLookup,
		EntityIdParser $repoItemUriParser,
		$geoShapeStorageBaseUrl,
		$tabularDataStorageBaseUrl,
		EntityTitleLookup $entityTitleLookup = null
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

		$this->defaultLanguage = $defaultLanguage;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->repoItemUriParser = $repoItemUriParser;
		$this->geoShapeStorageBaseUrl = $geoShapeStorageBaseUrl;
		$this->tabularDataStorageBaseUrl = $tabularDataStorageBaseUrl;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	private function newPlainEntityIdFormatter( FormatterOptions $options ) {
		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
		return new EntityIdValueFormatter(
			new EntityIdLabelFormatter( $labelDescriptionLookup )
		);
	}

	/**
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 *
	 * @throws InvalidArgumentException
	 * @return string Either SnakFormatter::FORMAT_HTML, ...WIKI or ...PLAIN.
	 */
	private function getBaseFormat( $format ) {
		switch ( $format ) {
			case SnakFormatter::FORMAT_HTML:
			case SnakFormatter::FORMAT_HTML_DIFF:
				return SnakFormatter::FORMAT_HTML;
			case SnakFormatter::FORMAT_WIKI:
			case SnakFormatter::FORMAT_PLAIN:
				return $format;
		}

		throw new InvalidArgumentException( 'Unsupported output format: ' . $format );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @throws InvalidArgumentException
	 * @return bool True if $format is one of the SnakFormatter::FORMAT_HTML_XXX formats.
	 */
	private function isHtmlFormat( $format ) {
		return $this->getBaseFormat( $format ) === SnakFormatter::FORMAT_HTML;
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
		switch ( $this->getBaseFormat( $format ) ) {
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
			$labelDescriptionLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );

			return new EntityIdValueFormatter(
				new EntityIdHtmlLinkFormatter(
					$labelDescriptionLookup,
					$this->entityTitleLookup,
					$this->languageNameLookup
				)
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
		switch ( $this->getBaseFormat( $format ) ) {
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
		switch ( $this->getBaseFormat( $format ) ) {
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
		switch ( $this->getBaseFormat( $format ) ) {
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
		switch ( $this->getBaseFormat( $format ) ) {
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
		if ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
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

		if ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
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
	 * @return GlobeCoordinateFormatter
	 */
	public function newGlobeCoordinateFormatter( $format, FormatterOptions $options ) {
		// TODO: Add a wikitext formatter that links to the geohack or it's proposed replacement,
		// see https://phabricator.wikimedia.org/T102960
		if ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
			return new GlobeCoordinateDetailsFormatter(
				$this->getVocabularyUriFormatter( $options ),
				$options
			);
		} else {
			$options->setOption( LatLongFormatter::OPT_FORMAT, LatLongFormatter::TYPE_DMS );
			$options->setOption( LatLongFormatter::OPT_SPACING_LEVEL, [
				LatLongFormatter::OPT_SPACE_LATLONG
			] );
			$options->setOption( LatLongFormatter::OPT_DIRECTIONAL, true );

			$plainFormatter = new GlobeCoordinateFormatter( $options );
			return $this->escapeValueFormatter( $format, $plainFormatter );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @return MonolingualHtmlFormatter
	 */
	public function newMonolingualFormatter( $format ) {
		// TODO: Add a wikitext formatter that shows the language name
		if ( $this->isHtmlFormat( $format ) ) {
			return new MonolingualHtmlFormatter( $this->languageNameLookup );
		} else {
			return $this->escapeValueFormatter( $format, new MonolingualTextFormatter() );
		}
	}

}
