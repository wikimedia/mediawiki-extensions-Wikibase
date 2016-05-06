<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\TermSearchFieldDefinition;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\DescriptionsProviderFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\DescriptionsProviderFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DescriptionsProviderFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new DescriptionsProviderFieldDefinitions(
			new TermSearchFieldDefinition(),
			$languageCodes
		);

		$expected = [
			'description_ar' => [
				'type' => 'string'
			],
			'description_es' => [
				'type' => 'string'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getFields() );
	}

}
