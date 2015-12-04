<?php

namespace Wikibase\Test\Interactors;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Interactors\TermSearchResult;

/**
 * @covers Wikibase\Repo\Interactors\TermSearchResult
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class TermSearchResultTest extends PHPUnit_Framework_TestCase {

	public function provideGoodConstruction() {
		return array(
			array(
				new Term( 'br', 'FooText' ),
				'label',
				new ItemId( 'Q1234' ),
				new Term( 'pt', 'ImaLabel' ),
				new Term( 'en', 'ImaDescription' ),
			),
			array(
				new Term( 'en-gb', 'FooText' ),
				'description',
				new PropertyId( 'P777' ),
				null,
				null
			),
		);
	}

	/**
	 * @dataProvider provideGoodConstruction
	 */
	public function testGoodConstruction(
		$matchedTerm,
		$matchedTermType,
		$entityId,
		$displayLabel,
		$displayDescription
	) {
		$result = new TermSearchResult(
			$matchedTerm,
			$matchedTermType,
			$entityId,
			$displayLabel,
			$displayDescription
		);

		$this->assertEquals( $matchedTerm, $result->getMatchedTerm() );
		$this->assertEquals( $matchedTermType, $result->getMatchedTermType() );
		$this->assertEquals( $entityId, $result->getEntityId() );
		$this->assertEquals( $displayLabel, $result->getDisplayLabel() );
		$this->assertEquals( $displayDescription, $result->getDisplayDescription() );
	}

}
