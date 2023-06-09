<?php

namespace Wikibase\Repo;

use InvalidArgumentException;
use Wikibase\Lib\DataType;

/**
 * Data provider for the property type (a.k.a. data type) selector UI element.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
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
	 * @param DataType[] $dataTypes
	 * @param string $languageCode
	 */
	public function __construct( array $dataTypes, string $languageCode ) {
		foreach ( $dataTypes as $dataType ) {
			if ( !( $dataType instanceof DataType ) ) {
				throw new InvalidArgumentException( '$dataTypes should only contain instances of ' . DataType::class );
			}
		}

		$this->dataTypes = $dataTypes;
		$this->languageCode = $languageCode;
	}

	/**
	 * Builds and returns the array for the options of the DataType selector.
	 *
	 * @return string[]
	 */
	public function getOptionsArray() {
		$byLabel = [];
		$byId = [];

		foreach ( $this->dataTypes as $dataType ) {
			$label = wfMessage( $dataType->getMessageKey() )->inLanguage( $this->languageCode )
				->text();
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
