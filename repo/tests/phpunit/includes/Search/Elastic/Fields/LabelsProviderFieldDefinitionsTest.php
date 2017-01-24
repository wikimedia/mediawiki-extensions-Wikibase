<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\Fields\AllLabelsField;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;
use Wikibase\Repo\Search\Elastic\Fields\LabelsField;
use Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 */
class LabelsProviderFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

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

		$searchEngine = $this->getMockBuilder( 'CirrusSearch' )->getMock();
		$searchEngine->expects( $this->never() )->method( 'makeSearchFieldMapping' );

		$mapping = $fields['labels']->getMapping( $searchEngine );
		$this->assertEquals( $languageCodes, array_keys( $mapping['properties'] ) );
	}

}
