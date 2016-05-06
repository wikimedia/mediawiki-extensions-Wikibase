<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\TermSearchFieldDefinition;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\TermSearchFieldDefinition
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSearchFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$fieldDefinition = new TermSearchFieldDefinition();

		$expected = [
			'type' => 'string'
		];

		$this->assertSame( $expected, $fieldDefinition->getMapping() );
	}

}
