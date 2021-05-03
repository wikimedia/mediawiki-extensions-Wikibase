<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use InvalidArgumentException;
use Message;
use RuntimeException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\MessageInLanguageProvider;
use Wikimedia\Assert\Assert;

/**
 * Factory service for obtaining a SnakFormatter for a desired output format.
 * Implemented based on constructor callbacks and a default SnakFormatter implementation
 * that uses TypedValueFormatters.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class OutputFormatSnakFormatterFactory {

	/**
	 * @var callable[]
	 */
	private $snakFormatterConstructorCallbacks;

	/**
	 * @var OutputFormatValueFormatterFactory
	 */
	private $valueFormatterFactory;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/** @var MessageInLanguageProvider */
	private $messageInLanguageProvider;

	/**
	 * @param callable[] $snakFormatterConstructorCallbacks An associative array mapping property
	 *        data type IDs to callbacks. The callbacks will be invoked with two parameters: the
	 *        desired output format, and the FormatterOptions. Each callback must return an
	 *        instance of SnakFormatter.
	 * @param OutputFormatValueFormatterFactory $valueFormatterFactory
	 * @param PropertyDataTypeLookup $propertyDataTypeLookup
	 * @param DataTypeFactory $dataTypeFactory
	 * @param MessageInLanguageProvider $messageInLanguageProvider
	 */
	public function __construct(
		array $snakFormatterConstructorCallbacks,
		OutputFormatValueFormatterFactory $valueFormatterFactory,
		PropertyDataTypeLookup $propertyDataTypeLookup,
		DataTypeFactory $dataTypeFactory,
		MessageInLanguageProvider $messageInLanguageProvider
	) {
		Assert::parameterElementType(
			'callable',
			$snakFormatterConstructorCallbacks,
			'$snakFormatterConstructorCallbacks'
		);

		$this->snakFormatterConstructorCallbacks = $snakFormatterConstructorCallbacks;
		$this->valueFormatterFactory = $valueFormatterFactory;
		$this->propertyDataTypeLookup = $propertyDataTypeLookup;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->messageInLanguageProvider = $messageInLanguageProvider;
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
			$valueFormatter,
			$this->propertyDataTypeLookup,
			$this->dataTypeFactory
		);

		$formattersBySnakType = [
			'novalue' => $noValueSnakFormatter,
			'somevalue' => $someValueSnakFormatter,
			// for 'value' snaks, rely on $formattersByDataType
		];

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

	private function getMessage( string $key, string $languageCode ): Message {
		return $this->messageInLanguageProvider->msgInLang( $key, $languageCode );
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
		$formatters = [];

		foreach ( $this->snakFormatterConstructorCallbacks as $key => $callback ) {
			$instance = call_user_func( $callback, $format, $options );

			Assert::postcondition(
				$instance instanceof SnakFormatter,
				"Constructor callback did not return a SnakFormatter for $key"
			);

			$formatters[$key] = $instance;
		}

		return $formatters;
	}

}
