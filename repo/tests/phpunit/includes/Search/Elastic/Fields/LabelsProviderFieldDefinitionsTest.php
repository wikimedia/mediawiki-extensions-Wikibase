<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use Wikibase\Repo\Search\Elastic\Fields\AllLabelsField;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;
use Wikibase\Repo\Search\Elastic\Fields\LabelsField;
use Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
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
		$this->assertInstanceOf( LabelCountField::class, $fields['label_count'] );
		$this->assertArrayHasKey( 'labels', $fields );
		$this->assertInstanceOf( LabelsField::class, $fields['labels'] );
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
