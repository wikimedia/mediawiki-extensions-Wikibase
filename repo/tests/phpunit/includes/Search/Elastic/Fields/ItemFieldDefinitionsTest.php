<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\Fields\DescriptionsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\ItemFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;
use Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField;
use Wikibase\Repo\Search\Elastic\Fields\StatementCountField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\ItemFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 */
class ItemFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];

		$fieldDefinitions = new ItemFieldDefinitions(
			$this->newLabelsProviderFieldDefinitions( $languageCodes ),
			$this->newDescriptionsProviderFieldDefinitions( $languageCodes )
		);

		$fields = $fieldDefinitions->getFields();

		$this->assertArrayHasKey( 'label_count', $fields );
		$this->assertInstanceOf( LabelCountField::class, $fields['label_count'] );

		$this->assertArrayHasKey( 'sitelink_count', $fields );
		$this->assertInstanceOf( SiteLinkCountField::class, $fields['sitelink_count'] );

		$this->assertArrayHasKey( 'statement_count', $fields );
		$this->assertInstanceOf( StatementCountField::class, $fields['statement_count'] );
	}

	private function newLabelsProviderFieldDefinitions( array $languageCodes ) {
		return new LabelsProviderFieldDefinitions(
			$languageCodes
		);
	}

	private function newDescriptionsProviderFieldDefinitions( array $languageCodes ) {
		return new DescriptionsProviderFieldDefinitions(
			$languageCodes
		);
	}

}
