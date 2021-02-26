<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\LoggerInterface;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LoggerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->assertInstanceOf(
			LoggerInterface::class,
			$this->getService( 'WikibaseClient.Logger' )
		);
	}

}
