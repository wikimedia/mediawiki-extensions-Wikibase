<?php

namespace Wikibase\Lib\Tests\Interactors;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Interactors\TermSearchResult;

/**
 * @covers \Wikibase\Lib\Interactors\TermSearchResult
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermSearchResultTest extends \PHPUnit\Framework\TestCase {

	public static function provideGoodConstruction() {
		return [
			[
				new Term( 'br', 'FooText' ),
				'label',
				new ItemId( 'Q1234' ),
				new Term( 'pt', 'ImaLabel' ),
				new Term( 'en', 'ImaDescription' ),
				[],
			],
			[
				new Term( 'en-gb', 'FooText' ),
				'description',
				new NumericPropertyId( 'P777' ),
				null,
				null,
				[ 'datatype' => 'some datatype' ],
			],
		];
	}

	/**
	 * @dataProvider provideGoodConstruction
	 */
	public function testGoodConstruction(
		$matchedTerm,
		$matchedTermType,
		$entityId,
		$displayLabel,
		$displayDescription,
		$metaData
	) {
		$result = new TermSearchResult(
			$matchedTerm,
			$matchedTermType,
			$entityId,
			$displayLabel,
			$displayDescription,
			$metaData
		);

		$this->assertEquals( $matchedTerm, $result->getMatchedTerm() );
		$this->assertEquals( $matchedTermType, $result->getMatchedTermType() );
		$this->assertEquals( $entityId, $result->getEntityId() );
		$this->assertEquals( $displayLabel, $result->getDisplayLabel() );
		$this->assertEquals( $displayDescription, $result->getDisplayDescription() );
		$this->assertEquals( $metaData, $result->getMetaData() );
	}

	public function testGetMetaDataReturnsEmptyArrayByDefault() {
		$termSearchResult = new TermSearchResult(
			new Term( 'en', 'potato' ),
			'label',
			new ItemId( 'Q10998' )
		);

		$this->assertEquals( [], $termSearchResult->getMetaData() );
	}

	public static function provideParamsForQ1(): iterable {
		yield 'EntityId' => [ new ItemId( 'Q1' ), [] ];
		yield 'string id' => [ null, [ 'id' => 'Q1' ] ];
	}

	/** @dataProvider provideParamsForQ1 */
	public function testGetEntityIdSerialization( ?EntityId $entityId, array $metadata ): void {
		$matchedTerm = new Term( 'en', 'matchedTerm' );
		$matchedTermType = 'matchedTermType';
		$displayLabel = null;
		$displayDescription = null;

		$result = new TermSearchResult(
			$matchedTerm,
			$matchedTermType,
			$entityId,
			$displayLabel,
			$displayDescription,
			$metadata
		);
		$entityIdSerialization = $result->getEntityIdSerialization();

		$this->assertSame( 'Q1', $entityIdSerialization );
	}

}
