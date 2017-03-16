<?php

namespace Wikibase\Client\Tests\Serializer;

use PHPUnit_Framework_TestCase;
use RuntimeException;

/**
 * @covers Wikibase\Client\Serializer\ForbiddenSerializer
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ForbiddenSerializerTest extends PHPUnit_Framework_TestCase {

	public function test() {
		$serializer = new \Wikibase\Edrsf\ForbiddenSerializer( 'customMessage' );

		$this->setExpectedException( RuntimeException::class, 'customMessage' );
		$serializer->serialize( null );
	}

}
