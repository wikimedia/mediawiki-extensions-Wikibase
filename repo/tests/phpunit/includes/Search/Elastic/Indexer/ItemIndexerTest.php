<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Search\Elastic\Indexer\ItemIndexer;

/**
 * @covers Wikibase\Repo\Search\Elastic\Indexer\ItemIndexer
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemIndexerTest extends PHPUnit_Framework_TestCase {

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$indexer = new ItemIndexer( $languageCodes );

		$document = new Document();

		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );
		$item->getFingerprint()->setDescription( 'en', 'young cat' );

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 9000 ) );

		$indexer->indexEntity( $item, $document );

		$this->assertSame( 'Gato', $document->get( 'label_es' ) );
		$this->assertSame( 'young cat', $document->get( 'description_en' ) );
		$this->assertSame( 1, $document->get( 'label_count' ) );
		$this->assertSame( 1, $document->get( 'sitelink_count' ) );
		$this->assertSame( 1, $document->get( 'statement_count' ) );
	}

}
