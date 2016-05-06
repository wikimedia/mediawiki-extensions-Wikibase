<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\PropertyFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\PropertyFieldDefinition
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetMappingProperties() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new PropertyFieldDefinitions( $languageCodes );

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
			],
			'description_ar' => [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			],
			'description_es' => [
				'type' => 'string',
				'copy_to' => [ 'all' ]
			],
			'statement_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getMappingProperties() );
	}

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$fieldDefinitions = new PropertyFieldDefinitions( $languageCodes );

		$document = new Document();

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setLabel( 'es', 'Gato' );
		$property->getFingerprint()->setDescription( 'en', 'young cat' );

		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 9000 ) );

		$fieldDefinitions->indexEntity( $property, $document );

		$this->assertSame( 'Gato', $document->get( 'label_es' ) );
		$this->assertSame( 'young cat', $document->get( 'description_en' ) );
		$this->assertSame( 1, $document->get( 'label_count' ) );
		$this->assertSame( 1, $document->get( 'statement_count' ) );
	}

}
