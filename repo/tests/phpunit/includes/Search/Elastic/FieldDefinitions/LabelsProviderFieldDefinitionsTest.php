<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\LabelsProviderFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\LabelsProviderFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelsProviderFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new LabelsProviderFieldDefinitions( $languageCodes );

		$expected = [
			'label_ar' => [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			],
			'label_es' => [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			],
			'label_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getFields() );
	}

}
