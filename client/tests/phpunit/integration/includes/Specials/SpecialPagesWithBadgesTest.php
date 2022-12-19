<?php

namespace Wikibase\Client\Tests\Integration\Specials;

use SpecialPageTestBase;
use Wikibase\Client\Specials\SpecialPagesWithBadges;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @covers \Wikibase\Client\Specials\SpecialPagesWithBadges
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

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );
	}

	private function getLabelDescriptionLookupFactory(): FallbackLabelDescriptionLookupFactory {
		$labelLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$labelLookup->method( 'getLabel' )
			->willReturnCallback( function( ItemId $id ): ?TermFallback {
				return new TermFallback(
					'en',
					'Label of ' . $id->getSerialization(),
					'en',
					'en'
				);
			} );

		$itemIds = [
			new ItemId( 'Q123' ),
			new ItemId( 'Q456' ),
		];

		$labelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$labelDescriptionLookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->with( $this->anything(), $itemIds )
			->willReturn( $labelLookup );

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

		$this->assertStringContainsString( "<option value='Q123'", $result );
		$this->assertStringContainsString( "<option value='Q456'", $result );

		$this->assertStringContainsString( 'Label of Q123', $result );
		$this->assertStringContainsString( 'Label of Q456', $result );
	}

	public function testExecuteWithValidParam() {
		list( $result, ) = $this->executeSpecialPage( 'Q456' );

		$this->assertStringContainsString( "<option value='Q456' selected='selected'", $result );
	}

	public function testExecuteWithInvalidParam() {
		list( $result, ) = $this->executeSpecialPage( 'FooBar' );

		$this->assertStringContainsString( '<p class="error"', $result );
		$this->assertStringContainsString(
			'(wikibase-pageswithbadges-invalid-id: FooBar)',
			$result
		);
	}

}
