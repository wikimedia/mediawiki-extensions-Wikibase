<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookupException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\TermLookupException
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TermLookupExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$entityId = new ItemId( 'Q1' );
		$exception = new TermLookupException( $entityId, [ 'de', 'en' ] );

		$this->assertSame( $entityId, $exception->getEntityId() );
		$this->assertSame(
			'Term lookup failed for: Q1 with language codes: de, en',
			$exception->getMessage()
		);
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$entityId = new ItemId( 'Q1' );
		$previous = new Exception( 'previous' );
		$exception = new TermLookupException(
			$entityId,
			[ 'de', 'en' ],
			'customMessage',
			$previous
		);

		$this->assertSame( $entityId, $exception->getEntityId() );
		$this->assertSame( 'customMessage', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
