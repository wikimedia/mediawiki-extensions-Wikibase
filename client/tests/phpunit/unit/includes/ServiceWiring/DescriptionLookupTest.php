<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Store\DescriptionLookup;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\Lib\Store\EntityIdLookup;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DescriptionLookupTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseClient.EntityIdLookup',
			$this->createMock( EntityIdLookup::class )
		);

		$this->mockService(
			'WikibaseClient.TermBuffer',
			$this->createMock( TermBuffer::class )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getPageProps' );

		$this->assertInstanceOf(
			DescriptionLookup::class,
			$this->getService( 'WikibaseClient.DescriptionLookup' )
		);
	}

}
