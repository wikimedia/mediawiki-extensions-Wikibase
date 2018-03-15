<?php

namespace Wikibase\Test;

use MediaWiki\Sparql\SparqlClient;
use Wikibase\AddUnitConversions;
use Wikibase\Lib\Units\UnitConverter;

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
