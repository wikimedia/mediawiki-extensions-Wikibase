<?php

namespace Wikibase;

use DataTypes\DataType;
use MWException;

/**
 * Data provider for the property type (a.k.a. data type) selector UI element.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
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
	 * @since 0.5
	 *
	 * @return string[]
	 */
	public function getOptionsArray() {
		$byLabel = [];
		$byId = [];

		foreach ( $this->dataTypes as $dataType ) {
			$label = $dataType->getLabel( $this->languageCode );
			$id = $dataType->getId();

			$byLabel[$label] = $id;
			$byId[$id] = $id;
		}

		if ( count( $byLabel ) < count( $this->dataTypes ) ) {
			$byLabel = $byId;
		}

		uksort( $byLabel, 'strnatcasecmp' );
		return $byLabel;
	}

}
