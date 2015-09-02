<?php


namespace Wikibase;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Class to build the information about a property.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyInfoBuilder {

	/**
	 * @var PropertyId|null
	 */
	private $formatterUrlProperty = null;

	/**
	 * @param PropertyId|null $formatterUrlProperty
	 */
	public function __construct( PropertyId $formatterUrlProperty = null ) {
		$this->formatterUrlProperty = $formatterUrlProperty;
	}

	/**
	 * @param Property $property
	 * @return array
	 */
	public function buildPropertyInfo( Property $property ) {
		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $property->getDataTypeId()
		);

		$formatterUrl = $this->getFormatterUrl( $property->getStatements() );
		if ( $formatterUrl !== null ) {
			$info[PropertyInfoStore::KEY_FORMATTER_URL] = $formatterUrl;
		}

		return $info;
	}

	/**
	 * @param StatementList $statements
	 * @return string|null
	 */
	private function getFormatterUrl( StatementList $statements ) {
		if ( $this->formatterUrlProperty === null ) {
			return null;
		}

		$bestStatements = $statements->getByPropertyId( $this->formatterUrlProperty )->getBestStatements();
		if ( $bestStatements->isEmpty() ) {
			return null;
		}

		/** @var Statement $statement */
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
