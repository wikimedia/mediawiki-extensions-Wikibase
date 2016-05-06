<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\LabelsProviderFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\LabelsProviderFieldDefinition
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelsProviderFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetMappingProperties() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new LabelsProviderFieldDefinitions( $languageCodes );

		$expected = [
			'label_ar' => [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			],
			'label_es' => [
				'type' => 'string',
				'copy_to' => [ 'all', 'all_near_match' ]
			],
			'label_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getMappingProperties() );
	}

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$fieldDefinitions = new LabelsProviderFieldDefinitions( $languageCodes );

		$document = new Document();

		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );

		$fieldDefinitions->indexEntity( $item, $document );

		$this->assertSame( 'Gato', $document->get( 'label_es' ) );
	}

	public function testIndexEntity_withUnknownLanguageCode() {
		$languageCodes = [ 'en', 'es' ];
		$fieldDefinitions = new LabelsProviderFieldDefinitions( $languageCodes );

		$document = new Document();
		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setLabel( 'de', 'Katze' );

		$fieldDefinitions->indexEntity( $property, $document );

		$this->setExpectedException( 'Elastica\Exception\InvalidException' );

		$document->get( 'label_de' );
	}

}
