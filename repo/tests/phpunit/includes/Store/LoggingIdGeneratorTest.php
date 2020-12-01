<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Store;

use Exception;
use FauxRequest;
use LogicException;
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

	public function testGetNewId_success() {
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
		$this->assertCount( 2, $logs );
		[ $debugLevel, $debugMessage, $debugContext ] = $logs[0];
		[ $infoLevel, $infoMessage, $infoContext ] = $logs[1];
		$this->assertSame( LogLevel::DEBUG, $debugLevel );
		$this->assertSame( LogLevel::INFO, $infoLevel );
		foreach ( [ $debugContext, $infoContext ] as $context ) {
			$this->assertSame( 'customType', $context['idType'] );
			$this->assertSame( $requestPostValues, $context['requestPostValues'] );
			$this->assertInstanceOf( Exception::class, $context['exception'] );
		}
		$this->assertSame( 12345, $infoContext['id'] );
	}

	public function testGetNewId_error() {
		$requestPostValues = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => '{}',
		];
		$this->setMwGlobals( 'wgRequest', new FauxRequest( $requestPostValues, true ) );

		$exception = new LogicException();
		$idGenerator = $this->createMock( IdGenerator::class );
		$idGenerator->expects( $this->once() )
			->method( 'getNewId' )
			->with( 'customType' )
			->willThrowException( $exception );

		$logger = new TestLogger();
		$logger->setCollect( true );
		$logger->setCollectContext( true );

		try {
			( new LoggingIdGenerator( $idGenerator, $logger ) )->getNewId( 'customType' );
			$this->fail( 'Should have (re)thrown the inner generator’s $exception' );
		} catch ( LogicException $logicException ) {
			$this->assertSame( $exception, $logicException );
		}

		$logs = $logger->getBuffer();
		$this->assertCount( 2, $logs );
		[ $firstLevel, $firstMessage, $firstContext ] = $logs[0];
		[ $secondLevel, $secondMessage, $secondContext ] = $logs[1];
		$this->assertSame( LogLevel::DEBUG, $firstLevel );
		$this->assertSame( LogLevel::DEBUG, $secondLevel );
		foreach ( [ $firstContext, $secondContext ] as $context ) {
			$this->assertSame( 'customType', $context['idType'] );
			$this->assertSame( $requestPostValues, $context['requestPostValues'] );
		}
		$this->assertInstanceOf( Exception::class, $firstContext['exception'] );
		$this->assertSame( $exception, $secondContext['exception'] );
	}

}
