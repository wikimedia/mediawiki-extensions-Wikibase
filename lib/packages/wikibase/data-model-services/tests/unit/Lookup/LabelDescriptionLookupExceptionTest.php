<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class LabelDescriptionLookupExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$itemId = new ItemId( 'Q1' );
		$exception = new LabelDescriptionLookupException( $itemId );

		$this->assertSame( $itemId, $exception->getEntityId() );
		$this->assertSame( 'Label and description lookup failed for: Q1', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$itemId = new ItemId( 'Q1' );
		$previous = new Exception( 'previous' );
		$exception = new LabelDescriptionLookupException( $itemId, 'customMessage', $previous );

		$this->assertSame( $itemId, $exception->getEntityId() );
		$this->assertSame( 'customMessage', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
