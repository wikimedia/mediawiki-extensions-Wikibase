<?php

namespace Wikibase;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Edrsf\PropertyInfoLookup;
use Wikibase\Edrsf\PropertyInfoTable;

/**
 * Class to build the information about a property.
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoBuilder {

	/**
	 * @var PropertyId|null
	 */
	private $formatterUrlProperty;

	/**
	 * @param PropertyId|null $formatterUrlProperty
	 */
	public function __construct( PropertyId $formatterUrlProperty = null ) {
		$this->formatterUrlProperty = $formatterUrlProperty;
	}

	/**
	 * @see PropertyInfoTable::setPropertyInfo
	 *
	 * @param Property $property
	 *
	 * @return array Information to be stored in the "pi_info" column of the "wb_property_info"
	 * table. Must be an array and can contain anything that can be encoded by json_encode.
	 */
	public function buildPropertyInfo( Property $property ) {
		$info = array(
			PropertyInfoLookup::KEY_DATA_TYPE => $property->getDataTypeId()
		);

		$formatterUrl = $this->getFormatterUrl( $property->getStatements() );
		if ( $formatterUrl !== null ) {
			$info[PropertyInfoLookup::KEY_FORMATTER_URL] = $formatterUrl;
		}

		return $info;
	}

	/**
	 * @param StatementList $statements
	 *
	 * @return string|null The string value of the main snak of the first best
	 * "formatterUrlProperty" statements, if such exists. Null otherwise.
	 */
	private function getFormatterUrl( StatementList $statements ) {
		if ( $this->formatterUrlProperty === null ) {
			return null;
		}

		$bestStatements = $statements->getByPropertyId( $this->formatterUrlProperty )->getBestStatements();
		if ( $bestStatements->isEmpty() ) {
			return null;
		}

		$statementArray = $bestStatements->toArray();
		$mainSnak = $statementArray[0]->getMainSnak();
		if ( !( $mainSnak instanceof PropertyValueSnak ) ) {
			return null;
		}

		$dataValue = $mainSnak->getDataValue();
		if ( !( $dataValue instanceof StringValue ) ) {
			return null;
		}

		return $dataValue->getValue();
	}

}
