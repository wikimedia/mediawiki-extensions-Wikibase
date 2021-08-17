<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Statement\V4GuidGenerator;

/**
 * @covers \Wikibase\DataModel\Services\Statement\V4GuidGenerator
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class V4GuidGeneratorTest extends TestCase {

	public function testGetGuid() {
		$guidGen = new V4GuidGenerator();

		$firstGuid = $guidGen->newGuid();
		$secondGuid = $guidGen->newGuid();

		$this->assertIsString( $firstGuid );
		$this->assertIsString( $secondGuid );
		$this->assertNotSame( $firstGuid, $secondGuid );
	}

}
