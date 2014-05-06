<?php

namespace Wikibase\Lib;

use Html;
use InvalidArgumentException;
use Wikibase\Lib\SnakFormatter;

/**
 * @license GPL 2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MismatchingSnakValueFormatter {

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @param string $format The name of this formatter's output format.
	 *		Use the FORMAT_XXX constants defined in OutputFormatSnakFormatterFactory.
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $format ) {
		if ( !is_string( $format ) ) {
			throw new InvalidArgumentException( '$format must be a string' );
		}

		$this->format = $format;
	}

	/**
	 * @param string $dataValueType
	 * @param string $dataTypeValueType
	 *
	 * @return string
	 */
	public function format( $dataValueType, $dataTypeValueType  ) {
		if ( $dataValueType === 'bad' ) {
			return $this->formatBadValue();
		} elseif ( $this->format === SnakFormatter::FORMAT_HTML ) {
			return $this->formatWithDetails( $dataValueType, $dataTypeValueType );
		} else {
			return $this->formatPlain();
		}
	}

	/**
	 * @return string
	 */
	private function formatBadValue() {
		return wfMessage( 'wikibase-undeserializable-value' )->parse();
	}

	/**
	 * @return string
	 */
	private function formatPlain() {
		$errorMessage = $this->getParsedErrorMessage();

		return $errorMessage;
	}

	/**
	 * @param string $dataValueType
	 * @param string $dataTypeValueType
	 *
	 * @return string
	 */
	private function formatWithDetails( $dataValueType, $dataTypeValueType ) {
		$errorMessage = $this->getParsedErrorMessage();
		$detailsMessage = $this->getParsedDetailsMessage( $dataValueType, $dataTypeValueType );

		$formattedDetailsError = $this->formatErrorDetailsHtml( $detailsMessage );

		return $errorMessage . $formattedDetailsError;
	}

	/**
	 * @return string
	 */
	private function getParsedErrorMessage() {
		return wfMessage( 'wikibase-snakview-variation-datavaluetypemismatch' )
			->parse();
	}

	/**
	 * @param string $dataValueType
	 * @param string $dataTypeValueType
	 *
	 * @return string
	 */
	private function getParsedDetailsMessage( $dataValueType, $dataTypeValueType ) {
		return wfMessage( 'wikibase-snakview-variation-datavaluetypemismatch-details' )
			->params( $dataValueType, $dataTypeValueType )
			->parse();
	}

	/**
	 * @param string $detailsText
	 *
	 * @return string
 	 */
	private function formatErrorDetailsHtml( $detailsText ) {
		$errorDetailsHtml = Html::element(
			'div',
			array(
				'class' => 'wb-valuesnak-error-datavaluetypemismatch-message'
			),
			$detailsText
		);

		return $errorDetailsHtml;
	}

}
