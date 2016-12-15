<?php

namespace Wikibase;

use DataTypes\DataType;
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
	 * Builds and returns the array for the options of the DataType selector.
	 *
	 * @return array
	 */
	public function getOptionsArray() {
		$dataTypes = array();

		foreach ( $this->dataTypes as $dataType ) {
			$dataTypes[$dataType->getId()] = $dataType->getLabel( $this->languageCode );
		}

		natcasesort( $dataTypes );

		return $dataTypes;
	}

}
