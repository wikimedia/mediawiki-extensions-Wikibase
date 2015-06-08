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
use ValueFormatters\QuantityUnitFormatter;
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
	 * @var QuantityUnitFormatter
	 */
	protected $unitFormatter;

	/**
	 * @param NumberLocalizer $numberLocalizer
	 * @param QuantityUnitFormatter $unitFormatter
	 * @param FormatterOptions|null $options
	 */
	public function __construct( NumberLocalizer $numberLocalizer, QuantityUnitFormatter $unitFormatter, FormatterOptions $options = null ) {
		parent::__construct( $options );

		$this->unitFormatter = $unitFormatter;
		$this->decimalFormatter = new DecimalFormatter( $this->options, $numberLocalizer );
		$this->quantityFormatter = new QuantityFormatter( $this->decimalFormatter, $unitFormatter, $this->options );
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
		$html .= $this->renderLabelValuePair( 'unit', htmlspecialchars( $value->getUnit() ) );

		$html .= Html::closeElement( 'table' );

		return $html;
	}

	private function formatNumber( DecimalValue $number, $unit ) {
		$text = $this->decimalFormatter->format( $number );
		$text = $this->unitFormatter->applyUnit( $unit, $text );
		return htmlspecialchars( $text );
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
