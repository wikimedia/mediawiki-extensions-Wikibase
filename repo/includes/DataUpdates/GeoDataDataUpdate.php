<?php

namespace Wikibase\Repo\DataUpdates;

use Coord;
use CoordinatesOutput;
use ParserOutput;
use RuntimeException;
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
	 * @var Statement[]
	 */
	private $statementsByGeoProperty = array();

	/**
	 * @var string[]
	 */
	private $preferredProperties;

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
			$serializedId = $propertyId->getSerialization();

			if ( !array_key_exists( $serializedId, $this->statementsByGeoProperty ) ) {
				$this->statementsByGeoProperty[$serializedId] = new StatementList();
			}

			$this->statementsByGeoProperty[$serializedId]->addStatement( $statement );
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		if ( $this->statementsByGeoProperty === array() ) {
			return;
		}

		$coordinatesOutput = new CoordinatesOutput();

		$secondaryCoordinates = $this->extractMainSnakCoords();
		$primaryCoordinate = $this->findPrimaryCoordinate( $secondaryCoordinates );

		if ( $primaryCoordinate !== null ) {
			$primaryCoordinate->primary = true;
			$coordinatesOutput->addPrimary( $primaryCoordinate );
		}

		foreach ( $secondaryCoordinates as $coordinate ) {
			$coordinatesOutput->addSecondary( $coordinate );
		}

		$parserOutput->geoData = $coordinatesOutput;
	}

	/**
	 * @param Coord[] &$secondaryCoordinates Primary coordinate gets removed.
	 *
	 * @return Coord|null
	 */
	private function findPrimaryCoordinate( array &$secondaryCoordinates ) {
		foreach ( $this->preferredProperties as $propertyId ) {
			$primaryCoordinate = null;

			if ( array_key_exists( $propertyId, $this->statementsByGeoProperty ) ) {
				$bestStatements = $this->statementsByGeoProperty[$propertyId]->getBestStatements();

				// maybe the only statements have deprecated rank
				if ( $bestStatements->isEmpty() ) {
					continue;
				}

				foreach ( $bestStatements as $bestStatement ) {
					if ( $primaryCoordinate instanceof Coord ) {
						// already set and there are multiple best statements, so
						// can't just (somewhat) arbitrarily pick one. Instead, don't
						// mark any as primary and consider them all as secondary.
						return null;
					}

					$primaryCoordinate = $this->extractMainSnakCoord( $bestStatement );
					$guid = $bestStatement->getGuid();
				}
			}

			// primary coordinate is only primary and not secondary
			unset( $secondaryCoordinates[$guid] );

			return $primaryCoordinate;
		}

		return null;
	}

	/**
	 * @return Coord[]
	 */
	private function extractMainSnakCoords() {
		$coordinates = array();

		foreach ( $this->statementsByGeoProperty as $propertyId => $statements ) {
			foreach ( $statements as $statement ) {
				$coord = $this->extractMainSnakCoord( $statement );

				if ( $coord instanceof Coord ) {
					$guid = $statement->getGuid();
					$coordinates[$guid] = $coord;
				}
			}
		}

		return $coordinates;
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

		return $this->extractCoordFromSnak( $snak );
	}

	/**
	 * @param Snak $snak
	 *
	 * @return Coord
	 */
	private function extractCoordFromSnak( Snak $snak ) {
		$dataValue = $snak->getDataValue();

		return new Coord( $dataValue->getLatitude(), $dataValue->getLongitude() );
	}

}
