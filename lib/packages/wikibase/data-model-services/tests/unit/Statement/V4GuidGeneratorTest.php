<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use Wikibase\DataModel\Services\Statement\V4GuidGenerator;

/**
 * @covers Wikibase\DataModel\Services\Statement\V4GuidGenerator
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class V4GuidGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function testGetGuid() {
		$guidGen = new V4GuidGenerator();

		$firstGuid = $guidGen->newGuid();
		$secondGuid = $guidGen->newGuid();

		$this->assertInternalType( 'string', $firstGuid );
		$this->assertInternalType( 'string', $secondGuid );
		$this->assertNotSame( $firstGuid, $secondGuid );
	}

}
