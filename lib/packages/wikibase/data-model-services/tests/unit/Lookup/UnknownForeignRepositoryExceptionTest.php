<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException
 *
 * @license GPL-2.0-or-later
 */
class UnknownForeignRepositoryExceptionTest extends TestCase {

	public function testConstructWithRepositoryNameOnly() {
		$exception = new UnknownForeignRepositoryException( 'foo' );

		$this->assertSame( 'foo', $exception->getRepositoryName() );
		$this->assertSame( 'Unknown repository name: foo', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructWithAllArguments() {
		$previous = new Exception();
		$exception = new UnknownForeignRepositoryException( 'foo', 'No such repository: foo', $previous );

		$this->assertSame( 'foo', $exception->getRepositoryName() );
		$this->assertSame( 'No such repository: foo', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
