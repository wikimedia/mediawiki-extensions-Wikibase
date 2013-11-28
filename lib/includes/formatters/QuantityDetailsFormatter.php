<?php

namespace Wikibase\Lib;

use DataValues\QuantityValue;
use Html;
use InvalidArgumentException;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a QuantityValue (most useful for diffs) in HTML.
 *
 * @since 0.5
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class QuantityDetailsFormatter extends ValueFormatterBase {

	/**
	 * @var DecimalFormatter
	 */
	protected $decimalFormatter;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		$this->decimalFormatter = new DecimalFormatter( $options );
	}

	/**
	 * Generates HTML representing the details of a QuantityValue,
	 * as an itemized list.
	 *
	 * @since 0.4
	 *
	 * @param QuantityValue $value The ID to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof QuantityValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an QuantityValue.' );
		}

		$html = '';
		$html .= Html::openElement( 'dl', array( 'class' => 'wikibase-details wikibase-details-quantity' ) );

		$html .= Html::element( 'dt', array(), $this->getFieldLabel( 'amount' )->text() );
		$html .= Html::element( 'dd', array(), $this->decimalFormatter->format( $value->getAmount() ) );

		$html .= Html::element( 'dt', array(), $this->getFieldLabel( 'upperBound' )->text() );
		$html .= Html::element( 'dd', array(), $this->decimalFormatter->format( $value->getUpperBound() ) );

		$html .= Html::element( 'dt', array(), $this->getFieldLabel( 'lowerBound' )->text() );
		$html .= Html::element( 'dd', array(), $this->decimalFormatter->format( $value->getLowerBound() ) );

		$html .= Html::element( 'dt', array(), $this->getFieldLabel( 'unit' )->text() );
		$html .= Html::element( 'dd', array(), $value->getUnit() ); // localize unit?...

		$html .= Html::closeElement( 'dl' );

		return $html;
	}

	protected function getFieldLabel( $field ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );

		$key = 'wikibase-quantitydetails-' . strtolower( $field );
		return wfMessage( $key )->inLanguage( $lang );
	}
}
