<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\DescriptionsProviderFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\DescriptionsProviderFieldDefinition
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DescriptionsProviderFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetMappingProperties() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new DescriptionsProviderFieldDefinitions( $languageCodes );

		$expected = [
			'description_ar' => [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			],
			'description_es' => [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getMappingProperties() );
	}

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$fieldDefinitions = new DescriptionsProviderFieldDefinitions( $languageCodes );

		$document = new Document();

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setDescription( 'es', 'un gato joven' );

		$fieldDefinitions->indexEntity( $property, $document );

		$this->assertSame( 'un gato joven', $document->get( 'description_es' ) );
	}

	public function testIndexEntity_withUnknownLanguageCode() {
		$languageCodes = [ 'de', 'en' ];
		$fieldDefinitions = new DescriptionsProviderFieldDefinitions( $languageCodes );

		$document = new Document();
		$item = new Item();
		$item->getFingerprint()->setDescription( 'es', 'un gato joven' );

		$fieldDefinitions->indexEntity( $item, $document );

		$this->setExpectedException( 'Elastica\Exception\InvalidException' );

		$document->get( 'description_es' );
	}

}
