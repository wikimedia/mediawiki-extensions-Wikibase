<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\Store\DispatchingFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;

/**
 * @covers \Wikibase\Lib\Store\DispatchingFallbackLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class DispatchingFallbackLabelDescriptionLookupTest extends TestCase {

	public function testGivenFederatedPropertyId_callsFederatedPropertiesLookup(): void {
		$entityId = new FederatedPropertyId( 'https://example.com/P1', 'P1' );
		$label = new TermFallback( 'en', 'label', 'en', 'en' );
		$description = new TermFallback( 'en', 'description', 'en', 'en' );

		$standardLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$standardLookup->expects( $this->never() )->method( $this->anything() );
		$federatedPropertiesLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$federatedPropertiesLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $entityId )
			->willReturn( $label );
		$federatedPropertiesLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $entityId )
			->willReturn( $description );

		$lookup = new DispatchingFallbackLabelDescriptionLookup(
			$standardLookup,
			$federatedPropertiesLookup
		);

		$this->assertSame( $label, $lookup->getLabel( $entityId ) );
		$this->assertSame( $description, $lookup->getDescription( $entityId ) );
	}

	/** @dataProvider provideOtherEntityIds */
	public function testGivenOtherEntityId_callsStandardLookup( EntityId $entityId ): void {
		$label = new TermFallback( 'en', 'label', 'en', 'en' );
		$description = new TermFallback( 'en', 'description', 'en', 'en' );

		$standardLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$standardLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $entityId )
			->willReturn( $label );
		$standardLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $entityId )
			->willReturn( $description );
		$federatedPropertiesLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$federatedPropertiesLookup->expects( $this->never() )->method( $this->anything() );

		$lookup = new DispatchingFallbackLabelDescriptionLookup(
			$standardLookup,
			$federatedPropertiesLookup
		);

		$this->assertSame( $label, $lookup->getLabel( $entityId ) );
		$this->assertSame( $description, $lookup->getDescription( $entityId ) );
	}

	public function provideOtherEntityIds(): iterable {
		yield 'item' => [ new ItemId( 'Q1' ) ];
		yield 'non-federated property' => [ new NumericPropertyId( 'P1' ) ];
	}

}
