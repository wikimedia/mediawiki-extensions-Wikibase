<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;

/**
 * Defines the snak formatters supported by Wikibase.
 * This largely relies on WikibaseValueFormatterBase, adding some logic
 * for handling different types of Snaks.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseSnakFormatterBuilders {

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 */
	public function __construct(
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * @return array DataType builder specs
	 */
	public function getSnakFormatterBuildersForFormats() {
		$buildDispatchingSnakFormatter = array( $this, 'buildDispatchingSnakFormatter' );

		$types = array(
			SnakFormatter::FORMAT_WIKI => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_PLAIN => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML_WIDGET => $buildDispatchingSnakFormatter,
			SnakFormatter::FORMAT_HTML_DIFF => $buildDispatchingSnakFormatter,
		);

		return $types;
	}

	/**
	 * Returns a DispatchingSnakFormatter for the given format, that will dispatch based on
	 * the snak type. The instance returned by this method will cover all standard snak types.
	 *
	 * @param string $format
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 * @param FormatterOptions $options
	 *
	 * @return DispatchingSnakFormatter
	 */
	public function buildDispatchingSnakFormatter(
		$format,
		WikibaseValueFormatterBuilders $valueFormatterBuilders,
		FormatterOptions $options
	) {
		$valueFormatterBuilders->applyLanguageDefaults( $options );
		$lang = $options->getOption( ValueFormatter::OPT_LANG );

		$noValueSnakFormatter = new MessageSnakFormatter( 'novalue', $this->getMessage( 'wikibase-snakview-snaktypeselector-novalue', $lang ), $format );
		$someValueSnakFormatter = new MessageSnakFormatter( 'somevalue', $this->getMessage( 'wikibase-snakview-snaktypeselector-somevalue', $lang ), $format );

		$factory = new OutputFormatValueFormatterFactory( $valueFormatterBuilders->getValueFormatterBuildersForFormats() );
		$valueFormatter = $valueFormatterBuilders->buildDispatchingValueFormatter( $factory, $format, $options );
		$valueSnakFormatter = new PropertyValueSnakFormatter(
			$format,
			$options,
			$valueFormatter,
			$this->propertyDataTypeLookup,
			$this->dataTypeFactory
		);

		$formatters = array(
			'novalue' => $noValueSnakFormatter,
			'somevalue' => $someValueSnakFormatter,
			'value' => $valueSnakFormatter,
		);

		return new DispatchingSnakFormatter( $format, $formatters );
	}

	/**
	 * @param string $key
	 * @param string $lang
	 *
	 * @return \Message
	 */
	private function getMessage( $key, $lang ) {
		$msg = wfMessage( $key );
		$msg = $msg->inLanguage( $lang );
		return $msg;
	}
}
