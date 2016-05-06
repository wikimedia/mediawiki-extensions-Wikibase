<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\LabelsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\TermSearchFieldDefinition;

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
		$fieldDefinitions = new LabelsProviderFieldDefinitions(
			new TermSearchFieldDefinition(),
			$languageCodes
		);

		$expected = [
			'label_ar' => [
				'type' => 'string'
			],
			'label_es' => [
				'type' => 'string'
			],
			'label_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getFields() );
	}

}
