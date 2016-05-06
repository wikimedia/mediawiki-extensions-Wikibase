<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\PropertyFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\PropertyFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new PropertyFieldDefinitions( $languageCodes );

		$expected = [
			'label_ar' => [
				'type' => 'string'
			],
			'label_es' => [
				'type' => 'string'
			],
			'label_count' => [
				'type' => 'integer'
			],
			'description_ar' => [
				'type' => 'string'
			],
			'description_es' => [
				'type' => 'string'
			],
			'statement_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getFields() );
	}

}
