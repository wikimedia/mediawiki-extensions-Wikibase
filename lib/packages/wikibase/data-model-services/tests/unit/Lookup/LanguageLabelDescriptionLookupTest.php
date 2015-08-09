<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;

/**
 * @covers Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LanguageLabelDescriptionLookupTest extends \PHPUnit_Framework_TestCase {

	public function testGetLabel() {
		$termLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\TermLookup' );

		$termLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $this->equalTo( new ItemId( 'Q42' ) ), $this->equalTo( 'language_code' ) )
			->willReturn( 'term_text' );

		$lookup = new LanguageLabelDescriptionLookup( $termLookup, 'language_code' );

		$this->assertEquals(
			new Term( 'language_code', 'term_text' ),
			$lookup->getLabel( new ItemId( 'Q42' ) )
		);
	}

	public function testGetDescription() {
		$termLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\TermLookup' );

		$termLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $this->equalTo( new ItemId( 'Q42' ) ), $this->equalTo( 'language_code' ) )
			->willReturn( 'term_text' );

		$lookup = new LanguageLabelDescriptionLookup( $termLookup, 'language_code' );

		$this->assertEquals(
			new Term( 'language_code', 'term_text' ),
			$lookup->getDescription( new ItemId( 'Q42' ) )
		);
	}

}
