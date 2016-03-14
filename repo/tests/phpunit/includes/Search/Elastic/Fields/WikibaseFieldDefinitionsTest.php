<?php

namespace Wikibase\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Search\Elastic\Fields\SearchIndexField;
use Wikibase\Repo\Search\Elastic\Fields\WikibaseFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\WikibaseFieldDefinitions
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

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
				SearchIndexField::class,
				$field,
				"$fieldName must be instance of SearchIndexField"
			);
		}
	}

}
