<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Repo\Search\Elastic\Fields\DescriptionsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\ItemFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\LabelCountField;
use Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField;
use Wikibase\Repo\Search\Elastic\Fields\StatementCountField;
use Wikibase\Repo\Search\Elastic\Fields\StatementProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\StatementsField;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Fields\ItemFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class ItemFieldDefinitionsTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];

		$fieldDefinitions = new ItemFieldDefinitions( [
			$this->newLabelsProviderFieldDefinitions( $languageCodes ),
			$this->newDescriptionsProviderFieldDefinitions( $languageCodes ),
			new StatementProviderFieldDefinitions( $this->getMock( PropertyDataTypeLookup::class ),
				[], [], [], [], [] ),
		] );

		$fields = $fieldDefinitions->getFields();

		$this->assertArrayHasKey( 'label_count', $fields );
		$this->assertInstanceOf( LabelCountField::class, $fields['label_count'] );

		$this->assertArrayHasKey( 'sitelink_count', $fields );
		$this->assertInstanceOf( SiteLinkCountField::class, $fields['sitelink_count'] );

		$this->assertArrayHasKey( 'statement_count', $fields );
		$this->assertInstanceOf( StatementCountField::class, $fields['statement_count'] );

		$this->assertArrayHasKey( 'statement_keywords', $fields );
		$this->assertInstanceOf( StatementsField::class, $fields['statement_keywords'] );
	}

	private function newLabelsProviderFieldDefinitions( array $languageCodes ) {
		return new LabelsProviderFieldDefinitions(
			$languageCodes
		);
	}

	private function newDescriptionsProviderFieldDefinitions( array $languageCodes ) {
		return new DescriptionsProviderFieldDefinitions(
			$languageCodes, []
		);
	}

}
