<?php

namespace Wikibase\Repo\Maintenance;

use DataValues\DecimalMath;
use DataValues\DecimalValue;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Sparql\SparqlClient;
use Wikibase\Lib\WikibaseSettings;
use Wikibase\Repo\WikibaseRepo;

$basePath =
	getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';

/**
 * Generate the conversion table for units,
 * optionally filtered to units of a certain type.
 *
 * This script retrieves and emits the conversion data into coherent units.
 * For instance, the millimetre is equal to 1/1000 of the coherent unit metre
 * (also an SI base unit), while the ampere hour is equal to 3600 of the
 * coherent unit coulomb, the product of the SI base units ampere and second.
 *
 * Example usage:
 * mwscript extensions/WikidataBuildResources/extensions/Wikibase/repo/maintenance/updateUnits.php
 *   --wiki wikidatawiki --base-uri http://www.wikidata.org/entity/
 *   --unit-class Q1978718 > unitConversion.json
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class UpdateUnits extends Maintenance {

	/**
	 * @var string
	 */
	private $baseUri;

	/**
	 * Length of the base URI.
	 * Helper variable to speed up cutting it out.
	 * @var int
	 */
	private $baseLen;

	/**
	 * @var SparqlClient
	 */
	private $client;

	/**
	 * Should we silence the error output for tests?
	 * @var bool
	 */
	public $silent;

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Generate unit conversion table, optionally filtered by type." );

		$this->addOption( 'base-uri', 'Base URI for the data.', false, true );
		$this->addOption( 'unit-class', 'Class for units.', false, true );
		$this->addOption( 'format', 'Output format "json" (default) or "csv".', false, true );
		$this->addOption( 'sparql', 'SPARQL endpoint URL.', false, true );
		$this->addOption( 'check-usage', 'Check whether unit is in use?', false );
	}

	public function execute() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->fatalError( "You need to have Wikibase enabled in order to use this maintenance script!" );
		}
		$format = $this->getOption( 'format', 'json' );
		$checkUsage = $this->hasOption( 'check-usage' );

		$repoSettings = WikibaseRepo::getSettings();
		$endPoint = $this->getOption( 'sparql',
			$repoSettings->getSetting( 'sparqlEndpoint' ) );
		if ( !$endPoint ) {
			$this->fatalError( 'SPARQL endpoint not defined' );
		}
		$this->setBaseUri( $this->getOption( 'base-uri', WikibaseRepo::getItemVocabularyBaseUri() ) );
		$this->client = new SparqlClient( $endPoint, MediaWikiServices::getInstance()->getHttpRequestFactory() );
		$this->client->appendUserAgent( __CLASS__ );

		$unitClass = $this->getOption( 'unit-class' );
		if ( $unitClass ) {
			$filter = "FILTER EXISTS { ?unit wdt:P31/wdt:P279* wd:$unitClass }\n";
		} else {
			$filter = '';
		}

		// Get units usage stats. We don't care about units
		// That have been used less than 10 times, for now
		if ( $checkUsage ) {
			$unitUsage = $this->getUnitUsage( 10 );
		} else {
			$unitUsage = null;
		}
		$coherentUnits = $this->getCoherentUnits( $filter );

		$convertUnits = [];
		$reconvert = [];

		if ( $checkUsage ) {
			$filter .= "FILTER EXISTS { [] wikibase:quantityUnit ?unit }\n";
		}

		$convertableUnits = $this->getConvertableUnits( $filter );
		foreach ( $convertableUnits as $unit ) {
			$converted =
				$this->convertUnit( $unit, $convertUnits, $coherentUnits, $unitUsage, $reconvert );
			if ( $converted ) {
				$unitName = substr( $unit['unit'], $this->baseLen );
				$convertUnits[$unitName] = $converted;
			}
		}

		$this->reduceUnits( $reconvert, $convertUnits );

		// Add coherent units
		foreach ( $coherentUnits as $coherentUnitName => $coherentUnitData ) {
			$convertUnits[$coherentUnitName] = [
				'factor' => "1",
				'unit' => $coherentUnitName,
				'label' => $coherentUnitData['unitLabel'],
				'siLabel' => $coherentUnitData['unitLabel'],
			];
		}

		// Sort units by Q-id, as number, to have predictable order
		uksort( $convertUnits,
			function ( $x, $y ) {
				return (int)substr( $x, 1 ) - (int)substr( $y, 1 );
			}
		);

		switch ( strtolower( $format ) ) {
			case 'csv':
				echo $this->formatCSV( $convertUnits );
				break;
			case 'json':
				echo $this->formatJSON( $convertUnits );
				break;
			default:
				$this->fatalError( 'Invalid format' );
		}
	}

	/**
	 * Reduce units that are not in term of coherent units into coherent units.
	 * If some units are not reducible to coherent units, warnings are issued.
	 * @param array $reconvert List of units to be reduced
	 * @param array &$convertUnits List of unit conversion configs, will be modified if
	 *                             it is possible to reduce the unit to coherent units.
	 */
	private function reduceUnits( $reconvert, &$convertUnits ) {
		while ( $reconvert ) {
			$converted = false;
			foreach ( $reconvert as $name => $unit ) {
				$convertedUnit = $this->convertDerivedUnit( $unit, $convertUnits );
				if ( $convertedUnit ) {
					$convertUnits[$name] = $convertedUnit;
					unset( $reconvert[$name] );
					$converted = true;
				}
			}
			// we didn't convert any on this step, no use to continue
			// This loop will converge since on each step we will reduce
			// the length of $reconvert until we can't do it anymore.
			if ( !$converted ) {
				break;
			}
		}

		if ( $reconvert ) {
			// still have unconverted units
			foreach ( $reconvert as $name => $unit ) {
				$this->error( "Weird conversion: {$unit['unit']} reduces to {$unit['siUnit']} which is not coherent!" );
			}
		}
	}

	/**
	 * @param string $uri
	 */
	public function setBaseUri( $uri ) {
		$this->baseUri = $uri;
		$this->baseLen = strlen( $uri );
	}

	/**
	 * Convert unit that does not reduce to a coherent unit.
	 *
	 * @param string[] $unit
	 * @param array[] $convertUnits List of units already converted
	 *
	 * @return string[]|null Converted data for the unit or null if no conversion possible.
	 */
	public function convertDerivedUnit( $unit, $convertUnits ) {
		if ( isset( $convertUnits[$unit['siUnit']] ) ) {
			// we have conversion now
			$math = new DecimalMath();
			$newUnit = $convertUnits[$unit['siUnit']];
			$newFactor =
				$math->product( new DecimalValue( $unit['si'] ),
					new DecimalValue( $newUnit['factor'] ) );
			return [
				'factor' => trim( $newFactor->getValue(), '+' ),
				'unit' => $newUnit['unit'],
				'label' => $unit['unitLabel'],
				'siLabel' => $newUnit['siLabel'],
			];
		}
		return null;
	}

	/**
	 * Create conversion data for a single unit.
	 * @param string[] $unit Unit data
	 * @param string[] $convertUnits Already converted data
	 * @param array[] $coherentUnits Ultimate target units
	 * @param string[]|null $unitUsage Unit usage data
	 * @param string[][] &$reconvert Array collecting units that require re-conversion later,
	 *                 due to their target unit not being coherent.
	 * @return string[]|null Produces conversion data for the unit or null if not possible.
	 */
	public function convertUnit( $unit, $convertUnits, $coherentUnits, $unitUsage, &$reconvert ) {
		$unit['unit'] = substr( $unit['unit'], $this->baseLen );
		$unit['siUnit'] = substr( $unit['siUnit'], $this->baseLen );

		if ( isset( $convertUnits[$unit['unit']] ) ) {
			// done already
			return null;
		}
		if ( $unit['unit'] == $unit['siUnit'] ) {
			// coherent unit
			if ( $unit['si'] != 1 ) {
				$this->error( "Weird unit: {$unit['unit']} is {$unit['si']} of itself!" );
				return null;
			}
			if ( !isset( $coherentUnits[$unit['siUnit']] ) ) {
				$this->error( "Weird unit: {$unit['unit']} is self-referring but not coherent!" );
				return null;
			}
		}

		if ( $unitUsage && !isset( $coherentUnits[$unit['unit']] ) && !isset( $unitUsage[$unit['unit']] ) ) {
			$this->error( "Low usage unit {$unit['unit']}, skipping..." );
			return null;
		}

		if ( !isset( $coherentUnits[$unit['siUnit']] ) ) {
			// target unit is not actually coherent
			$reconvert[$unit['unit']] = $unit;
		} else {
			return [
				'factor' => $unit['si'],
				'unit' => $unit['siUnit'],
				// These two are just for humans, not used by actual converter
				'label' => $unit['unitLabel'],
				'siLabel' => $unit['siUnitLabel'],
			];
		}

		return null;
	}

	/**
	 * Format units as JSON
	 * @param array[] $convertUnits
	 * @return string
	 */
	private function formatJSON( array $convertUnits ) {
		return json_encode( $convertUnits, JSON_PRETTY_PRINT );
	}

	/**
	 * Get units that are used at least $min times.
	 * We don't care about units that have been used less than 10 times, for now.
	 * Only top 200 will be returned (though so far we don't have that many).
	 * @param int $min Minimal usage for the unit.
	 * @return string[] Array of ['unit' => Q-id, 'c' => count]
	 */
	private function getUnitUsage( $min ) {
		$usageQuery = <<<UQUERY
SELECT ?unit (COUNT(DISTINCT ?v) as ?c) WHERE {
  ?v wikibase:quantityUnit ?unit .
  ?s ?p ?v .
  FILTER(?unit != wd:Q199)
# Exclude currencies
  FILTER NOT EXISTS { ?unit wdt:P31+ wd:Q8142 }
} GROUP BY ?unit
  HAVING(?c >= $min)
  ORDER BY DESC(?c)
  LIMIT 200
UQUERY;
		$unitUsage = $this->getIDs( $usageQuery, 'unit' );
		$unitUsage = array_flip( $unitUsage );
		return $unitUsage;
	}

	/**
	 * Get list of IDs from SPARQL.
	 * @param string $sparql Query
	 * @param string $item Variable name where IDs are stored
	 * @return string[] List of entity ID strings
	 */
	private function getIDs( $sparql, $item ) {
		$data = $this->client->query( $sparql );
		if ( $data ) {
			return array_map( function ( $row ) use ( $item ) {
				return str_replace( $this->baseUri, '', $row[$item] );
			}, $data );
		}
		return [];
	}

	/**
	 * Get coherent units (those with a conversion factor of 1 to themselves).
	 * @param string $filter Unit filter
	 * @return array[]
	 */
	private function getCoherentUnits( $filter ) {
		$query = <<<QUERY
SELECT ?unit ?unitLabel WHERE {
  ?unit wdt:P31 wd:Q69197847 .
  $filter
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language "en" .
  }
}
QUERY;
		$coherentUnitsData = $this->client->query( $query );
		'@phan-var array[] $coherentUnitsData';
		$coherentUnits = [];
		// arrange better lookup
		foreach ( $coherentUnitsData as $coherent ) {
			$item = substr( $coherent['unit'], $this->baseLen );
			$coherentUnits[$item] = $coherent;
		}
		return $coherentUnits;
	}

	/**
	 * Retrieve the list of convertable units.
	 * @param string $filter
	 * @return array[]|false List of units that can be converted
	 */
	private function getConvertableUnits( $filter ) {
		$unitsQuery = <<<QUERY
SELECT REDUCED ?unit ?si ?siUnit ?unitLabel ?siUnitLabel WHERE {
  ?unit wdt:P31 ?type .
  ?type wdt:P279* wd:Q47574 .
  # Not a currency
  FILTER (?type != wd:Q8142)
  # Not a cardinal number
  FILTER NOT EXISTS { ?unit wdt:P31 wd:Q163875 }
  $filter
  # Has conversion to SI Units
  ?unit p:P2370/psv:P2370 [ wikibase:quantityAmount ?si; wikibase:quantityUnit ?siUnit ] .
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language "en" .
  }
# Enable this to select only units that are actually used
}
QUERY;
		return $this->client->query( $unitsQuery );
	}

	/**
	 * Format units as CSV
	 * @param array[] $convertUnits
	 * @return string
	 */
	private function formatCSV( array $convertUnits ) {
		$str = '';
		foreach ( $convertUnits as $name => $data ) {
			$str .= "$name,{$data['unit']},{$data['factor']}\n";
		}
		return $str;
	}

	/**
	 * @param string $err
	 * @param int $die If > 0, go ahead and die out using this int as the code
	 */
	protected function error( $err, $die = 0 ) {
		if ( !$this->silent ) {
			parent::error( $err, $die );
		} elseif ( $die > 0 ) {
			die( $die );
		}
	}

}

$maintClass = UpdateUnits::class;
require_once RUN_MAINTENANCE_IF_MAIN;
