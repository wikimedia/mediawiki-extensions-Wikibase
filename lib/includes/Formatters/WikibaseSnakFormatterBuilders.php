<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Formatters\HtmlExternalIdentifierFormatter;
use Wikibase\Lib\Formatters\WikitextExternalIdentifierFormatter;
use Wikibase\PropertyInfoStore;

/**
 * Low level factory for SnakFormatters for well known data types.
 *
 * @warning: This is a low level factory for use by boostrap code only!
 * Program logic should use an instance of OutputFormatValueFormatterFactory
 * resp. OutputFormatSnakFormatterFactory.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuilders {

	/**
	 * @var WikibaseValueFormatterBuilders
	 */
	private $valueFormatterBuilders;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 * @param PropertyInfoStore $propertyInfoStore
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 */
	public function __construct(
		WikibaseValueFormatterBuilders $valueFormatterBuilders,
		PropertyInfoStore $propertyInfoStore,
		PropertyDataTypeLookup $dataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->valueFormatterBuilders = $valueFormatterBuilders;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
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
			case SnakFormatter::FORMAT_HTML_WIDGET:
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
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @throws InvalidArgumentException
	 * @return SnakFormatter
	 */
	public function newExternalIdentifierFormatter( $format, FormatterOptions $options ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return new PropertyValueSnakFormatter(
				$format,
				$options,
				$this->valueFormatterBuilders->newStringFormatter( $format, $options ),
				$this->dataTypeLookup,
				$this->dataTypeFactory
			);
		}

		$urlProvider = new FieldPropertyInfoProvider(
			$this->propertyInfoStore,
			PropertyInfoStore::KEY_FORMATTER_URL
		);

		$urlExpander = new PropertyInfoSnakUrlExpander( $urlProvider );

		if ( $format === SnakFormatter::FORMAT_WIKI ) {
			return new WikitextExternalIdentifierFormatter( $urlExpander );
		} elseif ( $this->isHtmlFormat( $format ) ) {
			return new HtmlExternalIdentifierFormatter( $urlExpander );
		}

		throw new InvalidArgumentException( 'Unsupported output format: ' . $format );
	}

}
