<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermBufferTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$prefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->mockService( 'WikibaseRepo.PrefetchingTermLookup', $prefetchingTermLookup );

		$termBuffer = $this->getService( 'WikibaseRepo.TermBuffer' );

		$this->assertSame( $prefetchingTermLookup, $termBuffer );
	}

}
