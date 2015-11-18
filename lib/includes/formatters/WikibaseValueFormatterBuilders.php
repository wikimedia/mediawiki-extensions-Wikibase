<?php

namespace Wikibase\Lib;

use DataValues\Geo\Formatters\GeoCoordinateFormatter;
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
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Low level factory for ValueFormatters for well known data types.
 *
 * @warning: This is a low level factory for use by boostrap code only!
 * Program logic should use an instance of OutputFormatValueFormatterFactory
 * resp. OutputFormatSnakFormatterFactory.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
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
	private $repoUriParser;

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
	private $unitOneUris = array(
		'http://www.wikidata.org/entity/Q199',
		'http://qudt.org/vocab/unit#Unitless',
	);

	/**
	 * @param Language $defaultLanguage
	 * @param FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param LanguageNameLookup $languageNameLookup
	 * @param EntityIdParser $repoUriParser
	 * @param EntityTitleLookup|null $entityTitleLookup
	 */
	public function __construct(
		Language $defaultLanguage,
		FormatterLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		LanguageNameLookup $languageNameLookup,
		EntityIdParser $repoUriParser,
		EntityTitleLookup $entityTitleLookup = null
	) {
		$this->defaultLanguage = $defaultLanguage;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->languageNameLookup = $languageNameLookup;
		$this->repoUriParser = $repoUriParser;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * Returns an associative array mapping value types to factory functions.
	 * This is for use with OutputFormatValueFormatterFactory and DispatchingValueFormatter,
	 * to allow a fallback from data type based formatter selection to formatting based
	 * only on the value type.
	 *
	 * @todo make formatters for value types configurable using a mechanism similar to the
	 * one used for data types (see DataTypeDefinitions).
	 *
	 * @return callable[]
	 */
	public function getFormatterFactoryCallbacksByValueType() {
		$self = $this; // yay PHP 5.3!
		return array(
				'string' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->newStringFormatter( $format, $options );
				},

				'bad' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->escapeValueFormatter( $format, new UnDeserializableValueFormatter( $options ) );
				},

				'globecoordinate' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->newGlobeCoordinateFormatter( $format, $options );
				},

				'quantity' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->newQuantityFormatter( $format, $options );
				},

				'time' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->newTimeFormatter( $format, $options );
				},

				'wikibase-entityid' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->newEntityIdFormatter( $format, $options );
				},

				'monolingualtext' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $self->newMonolingualFormatter( $format, $options );
				},
		);
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
	 * @return bool True if $format is one of the SnakFormatter::FORMAT_HTML_XXX formats.
	 */
	private function isHtmlFormat( $format ) {
		return $format === SnakFormatter::FORMAT_HTML
			|| $format === SnakFormatter::FORMAT_HTML_DIFF
			|| $format === SnakFormatter::FORMAT_HTML_WIDGET;
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
		if ( $this->isHtmlFormat( $format ) ) {
			return new EscapingValueFormatter( $formatter, 'htmlspecialchars' );
		} elseif( $format === SnakFormatter::FORMAT_WIKI ) {
			return new EscapingValueFormatter( $formatter, 'wfEscapeWikiText' );
		} else if( $format === SnakFormatter::FORMAT_PLAIN ) {
			return $formatter;
		} else {
			throw new InvalidArgumentException( 'Unsupported output format: ' . $format );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
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
		}

		$plainFormatter = $this->newPlainEntityIdFormatter( $options );
		return $this->escapeValueFormatter( $format, $plainFormatter );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newStringFormatter( $format, FormatterOptions $options ) {
		return $this->escapeValueFormatter( $format, new StringFormatter( $options ) );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newUrlFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_WIKI ) {
			// use the string formatter without escaping!
			return new StringFormatter( $options );
		} elseif ( $this->isHtmlFormat( $format ) ) {
			return new HtmlUrlFormatter( $options );
		} else {
			return $this->escapeValueFormatter( $format, new StringFormatter( $options ) );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newCommonsMediaFormatter( $format, FormatterOptions $options ) {
		//TODO: for FORMAT_WIKI, wikitext image link (inline? thumbnail? caption?...)
		if ( $this->isHtmlFormat( $format ) ) {
			return new CommonsLinkFormatter( $options );
		} else {
			return $this->escapeValueFormatter( $format, new StringFormatter( $options ) );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newTimeFormatter( $format, FormatterOptions $options ) {
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
		return new VocabularyUriFormatter( $this->repoUriParser, $labelLookup, $this->unitOneUris );
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return QuantityFormatter|null
	 */
	public function newQuantityFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
			$localizer = $this->getNumberLocalizer( $options );
			$vocabularyUriFormatter = $this->getVocabularyUriFormatter( $options );
			return new QuantityDetailsFormatter( $localizer, $vocabularyUriFormatter, $options );
		} elseif ( $this->isHtmlFormat( $format ) ) {
			$decimalFormatter = new DecimalFormatter( $options, $this->getNumberLocalizer( $options ) );
			$vocabularyUriFormatter = $this->getVocabularyUriFormatter( $options );
			return new QuantityHtmlFormatter( $options, $decimalFormatter, $vocabularyUriFormatter );
		} else {
			$decimalFormatter = new DecimalFormatter( $options, $this->getNumberLocalizer( $options ) );
			$vocabularyUriFormatter = $this->getVocabularyUriFormatter( $options );
			$plainFormatter = new QuantityFormatter( $options, $decimalFormatter, $vocabularyUriFormatter );
			return $this->escapeValueFormatter( $format, $plainFormatter );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return GlobeCoordinateFormatter|null
	 */
	public function newGlobeCoordinateFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
			return new GlobeCoordinateDetailsFormatter( $options );
		} else {
			$options->setOption( GeoCoordinateFormatter::OPT_FORMAT, GeoCoordinateFormatter::TYPE_DMS );
			$options->setOption( GeoCoordinateFormatter::OPT_SPACING_LEVEL, array(
				GeoCoordinateFormatter::OPT_SPACE_LATLONG
			) );
			$options->setOption( GeoCoordinateFormatter::OPT_DIRECTIONAL, true );

			$plainFormatter = new GlobeCoordinateFormatter( $options );
			return $this->escapeValueFormatter( $format, $plainFormatter );
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return MonolingualHtmlFormatter
	 */
	public function newMonolingualFormatter( $format, FormatterOptions $options ) {
		if ( $this->isHtmlFormat( $format ) ) {
			return new MonolingualHtmlFormatter( $options, $this->languageNameLookup );
		} else {
			return $this->escapeValueFormatter( $format, new MonolingualTextFormatter( $options ) );
		}
	}

}
