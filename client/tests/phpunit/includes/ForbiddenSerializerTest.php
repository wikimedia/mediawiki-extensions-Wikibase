<?php

namespace Wikibase\Client\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\Client\ForbiddenSerializer;

/**
 * @covers Wikibase\Client\ForbiddenSerializer
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class ForbiddenSerializerTest extends PHPUnit_Framework_TestCase {

	public function test() {
		$serializer = new ForbiddenSerializer( 'customMessage' );

		$this->setExpectedException( 'RuntimeException', 'customMessage' );
		$serializer->serialize( null );
	}

}
