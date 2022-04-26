<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\Usage;

use Exception;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;

/**
 * @covers \Wikibase\Client\Usage\EntityUsageFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityUsageFactoryTest extends \PHPUnit\Framework\TestCase {

	public function newFromIdentityRoundtripProvider() {
		return [
			[ new EntityUsage( new ItemId( 'Q21' ), EntityUsage::ALL_USAGE ) ],
			[ new EntityUsage( new NumericPropertyId( 'P12' ), EntityUsage::LABEL_USAGE, 'blah' ) ],
		];
	}

	/**
	 * @dataProvider newFromIdentityRoundtripProvider
	 */
	public function testNewFromIdentityRoundtrip( EntityUsage $entityUsage ) {
		$factory = new EntityUsageFactory( new BasicEntityIdParser() );

		$this->assertSame(
			$entityUsage->asArray(),
			$factory->newFromIdentity( $entityUsage->getIdentityString() )->asArray()
		);
	}

	public function newFromIdentityInvalidIdentityProvider() {
		return [
			'Invalid format' => [ 'banana' ],
			'Invalid entity id' => [ 'banana#X' ],
			'Invalid aspect' => [ 'Q12#Ü' ],
		];
	}

	/**
	 * @dataProvider newFromIdentityInvalidIdentityProvider
	 */
	public function testNewFromIdentityInvalidIdentity( string $identity ) {
		$factory = new EntityUsageFactory( new BasicEntityIdParser() );

		$this->expectException( Exception::class );
		$factory->newFromIdentity( $identity );
	}

}
