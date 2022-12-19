<?php

namespace Wikibase\Repo;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikimedia\Assert\Assert;

/**
 * Class to build the information about a property.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class PropertyInfoBuilder {

	/**
	 * @var NumericPropertyId[] Maps PropertyInfoStore keys to PropertyIds
	 */
	private $propertyIdMap;

	/**
	 * @param NumericPropertyId[] $propertyIdMap Maps PropertyInfoStore keys to PropertyIds
	 */
	public function __construct( $propertyIdMap = [] ) {
		Assert::parameterElementType( NumericPropertyId::class, $propertyIdMap, '$propertyIdMap' );
		$this->propertyIdMap = $propertyIdMap;
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
		$info = [
			PropertyInfoLookup::KEY_DATA_TYPE => $property->getDataTypeId(),
		];

		$formatterUrl = $this->getStringFromStatements(
			PropertyInfoLookup::KEY_FORMATTER_URL,
			$property->getStatements()
		);
		if ( $formatterUrl !== null ) {
			$info[PropertyInfoLookup::KEY_FORMATTER_URL] = $formatterUrl;
		}

		$canonicalUri = $this->getStringFromStatements(
			PropertyInfoStore::KEY_CANONICAL_URI,
			$property->getStatements()
		);
		if ( $canonicalUri !== null ) {
			$info[PropertyInfoStore::KEY_CANONICAL_URI] = $canonicalUri;
		}

		return $info;
	}

	/**
	 * @param string $propertyInfoKey
	 * @param StatementList $statements
	 *
	 * @return string The string value of the property associated with the given
	 *         $propertyInfoKey via the array provided to the constructor.
	 */
	private function getStringFromStatements( $propertyInfoKey, StatementList $statements ) {
		if ( !isset( $this->propertyIdMap[$propertyInfoKey] ) ) {
			return null;
		}

		$propertyId = $this->propertyIdMap[$propertyInfoKey];

		$bestStatements = $statements->getByPropertyId( $propertyId )->getBestStatements();
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

	/**
	 * @return NumericPropertyId[]
	 */
	public function getPropertyIdMap() {
		return $this->propertyIdMap;
	}

}
