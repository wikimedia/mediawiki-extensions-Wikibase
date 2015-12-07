<?php

namespace Wikibase\Test;

use Wikibase\Repo\Search\Elastic\Fields\WikibaseFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\WikibaseFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseFieldDefinitionsTest extends \PHPUnit_Framework_TestCase {

	public function testGetFields() {
		$wikibaseFieldDefinitions = new WikibaseFieldDefinitions();
		$fields = $wikibaseFieldDefinitions->getFields();

		$expectedFieldNames = array( 'label_count', 'sitelink_count', 'statement_count' );

		$this->assertSame( $expectedFieldNames, array_keys( $fields ) );
	}

	public function testGetFields_instanceOfSearchIndexField() {
		$wikibaseFieldDefinitions = new WikibaseFieldDefinitions();

		foreach ( $wikibaseFieldDefinitions->getFields() as $fieldName => $field ) {
			$this->assertInstanceOf(
				'Wikibase\Repo\Search\Elastic\Fields\SearchIndexField',
				$field,
				"$fieldName must be instance of SearchIndexField"
			);
		}
	}

}
