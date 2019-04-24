<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\Term;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\LanguageLabelDescriptionLookup
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class LanguageLabelDescriptionLookupTest extends TestCase {

	public function testGetLabelCallsTermLookupAndReturnsStringAsTerm() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $this->equalTo( new ItemId( 'Q42' ) ), $this->equalTo( 'language_code' ) )
			->will( $this->returnValue( 'term_text' ) );

		$lookup = new LanguageLabelDescriptionLookup( $termLookup, 'language_code' );

		$this->assertEquals(
			new Term( 'language_code', 'term_text' ),
			$lookup->getLabel( new ItemId( 'Q42' ) )
		);
	}

	public function testGetDescriptionCallsTermLookupAndReturnsStringAsTerm() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $this->equalTo( new ItemId( 'Q42' ) ), $this->equalTo( 'language_code' ) )
			->will( $this->returnValue( 'term_text' ) );

		$lookup = new LanguageLabelDescriptionLookup( $termLookup, 'language_code' );

		$this->assertEquals(
			new Term( 'language_code', 'term_text' ),
			$lookup->getDescription( new ItemId( 'Q42' ) )
		);
	}

	public function testWhenGettingNull_getLabelReturnsNull() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->once() )
			->method( 'getLabel' )
			->will( $this->returnValue( null ) );

		$lookup = new LanguageLabelDescriptionLookup( $termLookup, 'language_code' );

		$this->assertNull( $lookup->getLabel( new ItemId( 'Q42' ) ) );
	}

	public function testWhenGettingNull_getDescriptionReturnsNull() {
		$termLookup = $this->createMock( TermLookup::class );

		$termLookup->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( null ) );

		$lookup = new LanguageLabelDescriptionLookup( $termLookup, 'language_code' );

		$this->assertNull( $lookup->getDescription( new ItemId( 'Q42' ) ) );
	}

}
