<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Indexer\LabelsProviderIndexer;

/**
 * @covers Wikibase\Repo\Search\Elastic\Indexer\LabelsProviderIndexer
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LabelsProviderIndexerTest extends PHPUnit_Framework_TestCase {

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$indexer = new LabelsProviderIndexer( $languageCodes );

		$document = new Document();

		$item = new Item();
		$item->getFingerprint()->setLabel( 'es', 'Gato' );

		$indexer->indexEntity( $item, $document );

		$this->assertSame( 'Gato', $document->get( 'label_es' ) );
	}

	public function testIndexEntityWithUnknownLanguageCode() {
		$languageCodes = [ 'en', 'es' ];
		$indexer = new LabelsProviderIndexer( $languageCodes );

		$document = new Document();
		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setLabel( 'de', 'Katze' );

		$indexer->indexEntity( $property, $document );

		$this->setExpectedException( 'Elastica\Exception\InvalidException' );

		$document->get( 'label_de' );
	}

}
