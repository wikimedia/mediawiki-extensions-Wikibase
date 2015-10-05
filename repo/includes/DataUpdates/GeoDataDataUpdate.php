<?php

namespace Wikibase\Repo\DataUpdates;

use Coord;
use CoordinatesOutput;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;
use Wikibase\Repo\DataUpdates\ParserOutputDataUpdatesFactory;
use Wikibase\Repo\DataUpdates\SnakDataUpdate;

/**
 * Extracts and stashes coordinates in ParserOutput for GeoData.
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataDataUpdate implements SnakDataUpdate {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var array
	 */
	private $coordinates = array();

	/**
	 * @param PropertyDataTypeMatcher $propertyDataTypeMatcher
	 */
	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

	/**
	 * Extract globe-coordinate DataValues for storing in ParserOutput for GeoData.
	 *
	 * @param Snak $snak
	 */
	public function processSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak &&
			$this->propertyDataTypeMatcher->isMatchingDataType(
				$snak->getPropertyId(),
				'globe-coordinate'
			)
		) {
			$dataValue = $snak->getDataValue();
			$this->coordinates[] = new Coord( $dataValue->getLatitude(), $dataValue->getLongitude() );
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		$coordinatesOutput = new CoordinatesOutput();

		// @todo we probably want to refine how primary is set (e.g. only for specified
		// properties per some config?) and only preferred values if there are multiple
		// but one is marked as preferred.
		if ( count( $this->coordinates ) === 1 ) {
			$coordinates = $this->coordinates[0];
			$coordinates->primary = true;

			$coordinatesOutput->addPrimary( $coordinates );
		} else {
			foreach ( $this->coordinates as $coordinate ) {
				$coordinatesOutput->addSecondary( $coordinate );
			}
		}

		$parserOutput->geoData = $coordinatesOutput;
	}

}
