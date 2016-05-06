<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
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

	public function testGetMappingProperties() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new DescriptionsProviderFieldDefinitions( $languageCodes );

		$expected = [
			'description_ar' => [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			],
			'description_es' => [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getMappingProperties() );
	}

}
