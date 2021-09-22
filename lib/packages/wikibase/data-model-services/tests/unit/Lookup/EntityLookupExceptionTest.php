<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\EntityLookupException
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class EntityLookupExceptionTest extends TestCase {

	public function testConstructorWithJustAnId() {
		$propertyId = new NumericPropertyId( 'P42' );
		$exception = new EntityLookupException( $propertyId );

		$this->assertEquals( $propertyId, $exception->getEntityId() );
	}

}
