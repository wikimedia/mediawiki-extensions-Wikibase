<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\PropertyInfoStore;

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
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $fallbackLookup;

	/**
	 * @param OutputFormatValueFormatterFactory $valueFormatterFactory
	 * @param DataTypeFactory $dataTypeFactory
	 * @param PropertyInfoStore $propertyInfoStore
	 * @param PropertyDataTypeLookup|null $fallbackLookup
	 */
	public function __construct(
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		DataTypeFactory $dataTypeFactory,
		PropertyInfoStore $propertyInfoStore,
		PropertyDataTypeLookup $fallbackLookup = null
	) {
		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->fallbackLookup = $fallbackLookup;
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
			$this->propertyInfoStore,
			$this->fallbackLookup,
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
