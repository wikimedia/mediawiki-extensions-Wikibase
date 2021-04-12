<?php

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\PropertyInfoSnakUrlExpander;
use Wikibase\Lib\Store\FieldPropertyInfoProvider;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * Low level factory for SnakFormatters for well known data types.
 *
 * @warning: This is a low level factory for use by bootstrap code only!
 * Program logic should use an instance of OutputFormatValueFormatterFactory
 * resp. OutputFormatSnakFormatterFactory.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuilders {

	/**
	 * @var WikibaseValueFormatterBuilders
	 */
	private $valueFormatterBuilders;

	/**
	 * @var PropertyInfoLookup
	 */
	private $propertyInfoLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var SnakFormat
	 */
	private $snakFormat;

	public function __construct(
		WikibaseValueFormatterBuilders $valueFormatterBuilders,
		PropertyInfoLookup $propertyInfoLookup,
		PropertyDataTypeLookup $dataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->valueFormatterBuilders = $valueFormatterBuilders;
		$this->propertyInfoLookup = $propertyInfoLookup;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->snakFormat = new SnakFormat();
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
	 * @param string $format The desired target format, see SnakFormatter::FORMAT_XXX
	 *
	 * @throws InvalidArgumentException
	 * @return SnakFormatter
	 */
	public function newExternalIdentifierFormatter( $format ) {
		if ( $format === SnakFormatter::FORMAT_PLAIN ) {
			return new PropertyValueSnakFormatter(
				$format,
				$this->valueFormatterBuilders->newStringFormatter( $format ),
				$this->dataTypeLookup,
				$this->dataTypeFactory
			);
		}

		$urlProvider = new FieldPropertyInfoProvider(
			$this->propertyInfoLookup,
			PropertyInfoLookup::KEY_FORMATTER_URL
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
