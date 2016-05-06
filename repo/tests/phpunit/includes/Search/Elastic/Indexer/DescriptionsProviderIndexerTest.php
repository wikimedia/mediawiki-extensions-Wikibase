<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Indexer\DescriptionsProviderIndexer;

/**
 * @covers Wikibase\Repo\Search\Elastic\Indexer\DescriptionsProviderIndexer
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DescriptionsProviderIndexerTest extends PHPUnit_Framework_TestCase {

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$indexer = new DescriptionsProviderIndexer( $languageCodes );

		$document = new Document();

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setDescription( 'es', 'un gato joven' );

		$indexer->indexEntity( $property, $document );

		$this->assertSame( 'un gato joven', $document->get( 'description_es' ) );
	}

	public function testIndexEntityWithUnknownLanguageCode() {
		$languageCodes = [ 'de', 'en' ];
		$indexer = new DescriptionsProviderIndexer( $languageCodes );

		$document = new Document();
		$item = new Item();
		$item->getFingerprint()->setDescription( 'es', 'un gato joven' );

		$indexer->indexEntity( $item, $document );

		$this->setExpectedException( 'Elastica\Exception\InvalidException' );

		$document->get( 'description_es' );
	}

}
