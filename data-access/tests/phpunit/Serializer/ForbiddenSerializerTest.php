<?php

namespace Wikibase\DataAccess\Tests\Serializer;

use RuntimeException;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;

/**
 * @covers \Wikibase\DataAccess\Serializer\ForbiddenSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ForbiddenSerializerTest extends \PHPUnit\Framework\TestCase {

	public function test() {
		$serializer = new ForbiddenSerializer( 'customMessage' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'customMessage' );
		$serializer->serialize( null );
	}

}
