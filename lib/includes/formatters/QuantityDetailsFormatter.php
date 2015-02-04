<?php

namespace Wikibase\Lib;

use DataValues\QuantityValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\QuantityFormatter;
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
	 * @var QuantityFormatter
	 */
	protected $quantityFormatter;

	/**
	 * @param FormatterOptions $options
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		$this->decimalFormatter = new DecimalFormatter( $options );
		$this->quantityFormatter = new QuantityFormatter( $this->decimalFormatter, $options );
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
		$html .= Html::element( 'h4',
			array( 'class' => 'wb-details wb-quantity-details wb-quantity-rendered' ),
			$this->quantityFormatter->format( $value )
		);

		$html .= Html::openElement( 'table',
			array( 'class' => 'wb-details wb-quantity-details' ) );

		$html .= $this->renderLabelValuePair( 'amount',
			htmlspecialchars( $this->decimalFormatter->format( $value->getAmount() ) ) );
		$html .= $this->renderLabelValuePair( 'upperBound',
			htmlspecialchars( $this->decimalFormatter->format( $value->getUpperBound() ) ) );
		$html .= $this->renderLabelValuePair( 'lowerBound',
			htmlspecialchars( $this->decimalFormatter->format( $value->getLowerBound() ) ) );
		$html .= $this->renderLabelValuePair( 'unit', htmlspecialchars( $value->getUnit() ) );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', array( 'class' => 'wb-quantity-' . $fieldName ),
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::element( 'td', array( 'class' => 'wb-quantity-' . $fieldName ),
			$valueHtml );

		$html .= Html::closeElement( 'tr' );
		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	protected function getFieldLabel( $fieldName ) {
		$lang = $this->getOption( ValueFormatter::OPT_LANG );

		// Messages:
		// wikibase-quantitydetails-amount
		// wikibase-quantitydetails-upperbound
		// wikibase-quantitydetails-lowerbound
		// wikibase-quantitydetails-unit
		$key = 'wikibase-quantitydetails-' . strtolower( $fieldName );
		$msg = wfMessage( $key )->inLanguage( $lang );

		return $msg;
	}

}
