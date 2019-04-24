<?php

namespace Wikibase\DataModel\Services\Tests\Lookup;

use Exception;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;

/**
 * @covers \Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class UnresolvedEntityRedirectExceptionTest extends TestCase {

	public function testConstructorWithOnlyRequiredArguments() {
		$entityId = new ItemId( 'Q1' );
		$redirectTargetId = new ItemId( 'Q2' );
		$exception = new UnresolvedEntityRedirectException( $entityId, $redirectTargetId );

		$this->assertSame( $entityId, $exception->getEntityId() );
		$this->assertSame( $redirectTargetId, $exception->getRedirectTargetId() );
		$this->assertSame( 'Unresolved redirect from Q1 to Q2', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertNull( $exception->getPrevious() );
	}

	public function testConstructorWithAllArguments() {
		$entityId = new ItemId( 'Q1' );
		$redirectTargetId = new ItemId( 'Q2' );
		$previous = new Exception( 'previous' );
		$exception = new UnresolvedEntityRedirectException(
			$entityId,
			$redirectTargetId,
			'customMessage',
			$previous
		);

		$this->assertSame( $entityId, $exception->getEntityId() );
		$this->assertSame( $redirectTargetId, $exception->getRedirectTargetId() );
		$this->assertSame( 'customMessage', $exception->getMessage() );
		$this->assertSame( 0, $exception->getCode() );
		$this->assertSame( $previous, $exception->getPrevious() );
	}

}
