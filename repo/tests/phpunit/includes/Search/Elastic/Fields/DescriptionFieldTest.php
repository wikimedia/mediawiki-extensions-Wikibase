<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use CirrusSearch;
use SearchEngine;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\DescriptionsField;

/**
 * @covers \Wikibase\Repo\Search\Elastic\Fields\DescriptionsField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 */
class DescriptionFieldTest extends SearchFieldTestCase {

	public function getFieldDataProvider() {
		$item = new Item();
		$item->getFingerprint()->setDescription( 'es', 'Gato' );
		$item->getFingerprint()->setDescription( 'ru', 'Кошка' );
		$item->getFingerprint()->setDescription( 'de', 'Katze' );
		$item->getFingerprint()->setDescription( 'fr', 'Chat' );

		$prop = Property::newFromType( 'string' );
		$prop->getFingerprint()->setDescription( 'en', 'astrological sign' );
		$prop->getFingerprint()->setDescription( 'ru', 'знак зодиака' );

		$mock = $this->getMock( EntityDocument::class );

		return [
			[
				[
					'es' => 'Gato',
					'ru' => 'Кошка',
					'de' => 'Katze',
					'fr' => 'Chat'
				],
				$item
			],
			[
				[
					'en' => 'astrological sign',
					'ru' => 'знак зодиака',
				],
				$prop
			],
			[ [], $mock ]
		];
	}

	/**
	 * @dataProvider  getFieldDataProvider
	 * @param $expected
	 * @param EntityDocument $entity
	 */
	public function testDescriptions( $expected, EntityDocument $entity ) {
		$labels = new DescriptionsField( [ 'en', 'es', 'ru', 'de' ], [] );
		$this->assertEquals( $expected, $labels->getFieldData( $entity ) );
	}

	public function testGetMapping() {
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch needed.' );
		}
		$labels = new DescriptionsField( [ 'en', 'es', 'ru', 'de' ],
			[ 'useStemming' =>
				  [
					'en' => [ 'index' => true, 'search' => true ],
					'es' => [ 'index' => true, 'search' => false ],
					'ru' => [ 'index' => false, 'search' => true ],
				  ]
			]
		);
		$searchEngine = $this->getSearchEngineMock();
		$searchEngine->expects( $this->never() )->method( 'makeSearchFieldMapping' );

		$mapping = $labels->getMapping( $searchEngine );
		$this->assertArrayHasKey( 'properties', $mapping );
		$this->assertCount( 4, $mapping['properties'] );
		$this->assertEquals( 'object', $mapping['type'] );

		$this->assertEquals( "en_text", $mapping['properties']['en']['analyzer'] );
		$this->assertEquals( "es_text_search", $mapping['properties']['es']['search_analyzer'] );
		$this->assertEquals( "no", $mapping['properties']['ru']['index'] );
		$this->assertEquals( "ru_plain",
			$mapping['properties']['ru']['fields']['plain']['analyzer'] );
		$this->assertEquals( "no", $mapping['properties']['de']['index'] );
		$this->assertEquals( "de_plain_search",
			$mapping['properties']['de']['fields']['plain']['search_analyzer'] );
	}

	public function testGetMappingOtherSearchEngine() {
		$labels = new DescriptionsField( [ 'en', 'es', 'ru', 'de' ], [] );

		$searchEngine = $this->getMockBuilder( SearchEngine::class )->getMock();
		$searchEngine->expects( $this->never() )->method( 'makeSearchFieldMapping' );

		$this->assertSame( [], $labels->getMapping( $searchEngine ) );
	}

	public function testHints() {
		$labels = new DescriptionsField( [ 'en', 'es', 'ru', 'de' ], [] );
		$searchEngine = $this->getSearchEngineMock();
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->assertEquals( [], $labels->getEngineHints( $searchEngine ) );
		} else {
			$this->assertEquals( [ 'noop' => 'equals' ], $labels->getEngineHints( $searchEngine ) );
		}
	}

}
