<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use ValueFormatters\FormattingException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Assert\Assert;

/**
 * Dispatching snak formatter that makes it possible to special case certain
 * Snaks based on the associated property's data type. Depending on that
 * data type, we choose between a "special" SnakFormatter and a generic one.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class BinaryOptionDispatchingSnakFormatter implements SnakFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var SnakFormatter
	 */
	private $specialCaseSnakFormatter;

	/**
	 * @var SnakFormatter
	 */
	private $fallbackSnakFormatter;

	/**
	 * @var string[]
	 */
	private $specialCasedPropertyDataTypes;

	/**
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param SnakFormatter $specialCaseSnakFormatter Snak formatter to use for
	 *     Snaks with a property data type in $specialCasedPropertyDataTypes.
	 * @param SnakFormatter $fallbackSnakFormatter
	 * @param array $specialCasedPropertyDataTypes
	 */
	public function __construct(
		string $format,
		PropertyDataTypeLookup $dataTypeLookup,
		SnakFormatter $specialCaseSnakFormatter,
		SnakFormatter $fallbackSnakFormatter,
		array $specialCasedPropertyDataTypes
	) {
		Assert::parameterElementType(
			'string',
			$specialCasedPropertyDataTypes,
			'$specialCasedPropertyDataTypes'
		);

		$this->format = $format;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->specialCaseSnakFormatter = $specialCaseSnakFormatter;
		$this->fallbackSnakFormatter = $fallbackSnakFormatter;
		$this->specialCasedPropertyDataTypes = $specialCasedPropertyDataTypes;
	}

	/**
	 * @param Snak $snak
	 *
	 * @throws PropertyDataTypeLookupException
	 * @return string|null The Snak's data type
	 */
	private function getSnakDataType( Snak $snak ) {
		try {
			return $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		} catch ( PropertyDataTypeLookupException $ex ) {
			return null;
		}
	}

	/**
	 * @see SnakFormatter::formatSnak
	 *
	 * @param Snak $snak
	 *
	 * @throws FormattingException
	 * @throws PropertyDataTypeLookupException
	 * @return string The formatted snak value, in the format specified by getFormat().
	 */
	public function formatSnak( Snak $snak ) {
		$snakType = $snak->getType();

		if ( $snakType === 'value' ) {
			$dataType = $this->getSnakDataType( $snak );
			if ( $dataType !== null && in_array( $dataType, $this->specialCasedPropertyDataTypes ) ) {
				return $this->specialCaseSnakFormatter->formatSnak( $snak );
			}
		}

		return $this->fallbackSnakFormatter->formatSnak( $snak );
	}

	/**
	 * @see SnakFormatter::getFormat
	 *
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

}
