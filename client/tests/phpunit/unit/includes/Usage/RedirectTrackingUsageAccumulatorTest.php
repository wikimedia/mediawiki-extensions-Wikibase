<?php

declare( strict_types=1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\RedirectTrackingUsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;

/**
 * @license GPL-2.0-or-later
 *
 * @group Wikibase
 *
 * @covers Wikibase\Client\Usage\RedirectTrackingUsageAccumulator
 */
class RedirectTrackingUsageAccumulatorTest extends \PHPUnit\Framework\TestCase {

	public function testAddUsage_noRedirect(): void {
		$innerUsageAccumulator = new HashUsageAccumulator();
		$entityRedirectTargetLookup = $this->createStub( EntityRedirectTargetLookup::class );
		$entityRedirectTargetLookup->method( 'getRedirectForEntityId' )->willReturn( null );

		$testUsage = new EntityUsage( new ItemId( 'Q42' ), EntityUsage::LABEL_USAGE, 'en' );
		$sut = new RedirectTrackingUsageAccumulator( $innerUsageAccumulator, $entityRedirectTargetLookup );
		$sut->addUsage( $testUsage );

		$actualUsages = $sut->getUsages();

		$this->assertCount( 1, $actualUsages );
		$this->assertSame( 'Q42#L.en', array_values( $actualUsages )[0]->getIdentityString() );
	}

	public function testAddUsage_withRedirect(): void {
		$innerUsageAccumulator = new HashUsageAccumulator();
		$entityRedirectTargetLookup = $this->createStub( EntityRedirectTargetLookup::class );
		$entityRedirectTargetLookup->method( 'getRedirectForEntityId' )->willReturn( new ItemId( 'Q123' ) );

		$testUsage = new EntityUsage( new ItemId( 'Q42' ), EntityUsage::LABEL_USAGE, 'en' );
		$sut = new RedirectTrackingUsageAccumulator( $innerUsageAccumulator, $entityRedirectTargetLookup );
		$sut->addUsage( $testUsage );

		$actualUsages = $sut->getUsages();

		$this->assertCount( 2, $actualUsages );
		$this->assertSame( 'Q123#L.en', array_values( $actualUsages )[0]->getIdentityString() );
		$this->assertSame( 'Q42#O', array_values( $actualUsages )[1]->getIdentityString() );
	}
}
