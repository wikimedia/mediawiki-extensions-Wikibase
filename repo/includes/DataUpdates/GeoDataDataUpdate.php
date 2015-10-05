<?php

namespace Wikibase\Repo\DataUpdates;

use Coord;
use CoordinatesOutput;
use ParserOutput;
use RuntimeException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;

/**
 * Extracts and stashes coordinates in ParserOutput for use by the
 * GeoData extension to populate the geo_tags table, and if using
 * the 'elastic' backend, then also adding coordinates to CirrusSearch.
 *
 * GeoData then provides API modules to get coordinates for pages,
 * and to find nearby pages to a requested location.
 *
 * This class uses the Coord and CoordinatesOutput classes from the
 * GeoData extension.
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataDataUpdate implements StatementDataUpdate {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var Coord[]
	 */
	private $coordinates = array();

	/**
	 * @param PropertyDataTypeMatcher $propertyDataTypeMatcher
	 * @throws RuntimeException
	 */
	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		if ( !class_exists( 'CoordinatesOutput' ) ) {
			throw new RuntimeException( 'GeoDataDataUpdate requires the GeoData extension '
				. 'to be enabled' );
		}

		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

	/**
	 * Extract globe-coordinate DataValues for storing in ParserOutput for GeoData.
	 */
	public function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			$this->extractCoordinatesFromSnak( $snak );
		}
	}

	/**
	 * @param Snak $snak
	 */
	private function extractCoordinatesFromSnak( Snak $snak ) {
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
