<?php

namespace Wikibase\Lib;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\Formatters\ErrorHandlingSnakFormatter;
use Wikimedia\Assert\Assert;

/**
 * Factory service for obtaining a SnakFormatter for a desired output format.
 * Implemented based on constructor callbacks and a default SnakFormatter implementation
 * that uses TypedValueFormatters.
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
	 * @var callable[]
	 */
	private $snakFormatterConstructorCallbacks;

	/**
	 * @param callable[] $snakFormatterConstructorCallbacks An associative array mapping property
	 *        data type IDs to callbacks. The callbacks will be invoked with two parameters: the
	 *        desired output format, and the FormatterOptions. Each callback must return an
	 *        instance of SnakFormatter.
	 * @param OutputFormatValueFormatterFactory $valueFormatterFactory
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 */
	public function __construct(
		array $snakFormatterConstructorCallbacks,
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory
	) {
		Assert::parameterElementType(
			'callable',
			$snakFormatterConstructorCallbacks,
			'$snakFormatterConstructorCallbacks'
		);

		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->snakFormatterConstructorCallbacks = $snakFormatterConstructorCallbacks;
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
		$options->defaultOption( SnakFormatter::OPT_ON_ERROR, SnakFormatter::ON_ERROR_WARN );

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

		$formattersBySnakType = array(
			'novalue' => $noValueSnakFormatter,
			'somevalue' => $someValueSnakFormatter,
			// for 'value' snaks, rely on $formattersByDataType
		);

		$formattersByDataType = $this->createSnakFormatters( $format, $options );

		// Register default formatter for the special '*' key.
		$formattersByDataType['*'] = $valueSnakFormatter;

		$snakFormatter = new DispatchingSnakFormatter(
			$format,
			$this->propertyDataTypeLookup,
			$formattersBySnakType,
			$formattersByDataType
		);

		if ( $options->getOption( SnakFormatter::OPT_ON_ERROR ) === SnakFormatter::ON_ERROR_WARN ) {
			$snakFormatter = new ErrorHandlingSnakFormatter(
				$snakFormatter,
				$valueFormatter,
				$lang
			);
		}

		return $snakFormatter;
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

	/**
	 * Instantiate SnakFormatters based on the constructor callbacks passed to the constructor.
	 *
	 * @param string $format
	 * @param FormatterOptions $options
	 *
	 * @return SnakFormatter[]
	 */
	private function createSnakFormatters( $format, FormatterOptions $options ) {
		$formatters = array();

		foreach ( $this->snakFormatterConstructorCallbacks as $key => $callback ) {
			$instance = call_user_func( $callback, $format, $options );

			if ( $instance === null ) {
				continue;
			}

			Assert::postcondition(
				$instance instanceof SnakFormatter,
				'Constructor callback did not return a SnakFormatter instance'
			);

			$formatters[$key] = $instance;
		}

		//FIXME: format fallback via escaping, as implemented in OutputFormatValueFormatterFactory!

		return $formatters;
	}

}
