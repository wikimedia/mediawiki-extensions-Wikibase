<?php

namespace Wikibase\DataModel\Services\Tests\Statement;

use Wikibase\DataModel\Services\Statement\V4GuidGenerator;

/**
 * @covers Wikibase\DataModel\Services\Statement\V4GuidGenerator
 *
 * @license GPL-2.0+
 * @author Addshore
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
