<?php

declare( strict_types=1 );

namespace Wikibase\Lib\Tests\Store;

use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\ServiceBySourceAndTypeDispatcher;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\SourceAndTypeDispatchingRedirectChecker;

/**
 * @covers \Wikibase\Lib\Store\SourceAndTypeDispatchingRedirectChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SourceAndTypeDispatchingRedirectCheckerTest extends TestCase {

	/** @var array */
	private $callbacks;

	/** @var EntitySourceLookup|MockObject */
	private $entitySourceLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->callbacks = [];
		$this->entitySourceLookup = $this->createStub( EntitySourceLookup::class );
	}

	public function testGivenNoRedirectCheckerDefinedForEntityType_throwsException() {
		$entityId = new NumericPropertyId( 'P123' );

		$this->callbacks['some-other-source']['property'] = $this->newNeverCalledMockChecker();

		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->entitySourceLookup->expects( $this->once() )
			->method( 'getEntitySourceById' )
			->with( $entityId )
			->willReturn( NewDatabaseEntitySource::havingName( 'foo' )->build() );

		$this->expectException( LogicException::class );

		$this->newRedirectChecker()->isRedirect( $entityId );
	}

	public function testGivenRedirectCheckerDefinedForEntitySourceAndType_usesRespectiveRedirectChecker() {
		$entityId = new NumericPropertyId( 'P321' );
		$isRedirect = true;
		$sourceName = 'wikidorta';

		$this->callbacks['some-other-source']['property'] = $this->newNeverCalledMockChecker();
		$this->callbacks[$sourceName]['property'] = function () use ( $entityId, $isRedirect ) {
			$redirectChecker = $this->createMock( EntityRedirectChecker::class );
			$redirectChecker->expects( $this->once() )
				->method( 'isRedirect' )
				->with( $entityId )
				->willReturn( $isRedirect );

			return $redirectChecker;
		};
		$this->entitySourceLookup = $this->createMock( EntitySourceLookup::class );
		$this->entitySourceLookup->expects( $this->once() )
			->method( 'getEntitySourceById' )
			->with( $entityId )
			->willReturn( NewDatabaseEntitySource::havingName( $sourceName )->build() );

		$this->assertSame( $isRedirect, $this->newRedirectChecker()->isRedirect( $entityId ) );
	}

	private function newNeverCalledMockChecker(): EntityRedirectChecker {
		$redirectChecker = $this->createMock( EntityRedirectChecker::class );
		$redirectChecker->expects( $this->never() )->method( $this->anything() );

		return $redirectChecker;
	}

	private function newRedirectChecker(): SourceAndTypeDispatchingRedirectChecker {
		return new SourceAndTypeDispatchingRedirectChecker(
			new ServiceBySourceAndTypeDispatcher(
				EntityRedirectChecker::class,
				$this->callbacks
			),
			$this->entitySourceLookup
		);
	}

}
