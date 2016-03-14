<?php

namespace Wikibase\Client\Tests;

use PHPUnit_Framework_TestCase;
use RuntimeException;
use Wikibase\Client\ForbiddenSerializer;

/**
 * @covers Wikibase\Client\ForbiddenSerializer
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class ForbiddenSerializerTest extends PHPUnit_Framework_TestCase {

	public function test() {
		$serializer = new ForbiddenSerializer( 'customMessage' );

		$this->setExpectedException( RuntimeException::class, 'customMessage' );
		$serializer->serialize( null );
	}

}
