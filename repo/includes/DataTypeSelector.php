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
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataTypeSelector {

	/**
	 * @var DataType[]
	 */
	private $dataTypes;

	/**
	 * @var string
	 */
	private $languageCode;

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
	 * @param string $selectedTypeId
	 *
	 * @return string
	 */
	public function getHtml( $id = 'datatype', $name = 'datatype', $selectedTypeId = '' ) {
		$options = $this->getOptionsHtml( $selectedTypeId );

		$html = Html::rawElement(
			'select',
			array(
				'name' => $name,
				'id' => $id,
				'class' => 'wb-select'
			),
			$options
		);

		return $html;
	}

	/**
	 * Builds and returns the array for the options of the DataType selector.
	 *
	 * @return array
	 */
	public function getOptionsArray() {
		$dataTypes = [];

		foreach ( $this->dataTypes as $dataType ) {
			$dataTypes[$dataType->getId()] = $dataType->getLabel( $this->languageCode );
		}

		natcasesort( $dataTypes );

		return $dataTypes;
	}

	/**
	 * Builds and returns the html for the options of the DataType selector.
	 *
	 * @since 0.5
	 *
	 * @param string $selectedTypeId
	 *
	 * @return string
	 */
	public function getOptionsHtml( $selectedTypeId = '' ) {
		$dataTypes = $this->getOptionsArray();

		$html = '';

		foreach ( $dataTypes as $typeId => $typeLabel ) {
			$html .= Html::element(
				'option',
				array(
					'value' => $typeId,
					'selected' => $typeId === $selectedTypeId
				),
				$typeLabel
			);
		}

		return $html;
	}

}
