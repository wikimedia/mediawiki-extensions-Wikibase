<?php

namespace Wikibase\Lib;

use DataValues\Geo\Formatters\GeoCoordinateFormatter;
use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use Language;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\EntityId\EntityIdLabelFormatter;
use Wikibase\DataModel\Entity\EntityIdParser;
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
					return $format === SnakFormatter::FORMAT_PLAIN ? new StringFormatter( $options ) : null;
				},

				'bad' => function( $format, FormatterOptions $options ) use ( $self ) {
					return $format === SnakFormatter::FORMAT_PLAIN ? new UnDeserializableValueFormatter( $options ) : null;
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
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newEntityIdFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return $this->newPlainEntityIdFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_HTML ) {
			if ( !$this->entityTitleLookup ) {
				return new EscapingValueFormatter(
					$this->newPlainEntityIdFormatter( $options ),
					'htmlspecialchars'
				);
			} else {
				$labelDescriptionLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
				return new EntityIdValueFormatter(
					new EntityIdHtmlLinkFormatter(
						$labelDescriptionLookup,
						$this->entityTitleLookup,
						$this->languageNameLookup
					)
				);
			}
		} else {
			return null;
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newUrlFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return new StringFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_WIKI ) {
			// use the string formatter without escaping!
			return new StringFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_HTML ) {
			// use the string formatter without escaping!
			return new HtmlUrlFormatter( $options );
		} else {
			return null;
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newCommonsMediaFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return new StringFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_WIKI ) {
			//TODO: wikitext image link (inline? thumbnail? caption?...)
			return null;
		} elseif ( $format === SnakFormatter::FORMAT_HTML ) {
			return new CommonsLinkFormatter( $options );
		} else {
			return null;
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return ValueFormatter|null
	 */
	public function newTimeFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return new MwTimeIsoFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_HTML ) {
			return new HtmlTimeFormatter( $options, new MwTimeIsoFormatter( $options ) );
		} elseif ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
			return new TimeDetailsFormatter(
				$options,
				new HtmlTimeFormatter( $options, new MwTimeIsoFormatter( $options ) )
			);
		} else {
			return null;
		}
	}

	private function getNumberLocalizer( FormatterOptions $options ) {
		$language = Language::factory( $options->getOption( ValueFormatter::OPT_LANG ) );
		return new MediaWikiNumberLocalizer( $language );
	}

	private function getQuantityUnitFormatter( FormatterOptions $options ) {
		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );

		return new EntityLabelUnitFormatter( $this->repoUriParser, $labelDescriptionLookup, $this->unitOneUris );
	}

	/**
	 * @param FormatterOptions $options
	 *
	 * @return QuantityFormatter|null
	 */
	public function newQuantityFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			$decimalFormatter = new DecimalFormatter( $options, $this->getNumberLocalizer( $options ) );
			$labelDescriptionLookup = $this->labelDescriptionLookupFactory->getLabelDescriptionLookup( $options );
			$unitFormatter = new EntityLabelUnitFormatter( $this->repoUriParser, $labelDescriptionLookup );
			return new QuantityFormatter( $decimalFormatter, $unitFormatter, $options );
		} elseif ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
			$localizer = $this->getNumberLocalizer( $options );
			$unitFormatter = $this->getQuantityUnitFormatter( $options );
			return new QuantityDetailsFormatter( $localizer, $unitFormatter, $options );
		} else {
			return null;
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return GlobeCoordinateFormatter|null
	 */
	public function newGlobeCoordinateFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			$options->setOption( GeoCoordinateFormatter::OPT_FORMAT, GeoCoordinateFormatter::TYPE_DMS );
			$options->setOption( GeoCoordinateFormatter::OPT_SPACING_LEVEL, array(
				GeoCoordinateFormatter::OPT_SPACE_LATLONG
			) );
			$options->setOption( GeoCoordinateFormatter::OPT_DIRECTIONAL, true );

			return new GlobeCoordinateFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_HTML_DIFF ) {
			return new GlobeCoordinateDetailsFormatter( $options );
		} else {
			return null;
		}
	}

	/**
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return MonolingualHtmlFormatter
	 */
	public function newMonolingualFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return new MonolingualTextFormatter( $options );
		} elseif ( $format === SnakFormatter::FORMAT_HTML ) {
			return new MonolingualHtmlFormatter( $options, $this->languageNameLookup );
		} else {
			return null;
		}
	}

}
