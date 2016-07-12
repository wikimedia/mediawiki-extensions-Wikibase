<?php
namespace Wikibase;

use DataValues\DecimalMath;
use DataValues\DecimalValue;
use Maintenance;
use Wikibase\Repo\Maintenance\SPARQLClient;
use Wikibase\Repo\WikibaseRepo;

$basePath =
	getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../../..';
require_once $basePath . '/maintenance/Maintenance.php';
require_once __DIR__ . '/SPARQLClient.php';

/**
 * Update the conversion table for units.
 * Base unit types for Wikidata:
 * Q223662,Q208469
 * SI base unit,SI derived unit
 * TODO: add support to non-SI units
 * @package Wikibase
 */
class UpdateUnits extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Update unit conversion table." );

		$this->addOption( 'base-unit-types', 'Types of base units.', true, true );
		$this->addOption( 'format', 'Output format, default is json.', false, true );
	}

	public function execute() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->error( "You need to have Wikibase enabled in order to use this maintenance script!",
				1 );
		}
		$format = $this->getOption( 'format', 'json' );
		if ( !is_callable( [ $this, 'format' . $format ] ) ) {
			$this->error( "Invalid format", 1 );
		}

		$repo = WikibaseRepo::getDefaultInstance();
		$endPoint = $repo->getSettings()->getSetting( 'sparqlEndpoint' );
		if ( !$endPoint ) {
			$this->error( 'SPARQL endpoint not defined', 1 );
		}
		$baseUri =
			WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'conceptBaseUri' );
		$client = new SPARQLClient( $endPoint, $baseUri );

		$types =
			str_replace( [ ',', 'Q' ], [ ' ', 'wd:Q' ], $this->getOption( 'base-unit-types' ) );

		$baseUnits =
			$client->getIDs( "SELECT ?item WHERE { VALUES ?class { $types } ?item wdt:P31 ?class }",
				"item" );
		// flip for better lookup
		$baseUnits = array_flip( $baseUnits );
		// Get units that have conversion to SI units
		$unitsQuery = <<<QUERY
SELECT REDUCED ?unit ?si ?siUnit ?unitLabel ?siUnitLabel WHERE {
  ?unit wdt:P31 ?type .
  ?type wdt:P279* wd:Q47574 .
  # Not a currency
  FILTER (?type != wd:Q8142)
  # Not a cardinal number
  FILTER NOT EXISTS { ?unit wdt:P31 wd:Q163875 }
  # Has conversion to SI Units
  ?unit p:P2370/psv:P2370 [ wikibase:quantityAmount ?si; wikibase:quantityUnit ?siUnit ] .
  SERVICE wikibase:label {
    bd:serviceParam wikibase:language "en" .
  }
# Enable this to select only units that are actually used
#  FILTER EXISTS { [] wikibase:quantityUnit ?unit }
}        
QUERY;
		$convertUnits = [];
		$reconvert = [];
		$units = $client->query( $unitsQuery );
		foreach ( $units as $unit ) {
			$unit['unit'] = substr( $unit['unit'], 31 );
			$unit['siUnit'] = substr( $unit['siUnit'], 31 );
			if ( isset( $convertUnits[$unit['unit']] ) ) {
				// done already
				continue;
			}
			if ( $unit['unit'] == $unit['siUnit'] ) {
				// base unit
				if ( $unit['si'] != 1 ) {
					$this->error( "Weird unit: {$unit['unit']} is {$unit['si']} of itself!" );
					continue;
				}
				if ( !isset( $baseUnits[$unit['siUnit']] ) ) {
					$this->error( "Weird unit: {$unit['unit']} is self-referring but not base!" );
					continue;
				}
			}
			if ( !isset( $baseUnits[$unit['siUnit']] ) ) {
				// base unit is not actually base
				$reconvert[$unit['unit']] = $unit;
				continue;
			}
			$convertUnits[$unit['unit']] =
				[
					'factor' => $unit['si'],
					'unit' => $unit['siUnit'],
					// These two are just for humans, not used by actual convertor
					'label' => $unit['unitLabel'],
					'siLabel' => $unit['siUnitLabel']
				];
		}

		$math = new DecimalMath();
		// try to convert some units that reduce to other units
		while ( $reconvert ) {
			$converted = false;
			foreach ( $reconvert as $name => $unit ) {
				if ( isset( $convertUnits[$unit['siUnit']] ) ) {
					// we have conversion now
					$newUnit = $convertUnits[$unit['siUnit']];
					$newFactor =
						$math->product( $this->makeDecimalValue( $unit['si'] ),
							$this->makeDecimalValue( $newUnit[0] ) );
					$convertUnits[$name] = [ trim( $newFactor->getValue(), '+' ), $newUnit[1] ];
					unset( $reconvert[$name] );
					$converted = true;
				}
			}
			// we didn't convert any on this step, no use to continue
			if ( !$converted ) {
				break;
			}
		}

		if ( $reconvert ) {
			// still have unconverted units
			foreach ( $reconvert as $name => $unit ) {
				$this->error( "Weird base unit: {$unit['unit']} reduces to {$unit['siUnit']} which is not base!" );
			}
		}
		$formatter = 'format' . $format;
		echo $this->$formatter( $convertUnits );
	}

	/**
	 * Format units as JSON
	 * @param $convertUnits
	 * @return string
	 */
	private function formatJSON( $convertUnits ) {
		return json_encode( $convertUnits, JSON_PRETTY_PRINT );
	}

	/**
	 * Format units as CSV
	 * @param $convertUnits
	 * @return string
	 */
	private function formatCSV( $convertUnits ) {
		$str = '';
		foreach ( $convertUnits as $name => $data ) {
			$str .= "$name,$data[0],$data[1]\n";
		}
		return $str;
	}

	/**
	 * Create DecimalValue from regular numeric string or value.
	 * @param int|float|string $number
	 * FIXME: replace with DecimalValue method from https://github.com/DataValues/Number/pull/67
	 * @return DecimalValue
	 */
	private function makeDecimalValue( $number ) {

		if ( is_string( $number ) && $number !== '' ) {
			if ( $number[0] !== '-' && $number[0] !== '+' ) {
				$number = '+' . $number;
			}
		}

		return new DecimalValue( $number );
	}

}

$maintClass = UpdateUnits::class;
require_once RUN_MAINTENANCE_IF_MAIN;
