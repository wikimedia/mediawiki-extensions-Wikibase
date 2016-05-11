<?php

namespace Wikibase\Lib;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\NumberLocalizer;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/**
 * Formatter for rendering the details of a QuantityValue (most useful for diffs) in HTML.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class QuantityDetailsFormatter extends ValueFormatterBase {

	/**
	 * @var QuantityFormatter
	 */
	protected $quantityFormatter;

	/**
	 * @var QuantityFormatter
	 */
	protected $numberFormatter;

	/**
	 * @var ValueFormatter
	 */
	protected $vocabularyUriFormatter;

	/**
	 * @param NumberLocalizer|null $numberLocalizer
	 * @param ValueFormatter $vocabularyUriFormatter
	 * @param FormatterOptions|null $options
	 */
	public function __construct(
		NumberLocalizer $numberLocalizer = null,
		ValueFormatter $vocabularyUriFormatter,
		FormatterOptions $options = null
	) {
		parent::__construct( $options );

		$decimalFormatter = new DecimalFormatter( $this->options, $numberLocalizer );
		$this->vocabularyUriFormatter = $vocabularyUriFormatter;

		$this->quantityFormatter = new QuantityFormatter(
			$this->options,
			$decimalFormatter,
			$this->vocabularyUriFormatter
		);

		$this->numberFormatter = new QuantityFormatter(
			new FormatterOptions( array(
				QuantityFormatter::OPT_SHOW_UNCERTAINTY_MARGIN => false,
				QuantityFormatter::OPT_APPLY_ROUNDING => false,
			) ),
			$decimalFormatter,
			$this->vocabularyUriFormatter
		);
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Generates HTML representing the details of a QuantityValue,
	 * as an itemized list.
	 *
	 * @param QuantityValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof QuantityValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a QuantityValue.' );
		}

		$html = '';
		$html .= Html::element( 'h4',
			array( 'class' => 'wb-details wb-quantity-details wb-quantity-rendered' ),
			$this->quantityFormatter->format( $value )
		);

		$html .= Html::openElement( 'table',
			array( 'class' => 'wb-details wb-quantity-details' ) );

		$html .= $this->renderLabelValuePair( 'amount',
			$this->formatNumber( $value->getAmount(), $value->getUnit() ) );
		$html .= $this->renderLabelValuePair( 'upperBound',
			$this->formatNumber( $value->getUpperBound(), $value->getUnit() ) );
		$html .= $this->renderLabelValuePair( 'lowerBound',
			$this->formatNumber( $value->getLowerBound(), $value->getUnit() ) );
		$html .= $this->renderLabelValuePair( 'unit', $this->formatUnit( $value->getUnit() ) );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	/**
	 * @param DecimalValue $number
	 * @param string $unit URI
	 *
	 * @return string HTML
	 */
	private function formatNumber( DecimalValue $number, $unit ) {
		return htmlspecialchars( $this->numberFormatter->format(
			new QuantityValue( $number, $unit, $number, $number )
		) );
	}

	/**
	 * @param string $unit URI
	 *
	 * @return string HTML
	 */
	private function formatUnit( $unit ) {
		$formattedUnit = $this->vocabularyUriFormatter->format( $unit );

		if ( $formattedUnit === null || $formattedUnit === $unit ) {
			return htmlspecialchars( $unit );
		}

		return Html::element( 'a', array( 'href' => $unit ), $formattedUnit );
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml HTML
	 *
	 * @return string HTML for the label/value pair
	 */
	protected function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', array( 'class' => 'wb-quantity-' . $fieldName ),
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::rawElement( 'td', array( 'class' => 'wb-quantity-' . $fieldName ),
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
