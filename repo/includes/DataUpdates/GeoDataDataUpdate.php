<?php

namespace Wikibase\Repo\DataUpdates;

use Coord;
use CoordinatesOutput;
use DataValues\Geo\Values\GlobeCoordinateValue;
use ParserOutput;
use RuntimeException;
use UnexpectedValueException;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;

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
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class GeoDataDataUpdate implements StatementDataUpdate {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var string[]
	 */
	private $preferredProperties;

	/**
	 * @var Coord[]
	 */
	private $coordinates = array();

	/**
	 * @param PropertyDataTypeMatcher $propertyDataTypeMatcher
	 * @param string[] $preferredProperties
	 * @throws RuntimeException
	 */
	public function __construct(
		PropertyDataTypeMatcher $propertyDataTypeMatcher,
		array $preferredProperties
	) {
		if ( !class_exists( 'GeoData' ) ) {
			throw new RuntimeException( 'GeoDataDataUpdate requires the GeoData extension '
				. 'to be enabled' );
		}

		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
		$this->preferredProperties = $preferredProperties;
	}

	/**
	 * Extract globe-coordinate DataValues for storing in ParserOutput for GeoData.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		$propertyId = $statement->getMainSnak()->getPropertyId();

		if ( $this->propertyDataTypeMatcher->isMatchingDataType(
			$propertyId,
			'globe-coordinate'
		) ) {
			$rank = $statement->getRank();

			if ( $rank > Statement::RANK_DEPRECATED ) {
				$coordinate = $this->extractMainSnakCoord( $statement );

				if ( $coordinate instanceof Coord ) {
					$key = $this->makeCoordinateKey( $propertyId->getSerialization(), $rank );
					$this->coordinates[$key][] = $coordinate;
				}
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		$coordinatesOutput = isset( $parserOutput->geoData ) ?: new CoordinatesOutput();

		if ( $coordinatesOutput->getPrimary() === false ) {
			$primaryCoordKey = $this->addPrimaryCoordinate( $coordinatesOutput );
		}

		foreach ( $this->coordinates as $key => $coordinates ) {
			if ( $key !== $primaryCoordKey ) {
				foreach ( $coordinates as $coordinate ) {
					$coordinatesOutput->addSecondary( $coordinate );
				}
			}
		}

		$parserOutput->geoData = $coordinatesOutput;
	}

	/**
	 * @param CoordinatesOutput $coordinatesOutput
	 *
	 * @return string|null Array key for Coord selected as primary.
	 */
	private function addPrimaryCoordinate( CoordinatesOutput $coordinatesOutput ) {
		foreach ( $this->preferredProperties as $propertyIdString ) {
			$key = $this->makeCoordinateKey( $propertyIdString, Statement::RANK_PREFERRED );

			$preferred = isset( $this->coordinates[$key] ) ? $this->coordinates[$key] : array();
			$preferredCount = count( $preferred );

			if ( $preferredCount === 1 ) {
				$primaryCoordinate = $preferred[0];
			} elseif ( $preferredCount === 0 ) {
				$key = $this->makeCoordinateKey( $propertyIdString, Statement::RANK_NORMAL );
				$normal = isset( $this->coordinates[$key] ) ? $this->coordinates[$key] : array();
				$normalCount = count( $normal );

				if ( $normalCount === 1 ) {
					$primaryCoordinate = $normal[0];
				} elseif ( $normalCount > 1 ) {
					// multiple normal coordinates
					return null;
				}
			} else {
				// multiple preferred coordinates
				return null;
			}

			if ( isset( $primaryCoordinate ) ) {
				$primaryCoordinate->primary = true;
				$coordinatesOutput->addPrimary( $primaryCoordinate );

				return $key;
			}
		}

		return null;
	}

	/**
	 * @param string $propertyIdString
	 * @param int $rank
	 *
	 * @return string
	 */
	private function makeCoordinateKey( $propertyIdString, $rank ) {
		return $propertyIdString . '-' . $rank;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return Coord|null
	 */
	private function extractMainSnakCoord( Statement $statement ) {
		$snak = $statement->getMainSnak();

		if ( !$snak instanceof PropertyValueSnak ) {
			return null;
		}

		$dataValue = $snak->getDataValue();

		if ( !$dataValue instanceof GlobeCoordinateValue ) {
			// Property data type - value mismatch
			return null;
		}

		return new Coord( $dataValue->getLatitude(), $dataValue->getLongitude() );
	}

}
