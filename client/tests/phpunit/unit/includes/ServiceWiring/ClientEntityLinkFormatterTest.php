<?php
declare( strict_types=1 );
namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Hooks\Formatter\ClientEntityLinkFormatter;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ClientEntityLinkFormatterTest extends ServiceWiringTestCase {
	public function testConstruction() {
		$this->assertInstanceOf(
			ClientEntityLinkFormatter::class,
			$this->getService( 'WikibaseClient.ClientEntityLinkFormatter' )
		);
	}
}
