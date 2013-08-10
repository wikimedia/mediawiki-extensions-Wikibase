<?php

/**
 * Class registration file for the DataValues library.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
return array(
	'DataValues\BooleanValue' => 'includes/values/BooleanValue.php',
	'DataValues\GeoCoordinateValue' => 'includes/values/GeoCoordinateValue.php',
	'DataValues\IriValue' => 'includes/values/IriValue.php',
	'DataValues\MonolingualTextValue' => 'includes/values/MonolingualTextValue.php',
	'DataValues\MultilingualTextValue' => 'includes/values/MultilingualTextValue.php',
	'DataValues\NumberValue' => 'includes/values/NumberValue.php',
	'DataValues\QuantityValue' => 'includes/values/QuantityValue.php',
	'DataValues\StringValue' => 'includes/values/StringValue.php',
	'DataValues\TimeValue' => 'includes/values/TimeValue.php',
	'DataValues\UnknownValue' => 'includes/values/UnknownValue.php',
	'DataValues\UnDeserializableValue' => 'includes/values/UnDeserializableValue.php',

	'DataValues\DataValue' => 'includes/DataValue.php',
	'DataValues\DataValueFactory' => 'includes/DataValueFactory.php',
	'DataValues\DataValueObject' => 'includes/DataValueObject.php',

	'DataValues\IllegalValueException' => 'includes/IllegalValueException.php',

	'Comparable' => 'includes/Comparable.php',
	'Copyable' => 'includes/Copyable.php',
	'Hashable' => 'includes/Hashable.php',
	'Immutable' => 'includes/Immutable.php',

	'DataValues\Test\DataValueTest' => 'tests/phpunit/includes/DataValueTest.php',
);
