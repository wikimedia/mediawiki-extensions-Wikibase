<?php

namespace Wikibase\Lib;

use DataValues\QuantityValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a QuantityValue (most useful for diffs) in HTML.
 *
 * @since 0.5
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

		$this->decimalFormatter = new EscapingValueFormatter( new DecimalFormatter( $options ),
			'htmlspecialchars' );
	}

	/**
	 * Generates HTML representing the details of a QuantityValue,
	 * as an itemized list.
	 *
	 * @since 0.5
	 *
	 * @param QuantityValue $value The ID to format
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof QuantityValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an QuantityValue.' );
		}

		$html = '';
		$html .= Html::openElement( 'dl',
			array( 'class' => 'wikibase-details wikibase-quantity-details' ) );

		$html .= $this->renderLabelValuePair( 'amount',
			$this->decimalFormatter->format( $value->getAmount() ) );
		$html .= $this->renderLabelValuePair( 'upperBound',
			$this->decimalFormatter->format( $value->getUpperBound() ) );
		$html .= $this->renderLabelValuePair( 'lowerBound',
			$this->decimalFormatter->format( $value->getLowerBound() ) );
		$html .= $this->renderLabelValuePair( 'unit', htmlspecialchars( $value->getUnit() ) );

		$html .= Html::closeElement( 'dl' );

		return $html;
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	public function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = '';
		$html .= Html::element( 'dt', array( 'class' => 'wikibase-quantity-' . $fieldName ),
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::element( 'dd', array( 'class' => 'wikibase-quantity-' . $fieldName ),
			$valueHtml );

		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	protected function getFieldLabel( $fieldName ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );

		// Messages: wikibase-quantitydetails-amount, wikibase-quantitydetails-upperbound,
		// wikibase-quantitydetails-lowerbound, wikibase-quantitydetails-unit
		$key = 'wikibase-quantitydetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}

}
