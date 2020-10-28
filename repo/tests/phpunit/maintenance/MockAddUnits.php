<?php

namespace Wikibase\Repo\Tests\Maintenance;

use MediaWiki\Sparql\SparqlClient;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Repo\Maintenance\AddUnitConversions;

// files in maintenance/ are not autoloaded to avoid accidental usage, so load explicitly
require_once __DIR__ . '/../../../maintenance/addUnitConversions.php';

/**
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class MockAddUnits extends AddUnitConversions {

	/**
	 * Output data.
	 * @var string
	 */
	public $output;

	/**
	 * Set SPARQL client.
	 * @param SparqlClient $client
	 */
	public function setClient( SparqlClient $client ) {
		$this->client = $client;
	}

	/**
	 * @param UnitConverter $uc
	 */
	public function setUnitConverter( UnitConverter $uc ) {
		$this->unitConverter = $uc;
	}

	protected function writeOut() {
		$data = $this->rdfWriter->drain();
		$this->output .= $data;
	}

	protected function output( $out, $channel = null ) {
	}

}
