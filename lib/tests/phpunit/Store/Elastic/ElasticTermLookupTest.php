<?php
namespace Wikibase\Lib\Tests\Store;

use Elastica\Result;
use Status;
use Title;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Lib\Store\ElasticTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\TermLookupSearcher;

/**
 * @covers \Wikibase\Lib\Store\ElasticTermLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class ElasticTermLookupTest extends EntityTermLookupTest {

	private static $termData;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$termData = self::getTermData();
		if ( !class_exists( 'CirrusSearch' ) ) {
			self::markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
	}

	public function providePrefetchTerms() {
		return [
			'not existing' => [
				'Q115',
				'label',
				'en',
				false
			],
			'not fetched' => [
				'Q117',
				'label',
				'en',
				null
			],
			'existing' => [
				'Q116',
				'label',
				'en',
				'New York City'
			],
			'existing de' => [
				'Q116',
				'description',
				'de',
				'Metropole an der Ostküste der Vereinigten Staaten'
			],
			'existing no lang' => [
				'Q116',
				'label',
				'ru',
				false
			],

		];
	}

	/**
	 * @dataProvider providePrefetchTerms
	 */
	public function testPrefetchedTerms( $entityId, $type, $language, $result ) {
		$lookup = $this->getEntityTermLookup();
		$lookup->prefetchTerms( [
			new ItemId( 'Q115' ),
			new ItemId( 'Q116' ),
		] );

		$term = $lookup->getPrefetchedTerm( new ItemId( $entityId ), $type, $language );
		$this->assertEquals( $result, $term );
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function ( EntityId $entityId ) {
				return Title::newFromText( $entityId->getSerialization() );
			} ) );

		return $entityTitleLookup;
	}

	/**
	 * Extract term data into format better suitable for searcher mocking.
	 * @return array
	 */
	private static function getTermData() {
		$termIndexData = parent::provideTerms();
		$data = [];
		foreach ( $termIndexData as $termIndex ) {
			$data[$termIndex->getEntityId()->getSerialization()]
			[$termIndex->getTermType()]
			[$termIndex->getLanguage()] = $termIndex->getText();
		}
		return $data;
	}

	/**
	 * @return TermLookupSearcher
	 */
	private function getSearcher() {
		$searcher = $this->getMockBuilder( TermLookupSearcher::class )->
			disableOriginalConstructor()->getMock();
		$data = self::$termData;
		$searcher->method( 'getByTitle' )->willReturnCallback(
			function ( $titles, $fields ) use ( $data ) {
				$result = [];
				foreach ( $titles as $title ) {
					$key = $title->getText();
					if ( !empty( $data[$key] ) ) {
						$result[] = new Result( [
							'_source' => [
								'title' => $key,
								'labels' => $data[$key]['label'],
								'descriptions' => $data[$key]['description'],
							],
						] );
					}
				}
				return Status::newGood( $result );
		 } );

		return $searcher;
	}

	/**
	 * @return TermLookup
	 */
	protected function getEntityTermLookup() {
		$idParser = new BasicEntityIdParser();
		return new ElasticTermLookup( $this->getSearcher(), $this->getEntityTitleLookup(), $idParser );
	}

}
