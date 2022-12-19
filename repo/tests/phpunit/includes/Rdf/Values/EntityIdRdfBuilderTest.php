<?php

namespace Wikibase\Repo\Tests\Rdf\Values;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Rdf\NullEntityMentionListener;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\Values\EntityIdRdfBuilder;
use Wikibase\Repo\Tests\Rdf\NTriplesRdfTestHelper;
use Wikimedia\Purtle\NTriplesRdfWriter;

/**
 * @covers \Wikibase\Repo\Rdf\Values\EntityIdRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdRdfBuilderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var NTriplesRdfTestHelper
	 */
	private $helper;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = new NTriplesRdfTestHelper();
	}

	public function testAddValue() {
		$vocab = new RdfVocabulary(
			[ 'test' => 'http://test/item/' ],
			[ 'test' => 'http://test/data/' ],
			new EntitySourceDefinitions( [
				new DatabaseEntitySource(
					'test',
					'testdb',
					[ 'item' => [ 'namespaceId' => 3000, 'slot' => SlotRecord::MAIN ] ],
					'http://test/item/',
					'',
					'',
					''
				),
			], new SubEntityTypesMapper( [] ) ),
			[ 'test' => '' ],
			[ 'test' => '' ]
		);
		$builder = new EntityIdRdfBuilder( $vocab, new NullEntityMentionListener() );

		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );
		$writer->prefix( RdfVocabulary::NS_ENTITY, 'http://test/item/' );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak(
			new NumericPropertyId( 'P1' ),
			new EntityIdValue( new ItemId( 'Q23' ) )
		);

		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', '', $snak );

		$expected = '<http://www/Q1> <http://acme/testing> <http://test/item/Q23> .';
		$this->helper->assertNTriplesEquals( $expected, $writer->drain() );
	}

}
