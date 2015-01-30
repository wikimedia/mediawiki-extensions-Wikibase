<?php

namespace Wikibase;
use DataTypes\DataType;
use Html;
use MWException;

/**
 * DataType selector UI element.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataTypeSelector {

	/**
	 * @var DataType[]
	 */
	protected $dataTypes;

	/**
	 * @var string
	 */
	protected $languageCode;

	/**
	 * @since 0.4
	 *
	 * @param DataType[] $dataTypes
	 * @param string $languageCode
	 *
	 * @throws MWException
	 */
	public function __construct( array $dataTypes, $languageCode ) {
		if ( !is_string( $languageCode ) ) {
			throw new MWException( '$languageCode should be a string' );
		}

		foreach ( $dataTypes as $dataType ) {
			if ( !( $dataType instanceof DataType ) ) {
				throw new MWException( '$dataTypes should only contain instances of DataTypes\DataType' );
			}
		}

		$this->dataTypes = $dataTypes;
		$this->languageCode = $languageCode;
	}

	/**
	 * Builds and returns the HTML for the DataType selector.
	 *
	 * @since 0.4
	 *
	 * @param string $id
	 * @param string $name
	 *
	 * @return string
	 */
	public function getHtml( $id = 'datatype', $name = 'datatype' ) {
		$dataTypes = array();

		foreach ( $this->dataTypes as $dataType ) {
			$dataTypes[$dataType->getId()] = $dataType->getLabel( $this->languageCode );
		}

		natcasesort( $dataTypes );

		$html = '';

		foreach ( $dataTypes as $typeId => $typeLabel ) {
			$html .= Html::element(
				'option',
				array( 'value' => $typeId ),
				$typeLabel
			);
		}

		$html = Html::rawElement(
			'select',
			array(
				'name' => $name,
				'id' => $id,
				'class' => 'wb-select'
			),
			$html
		);

		return $html;
	}

}