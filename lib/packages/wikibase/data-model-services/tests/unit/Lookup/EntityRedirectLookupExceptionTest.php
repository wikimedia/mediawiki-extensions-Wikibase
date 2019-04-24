<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\EntityRedirectLookupException
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityRedirectLookupExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$entityId = new ItemId( 'Q1' );
		$exception = new EntityRedirectLookupException( $entityId );

		$this->assertSame( $entityId, $exception->getEntityId() );
		$this->assertSame( 'Entity redirect lookup failed for: Q1', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$entityId = new ItemId( 'Q1' );
		$previous = new Exception( 'previous' );
		$exception = new EntityRedirectLookupException( $entityId, 'customMessage', $previous );

		$this->assertSame( $entityId, $exception->getEntityId() );
		$this->assertSame( 'customMessage', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
