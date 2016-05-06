<?php

namespace Wikibase\Test;

use Elastica\Document;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Search\Elastic\Indexer\PropertyIndexer;

/**
 * @covers Wikibase\Repo\Search\Elastic\Indexer\PropertyIndexer
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyIndexerTest extends PHPUnit_Framework_TestCase {

	public function testIndexEntity() {
		$languageCodes = [ 'en', 'es' ];
		$indexer = new PropertyIndexer( $languageCodes );

		$document = new Document();

		$property = Property::newFromType( 'string' );
		$property->getFingerprint()->setLabel( 'es', 'Gato' );
		$property->getFingerprint()->setDescription( 'en', 'young cat' );

		$property->getStatements()->addNewStatement( new PropertyNoValueSnak( 9000 ) );

		$indexer->indexEntity( $property, $document );

		$this->assertSame( 'Gato', $document->get( 'label_es' ) );
		$this->assertSame( 'young cat', $document->get( 'description_en' ) );
		$this->assertSame( 1, $document->get( 'label_count' ) );
		$this->assertSame( 1, $document->get( 'statement_count' ) );
	}

}
