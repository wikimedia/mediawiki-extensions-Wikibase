<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;
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

		// TODO: more testing will be done when next patch
		$fields = $fieldDefinitions->getFields();
		$this->assertArrayHasKey( 'label_count', $fields );
		$this->assertInstanceOf( LabelCountField::class, $fields['label_count'] );
	}

}
