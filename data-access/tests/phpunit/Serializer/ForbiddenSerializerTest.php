<?php

namespace Wikibase\DataAccess\Tests\Serializer;

use PHPUnit4And6Compat;
use RuntimeException;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;

/**
 * @covers Wikibase\DataAccess\Serializer\ForbiddenSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ForbiddenSerializerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function test() {
		$serializer = new ForbiddenSerializer( 'customMessage' );

		$this->setExpectedException( RuntimeException::class, 'customMessage' );
		$serializer->serialize( null );
	}

}
