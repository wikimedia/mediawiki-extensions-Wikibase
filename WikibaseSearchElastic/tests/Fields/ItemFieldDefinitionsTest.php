<?php

namespace WikibaseSearchElastic\Tests\Fields;

use PHPUnit_Framework_TestCase;
use WikibaseSearchElastic\Fields\DescriptionsProviderFieldDefinitions;
use WikibaseSearchElastic\Fields\ItemFieldDefinitions;
use WikibaseSearchElastic\Fields\LabelCountField;
use WikibaseSearchElastic\Fields\LabelsProviderFieldDefinitions;
use WikibaseSearchElastic\Fields\SiteLinkCountField;
use WikibaseSearchElastic\Fields\StatementCountField;
use WikibaseSearchElastic\Fields\StatementProviderFieldDefinitions;
use WikibaseSearchElastic\Fields\StatementsField;

/**
 * @covers \WikibaseSearchElastic\Fields\ItemFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Stas Malyshev
 */
class ItemFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$languageCodes = [ 'ar', 'es' ];

		$fieldDefinitions = new ItemFieldDefinitions( [
			$this->newLabelsProviderFieldDefinitions( $languageCodes ),
			$this->newDescriptionsProviderFieldDefinitions( $languageCodes ),
			new StatementProviderFieldDefinitions( [], [] ),
		] );

		$fields = $fieldDefinitions->getFields();

		$this->assertArrayHasKey( 'label_count', $fields );
		$this->assertInstanceOf( \WikibaseSearchElastic\Fields\LabelCountField::class, $fields['label_count'] );

		$this->assertArrayHasKey( 'sitelink_count', $fields );
		$this->assertInstanceOf( \WikibaseSearchElastic\Fields\SiteLinkCountField::class, $fields['sitelink_count'] );

		$this->assertArrayHasKey( 'statement_count', $fields );
		$this->assertInstanceOf( StatementCountField::class, $fields['statement_count'] );

		$this->assertArrayHasKey( 'statement_keywords', $fields );
		$this->assertInstanceOf( \WikibaseSearchElastic\Fields\StatementsField::class, $fields['statement_keywords'] );
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
