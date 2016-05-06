<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\ItemFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\ItemFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new ItemFieldDefinitions( $languageCodes );

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
			'sitelink_count' => [
				'type' => 'integer'
			],
			'statement_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getFields() );
	}

}
