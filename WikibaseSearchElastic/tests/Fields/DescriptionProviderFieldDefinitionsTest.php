<?php

namespace WikibaseSearchElastic\Tests\Fields;

use CirrusSearch;
use WikibaseSearchElastic\Fields\DescriptionsField;
use WikibaseSearchElastic\Fields\DescriptionsProviderFieldDefinitions;
use WikibaseSearchElastic\Tests\Fields\SearchFieldTestCase;

/**
 * @covers \WikibaseSearchElastic\Fields\DescriptionsProviderFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 */
class DescriptionProviderFieldDefinitionsTest extends SearchFieldTestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new \WikibaseSearchElastic\Fields\DescriptionsProviderFieldDefinitions(
			$languageCodes, []
		);

		$fields = $fieldDefinitions->getFields();
		$this->assertArrayHasKey( 'descriptions', $fields );
		$this->assertInstanceOf( DescriptionsField::class, $fields['descriptions'] );

		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}
		$searchEngine = $this->getSearchEngineMock();

		$mapping = $fields['descriptions']->getMapping( $searchEngine );
		$this->assertEquals( $languageCodes, array_keys( $mapping['properties'] ) );
	}

}
