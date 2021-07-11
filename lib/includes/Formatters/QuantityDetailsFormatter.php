<?php

namespace Wikibase\Lib\Formatters;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use DataValues\UnboundedQuantityValue;
use Html;
use InvalidArgumentException;
use Message;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\NumberLocalizer;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\ValueFormatter;

/**
 * Formatter for rendering the details of a QuantityValue (most useful for diffs) in HTML.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class QuantityDetailsFormatter implements ValueFormatter {

	/**
	 * @var QuantityFormatter
	 */
	private $quantityFormatter;

	/**
	 * @var QuantityFormatter
	 */
	private $numberFormatter;

	/**
	 * @var ValueFormatter
	 */
	private $vocabularyUriFormatter;

	/**
	 * @var FormatterOptions
	 */
	private $options;

	/**
	 * @param NumberLocalizer|null $numberLocalizer
	 * @param ValueFormatter $vocabularyUriFormatter
	 * @param FormatterOptions|null $options
	 */
	public function __construct(
		?NumberLocalizer $numberLocalizer,
		ValueFormatter $vocabularyUriFormatter,
		FormatterOptions $options = null
	) {
		$this->options = $options ?: new FormatterOptions();
		$this->options->defaultOption( ValueFormatter::OPT_LANG, 'en' );

		$decimalFormatter = new DecimalFormatter( $this->options, $numberLocalizer );
		$this->vocabularyUriFormatter = $vocabularyUriFormatter;

		$this->quantityFormatter = new QuantityFormatter(
			$this->options,
			$decimalFormatter,
			$this->vocabularyUriFormatter
		);

		$this->numberFormatter = new QuantityFormatter(
			new FormatterOptions( [
				QuantityFormatter::OPT_SHOW_UNCERTAINTY_MARGIN => false,
				QuantityFormatter::OPT_APPLY_ROUNDING => false,
			] ),
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
	 * @param UnboundedQuantityValue|QuantityValue $value
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof UnboundedQuantityValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a UnboundedQuantityValue.' );
		}

		$html = '';
		$html .= Html::element( 'b',
			[ 'class' => 'wb-details wb-quantity-details wb-quantity-rendered' ],
			$this->quantityFormatter->format( $value )
		);

		$html .= Html::openElement( 'table',
			[ 'class' => 'wb-details wb-quantity-details' ] );

		$html .= $this->renderLabelValuePair( 'amount',
			$this->formatNumber( $value->getAmount(), $value->getUnit() ) );
		if ( $value instanceof QuantityValue ) {
			$html .= $this->renderLabelValuePair( 'upperBound',
				$this->formatNumber( $value->getUpperBound(), $value->getUnit() ) );
			$html .= $this->renderLabelValuePair( 'lowerBound',
				$this->formatNumber( $value->getLowerBound(), $value->getUnit() ) );
		}
		/**
		 * @todo Display URIs to entities in the local repository as clickable labels.
		 * @todo Display URIs that start with http:// or https:// as clickable links.
		 * @todo Mark "unitless" units somehow, e.g. via CSS or with an appended message.
		 * @see WikibaseValueFormatterBuilders::$unitOneUris
		 */
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
			new UnboundedQuantityValue( $number, $unit )
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

		return Html::element( 'a', [ 'href' => $unit ], $formattedUnit );
	}

	/**
	 * @param string $fieldName
	 * @param string $valueHtml HTML
	 *
	 * @return string HTML for the label/value pair
	 */
	private function renderLabelValuePair( $fieldName, $valueHtml ) {
		$html = Html::openElement( 'tr' );

		$html .= Html::element( 'th', [ 'class' => 'wb-quantity-' . $fieldName ],
			$this->getFieldLabel( $fieldName )->text() );
		$html .= Html::rawElement( 'td', [ 'class' => 'wb-quantity-' . $fieldName ],
			$valueHtml );

		$html .= Html::closeElement( 'tr' );
		return $html;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return Message
	 */
	private function getFieldLabel( $fieldName ) {
		$lang = $this->options->getOption( ValueFormatter::OPT_LANG );

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
