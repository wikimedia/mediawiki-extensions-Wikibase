<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * Service for obtaining a SnakFormatter for a desired output format.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class OutputFormatSnakFormatterFactory {

	/**
	 * @var OutputFormatValueFormatterFactory
	 */
	private $valueFormatterFactory;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @param OutputFormatValueFormatterFactory $valueFormatterFactory
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 */
	public function __construct(
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
	}

	/**
	 * Returns an SnakFormatter for rendering snaks in the desired format
	 * using the given options.
	 *
	 * @param string $format Use the SnakFormatter::FORMAT_XXX constants.
	 * @param FormatterOptions $options
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return SnakFormatter
	 */
	public function getSnakFormatter( $format, FormatterOptions $options ) {
		$this->valueFormatterFactory->applyLanguageDefaults( $options );
		$lang = $options->getOption( ValueFormatter::OPT_LANG );

		$noValueSnakFormatter = new MessageSnakFormatter(
			'novalue',
			$this->getMessage( 'wikibase-snakview-snaktypeselector-novalue', $lang ),
			$format
		);
		$someValueSnakFormatter = new MessageSnakFormatter(
			'somevalue',
			$this->getMessage( 'wikibase-snakview-snaktypeselector-somevalue', $lang ),
			$format
		);

		$valueFormatter = $this->valueFormatterFactory->getValueFormatter( $format, $options );
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
	 * @return Message
	 */
	private function getMessage( $key, $lang ) {
		$msg = wfMessage( $key );
		$msg = $msg->inLanguage( $lang );
		return $msg;
	}

}
