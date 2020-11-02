<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\Geo\Values\GlobeCoordinateValue;
use ExtensionRegistry;
use GeoData\Coord;
use GeoData\CoordinatesOutput;
use ParserOutput;
use RuntimeException;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Extracts and stashes coordinates from Statement main snaks and
 * adds to ParserOutput for use by the GeoData extension.
 *
 * GeoData populates the geo_tags table, and if using
 * the 'elastic' backend, also adds coordinates to CirrusSearch.
 * GeoData then provides API modules to get coordinates for pages,
 * and to find nearby pages to a requested location.
 *
 * This class uses the Coord and CoordinatesOutput classes from the
 * GeoData extension.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataDataUpdater implements StatementDataUpdater {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var string[]
	 */
	private $preferredPropertiesIds;

	/**
	 * @var string[]
	 */
	private $globeUris;

	/**
	 * @var array[]
	 */
	private $coordinates = [];

	/**
	 * @param PropertyDataTypeMatcher $propertyDataTypeMatcher
	 * @param string[] $preferredPropertiesIds
	 * @param string[] $globeUris
	 *
	 * @throws RuntimeException
	 */
	public function __construct(
		PropertyDataTypeMatcher $propertyDataTypeMatcher,
		array $preferredPropertiesIds,
		array $globeUris
	) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'GeoData' ) ) {
			throw new RuntimeException( 'GeoDataDataUpdater requires the GeoData extension '
				. 'to be enabled' );
		}

		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
		$this->preferredPropertiesIds = $preferredPropertiesIds;
		$this->globeUris = $globeUris;
	}

	/**
	 * Extract globe-coordinate DataValues for storing in ParserOutput for GeoData.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		$propertyId = $statement->getPropertyId();

		if ( $this->propertyDataTypeMatcher->isMatchingDataType(
			$propertyId,
			'globe-coordinate'
		) ) {
			$rank = $statement->getRank();

			if ( $rank !== Statement::RANK_DEPRECATED ) {
				$coordinate = $this->extractMainSnakCoord( $statement );

				if ( $coordinate instanceof Coord ) {
					$key = $this->makeCoordinateKey( $propertyId->getSerialization(), $rank );
					$this->coordinates[$key][] = $coordinate;
				}
			}
		}
	}

	public function updateParserOutput( ParserOutput $parserOutput ) {
		$coordinatesOutput = CoordinatesOutput::getOrBuildFromParserOutput( $parserOutput );
		$primaryCoordKey = $this->findPrimaryCoordinateKey();

		if ( !$coordinatesOutput->hasPrimary() && $primaryCoordKey !== null ) {
			$this->addPrimaryCoordinate( $coordinatesOutput, $primaryCoordKey );
		}

		$this->addSecondaryCoordinates( $coordinatesOutput, $primaryCoordKey );
		$coordinatesOutput->setToParserOutput( $parserOutput );
	}

	/**
	 * @return string|null Array key for Coord selected as primary.
	 */
	private function findPrimaryCoordinateKey() {
		foreach ( $this->preferredPropertiesIds as $id ) {
			$key = $this->makeCoordinateKey( $id, Statement::RANK_PREFERRED );
			$preferredCount = $this->getCoordinatesGroupCount( $key );

			if ( $preferredCount === 1 ) {
				return $key;
			} elseif ( $preferredCount === 0 ) {
				$key = $this->makeCoordinateKey( $id, Statement::RANK_NORMAL );
				$normalCount = $this->getCoordinatesGroupCount( $key );

				if ( $normalCount === 1 ) {
					return $key;
				} elseif ( $normalCount > 1 ) {
					// multiple normal coordinates
					return null;
				}
			} else {
				// multiple preferred coordinates
				return null;
			}
		}

		return null;
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 */
	private function getCoordinatesGroupCount( $key ) {
		if ( isset( $this->coordinates[$key] ) ) {
			return count( $this->coordinates[$key] );
		}

		return 0;
	}

	/**
	 * @param CoordinatesOutput $coordinatesOutput
	 * @param string $primaryCoordKey
	 */
	private function addPrimaryCoordinate(
		CoordinatesOutput $coordinatesOutput,
		$primaryCoordKey
	) {
		$primaryCoordinate = $this->coordinates[$primaryCoordKey][0];
		$primaryCoordinate->primary = true;

		$coordinatesOutput->addPrimary( $primaryCoordinate );
	}

	/**
	 * @param CoordinatesOutput $coordinatesOutput
	 * @param string|null $primaryCoordKey
	 */
	private function addSecondaryCoordinates(
		CoordinatesOutput $coordinatesOutput,
		$primaryCoordKey
	) {
		foreach ( $this->coordinates as $key => $coords ) {
			if ( $key !== $primaryCoordKey ) {
				foreach ( $coords as $coord ) {
					$coordinatesOutput->addSecondary( $coord );
				}
			}
		}
	}

	/**
	 * @param string $propertyIdString
	 * @param int $rank
	 *
	 * @return string
	 */
	private function makeCoordinateKey( $propertyIdString, $rank ) {
		return $propertyIdString . '|' . $rank;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return Coord|null
	 */
	private function extractMainSnakCoord( Statement $statement ) {
		$snak = $statement->getMainSnak();

		if ( !( $snak instanceof PropertyValueSnak ) ) {
			return null;
		}

		$dataValue = $snak->getDataValue();

		if ( !( $dataValue instanceof GlobeCoordinateValue ) ) {
			// Property data type - value mismatch
			return null;
		}

		$globeUri = $dataValue->getGlobe();

		if ( !isset( $this->globeUris[$globeUri] ) ) {
			// Unknown globe
			return null;
		}

		return new Coord(
			$dataValue->getLatitude(),
			$dataValue->getLongitude(),
			$this->globeUris[$globeUri]
		);
	}

}
