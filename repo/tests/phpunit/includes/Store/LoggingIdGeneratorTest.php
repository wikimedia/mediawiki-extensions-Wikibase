<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use Exception;
use FauxRequest;
use MediaWikiIntegrationTestCase;
use Psr\Log\LogLevel;
use TestLogger;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\LoggingIdGenerator;

/**
 * @covers \Wikibase\Repo\Store\LoggingIdGenerator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LoggingIdGeneratorTest extends MediaWikiIntegrationTestCase {

	public function testGetNewId() {
		$requestPostValues = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => '{}',
		];
		$this->setMwGlobals( 'wgRequest', new FauxRequest( $requestPostValues, true ) );

		$idGenerator = $this->createMock( IdGenerator::class );
		$idGenerator->expects( $this->once() )
			->method( 'getNewId' )
			->with( 'customType' )
			->willReturn( 12345 );

		$logger = new TestLogger();
		$logger->setCollect( true );
		$logger->setCollectContext( true );

		$id = ( new LoggingIdGenerator( $idGenerator, $logger ) )->getNewId( 'customType' );

		$this->assertSame( 12345, $id );
		$logs = $logger->getBuffer();
		$this->assertCount( 1, $logs );
		[ $level, $message, $context ] = $logs[0];
		$this->assertSame( LogLevel::INFO, $level );
		$this->assertSame( 'customType', $context['idType'] );
		$this->assertSame( 12345, $context['id'] );
		$this->assertSame( $requestPostValues, $context['requestPostValues'] );
		$this->assertInstanceOf( Exception::class, $context['exception'] );
	}

}
