<?php

namespace Wikibase\Client\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Client\Specials\SpecialPagesWithBadges;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;

/**
 * @covers Wikibase\Client\Specials\SpecialPagesWithBadges
 *
 * @group WikibaseClient
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialPagesWithBadgesTest extends SpecialPageTestBase {

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelLookup() {
		$labelLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnCallback( function( ItemId $id ) {
				return new Term( 'en', 'Label of ' . $id->getSerialization() );
			} ) );

		return $labelLookup;
	}

	/**
	 * @return LanguageFallbackLabelDescriptionLookupFactory
	 */
	private function getLabelDescriptionLookupFactory() {
		$itemIds = [
			new ItemId( 'Q123' ),
			new ItemId( 'Q456' )
		];

		$labelDescriptionLookupFactory = $this->getMockBuilder(
				LanguageFallbackLabelDescriptionLookupFactory::class
			)
			->disableOriginalConstructor()
			->getMock();
		$labelDescriptionLookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->with( $this->anything(), $this->equalTo( $itemIds ) )
			->will( $this->returnValue( $this->getLabelLookup() ) );

		return $labelDescriptionLookupFactory;
	}

	protected function newSpecialPage() {
		$specialPage = new SpecialPagesWithBadges(
			$this->getLabelDescriptionLookupFactory(),
			[ 'Q123', 'Q456' ],
			'enwiki'
		);

		return $specialPage;
	}

	public function testExecuteWithoutAnyParams() {
		list( $result, ) = $this->executeSpecialPage( '' );

		$this->assertContains( "<select tabindex='0' aria-disabled='false' name='badge'", $result );
		$this->assertContains( "<option value='Q123'", $result );
		$this->assertContains( "<option value='Q456'", $result );

		$this->assertContains( 'Label of Q123', $result );
		$this->assertContains( 'Label of Q456', $result );
	}

	public function testExecuteWithValidParam() {
		list( $result, ) = $this->executeSpecialPage( 'Q456' );

		$this->assertContains( "<option value='Q456' selected='selected'", $result );
	}

	public function testExecuteWithInvalidParam() {
		list( $result, ) = $this->executeSpecialPage( 'FooBar' );

		$this->assertContains( '<p class="error"', $result );
		$this->assertContains(
			wfMessage( 'wikibase-pageswithbadges-invalid-id', 'FooBar' )->text(),
			$result
		);
	}

}
