<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Search\Elastic\FieldDefinitions\ItemFieldDefinitions;

/**
 * @covers Wikibase\Repo\Search\Elastic\FieldDefinitions\ItemFieldDefinition
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemFieldDefinitionsTest extends PHPUnit_Framework_TestCase {

	public function testGetMappingProperties() {
		$languageCodes = [ 'ar', 'es' ];
		$fieldDefinitions = new ItemFieldDefinitions( $languageCodes );

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
			'sitelink_count' => [
				'type' => 'integer'
			],
			'statement_count' => [
				'type' => 'integer'
			]
		];

		$this->assertSame( $expected, $fieldDefinitions->getMappingProperties() );
	}

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$fieldDefinitions = new ItemFieldDefinitions( $languageCodes );

		$document = new Document();

		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );
		$item->getFingerprint()->setDescription( 'en', 'young cat' );

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 9000 ) );

		$fieldDefinitions->indexEntity( $item, $document );

		$this->assertSame( 'Gato', $document->get( 'label_es' ) );
		$this->assertSame( 'young cat', $document->get( 'description_en' ) );
		$this->assertSame( 1, $document->get( 'label_count' ) );
		$this->assertSame( 1, $document->get( 'sitelink_count' ) );
		$this->assertSame( 1, $document->get( 'statement_count' ) );
	}

}
