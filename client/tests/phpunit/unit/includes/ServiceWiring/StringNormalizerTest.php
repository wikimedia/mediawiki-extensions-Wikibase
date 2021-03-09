<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\StringNormalizer;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class StringNormalizerTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->assertInstanceOf(
			StringNormalizer::class,
			$this->getService( 'WikibaseClient.StringNormalizer' )
		);
	}
}
