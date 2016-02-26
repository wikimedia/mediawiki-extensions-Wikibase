<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use ValueFormatters\FormattingException;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\DataModel\Snak\Snak;
use Wikimedia\Assert\Assert;

/**
 * DispatchingSnakFormatter will format a Snak by delegating the formatting to an appropriate
 * SnakFormatter based on the snak type or the associated property's data type.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DispatchingSnakFormatter implements SnakFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var SnakFormatter[]
	 */
	private $formattersByDataType;

	/**
	 * @var SnakFormatter[]
	 */
	private $formattersBySnakType;

	/**
	 * @param string $format The output format generated by this formatter. All SnakFormatters
	 *  provided via $formattersBySnakType and $formattersByDataType must be safe for this
	 *  output format. This is checked by comparing the $format with what each SnakFormatter
	 *  returns from getFormat(). MIME parameters are ignored for this check, so FORMAT_HTML
	 *  is considered compatible with FORMAT_HTML_DIFF, etc.
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param SnakFormatter[] $formattersBySnakType An associative array mapping snak types
	 *  to SnakFormatter objects. If no formatter is defined for the a given snak type,
	 *  $formattersByDataType will be checked for a SnakFormatter for the snak's data type.
	 * @param SnakFormatter[] $formattersByDataType An associative array mapping data types
	 *  to SnakFormatter objects. If no formatter is defined for the a given data type,
	 *  the "*" key in this array is checked for a default formatter.
	 *
	 * @throws InvalidArgumentException If any of the given formatters is incompatible
	 *         with $format. Formats are assumed to be represented by MIME types,
	 *         MIME parameters are ignored.
	 */
	public function __construct(
		$format,
		PropertyDataTypeLookup $dataTypeLookup,
		array $formattersBySnakType,
		array $formattersByDataType
	) {
		Assert::parameterType( 'string', $format, '$format' );

		$this->assertFormatterArray( $format, $formattersBySnakType );
		$this->assertFormatterArray( $format, $formattersByDataType );

		$this->format = $format;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->formattersBySnakType = $formattersBySnakType;
		$this->formattersByDataType = $formattersByDataType;
	}

	/**
	 * @param string $format MIME type
	 * @param SnakFormatter[] $formatters
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertFormatterArray( $format, array $formatters ) {
		foreach ( $formatters as $type => $formatter ) {
			if ( !is_string( $type ) ) {
				throw new InvalidArgumentException( 'formatter array must map type IDs to formatters.' );
			}

			if ( !( $formatter instanceof SnakFormatter ) ) {
				throw new InvalidArgumentException( 'formatter array must contain instances of SnakFormatter.' );
			}

			// Ignore MIME parameters when checking output format. We only care that the base format
			// is the same, so we can assume that all formatters apply the correct escaping and are
			// safe to use.
			if ( $this->getBaseFormat( $formatter->getFormat() ) !== $this->getBaseFormat( $format ) ) {
				throw new InvalidArgumentException( 'The formatter supplied for ' . $type
					. ' produces ' . $formatter->getFormat() . ', but we expect ' . $format . '.' );
			}
		}
	}

	/**
	 * @param string $format MIME type
	 *
	 * @return string MIME type with parameters stripped.
	 */
	private function getBaseFormat( $format ) {
		return preg_replace( '/ *;.*$/', '', $format );
	}

	/**
	 * @param Snak $snak
	 *
	 * @throws PropertyDataTypeLookupException
	 * @return string The Snak's data type
	 */
	private function getSnakDataType( Snak $snak ) {
		return $this->dataTypeLookup->getDataTypeIdForProperty( $snak->getPropertyId() );
		// @todo: wrap the PropertyDataTypeLookupException, but make sure ErrorHandlingSnakFormatter still handles it.
	}

	/**
	 * @see SnakFormatter::formatSnak
	 *
	 * Formats the given Snak by finding an appropriate formatter among the ones supplied
	 * to the constructor, and applying it.
	 *
	 * @param Snak $snak
	 *
	 * @throws FormattingException
	 * @throws PropertyDataTypeLookupException
	 * @return string The formatted snak value, in the format specified by getFormat().
	 */
	public function formatSnak( Snak $snak ) {
		$snakType = $snak->getType();

		if ( isset( $this->formattersBySnakType[$snakType] ) ) {
			$formatter = $this->formattersBySnakType[$snakType];
			return $formatter->formatSnak( $snak );
		}

		$dataType = $this->getSnakDataType( $snak );

		if ( isset( $this->formattersByDataType["PT:$dataType"] ) ) {
			$formatter = $this->formattersByDataType["PT:$dataType"];
			return $formatter->formatSnak( $snak );
		}

		if ( isset( $this->formattersByDataType['*'] ) ) {
			$formatter = $this->formattersByDataType['*'];
			return $formatter->formatSnak( $snak );
		}

		throw new FormattingException( "No formatter found for snak type $snakType and data type $dataType" );
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
