<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use IContextSource;
use MediaWikiIntegrationTestCase;
use User;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\RateLimitingIdGenerator;

/**
 * @covers \Wikibase\Repo\Store\RateLimitingIdGenerator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RateLimitingIdGeneratorTest extends MediaWikiIntegrationTestCase {

	public function testGetNewId_success() {
		$user = $this->createMock( User::class );
		$user->expects( $this->once() )
			->method( 'pingLimiter' )
			->with( 'wikibase-idgenerator' )
			->willReturn( false );
		$contextSource = $this->createMock( IContextSource::class );
		$contextSource->expects( $this->once() )
			->method( 'getUser' )
			->willReturn( $user );
		$idGenerator = $this->createMock( IdGenerator::class );
		$idGenerator->expects( $this->once() )
			->method( 'getNewId' )
			->with( 'customType' )
			->willReturn( 12345 );

		$id = ( new RateLimitingIdGenerator( $idGenerator, $contextSource ) )
			->getNewId( 'customType' );

		$this->assertSame( 12345, $id );
	}

	public function testGetNewId_error() {
		$user = $this->createMock( User::class );
		$user->expects( $this->once() )
			->method( 'pingLimiter' )
			->with( 'wikibase-idgenerator' )
			->willReturn( true );
		$contextSource = $this->createMock( IContextSource::class );
		$contextSource->expects( $this->once() )
			->method( 'getUser' )
			->willReturn( $user );
		$idGenerator = $this->createMock( IdGenerator::class );
		$idGenerator->expects( $this->never() )
			->method( 'getNewId' );

		try {
			( new RateLimitingIdGenerator( $idGenerator, $contextSource ) )
				->getNewId( 'customType' );
			$this->fail( 'Should have thrown StorageException' );
		} catch ( StorageException $exception ) {
			$status = $exception->getStatus();
			$this->assertFalse( $status->isOK(), 'status must be fatal' );
			$key = $status->getMessage()->getKey();
			$this->assertSame( 'actionthrottledtext', $key );
		}
	}

}
