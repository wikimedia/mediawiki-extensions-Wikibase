<?php
namespace Wikibase\Test;

use Wikibase\AddUnitConversions;
use Wikibase\Lib\UnitConverter;
use Wikibase\Repo\Maintenance\SPARQLClient;

class MockAddUnits extends AddUnitConversions {

	/**
	 * Output data.
	 * @var string
	 */
	public $output;

	/**
	 * Set SPARQL client.
	 * @param SPARQLClient $client
	 */
	public function setClient( SPARQLClient $client ) {
		$this->client = $client;
	}

	/**
	 * Set unit converter.
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
