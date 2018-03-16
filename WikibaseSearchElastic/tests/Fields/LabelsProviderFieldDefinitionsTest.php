<?php

namespace WikibaseSearchElastic\Tests\Fields;

use CirrusSearch;
use WikibaseSearchElastic\Fields\AllLabelsField;
use WikibaseSearchElastic\Fields\LabelCountField;
use WikibaseSearchElastic\Fields\LabelsField;
use WikibaseSearchElastic\Fields\LabelsProviderFieldDefinitions;
use WikibaseSearchElastic\Tests\Fields\SearchFieldTestCase;

/**
 * @covers \WikibaseSearchElastic\Fields\LabelsProviderFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class LabelsProviderFieldDefinitionsTest extends SearchFieldTestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new LabelsProviderFieldDefinitions(
			$languageCodes
		);

		$fields = $fieldDefinitions->getFields();
		$this->assertArrayHasKey( 'label_count', $fields );
		$this->assertInstanceOf( \WikibaseSearchElastic\Fields\LabelCountField::class, $fields['label_count'] );
		$this->assertArrayHasKey( 'labels', $fields );
		$this->assertInstanceOf( \WikibaseSearchElastic\Fields\LabelsField::class, $fields['labels'] );
		$this->assertArrayHasKey( 'labels_all', $fields );
		$this->assertInstanceOf( AllLabelsField::class, $fields['labels_all'] );

		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}
		$searchEngine = $this->getSearchEngineMock();

		$mapping = $fields['labels']->getMapping( $searchEngine );
		$this->assertEquals( $languageCodes, array_keys( $mapping['properties'] ) );
	}

}
