<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\Specials\SpecialDispatchStats;
use Wikibase\Repo\Store\Sql\DispatchStats;

/**
 * @covers \Wikibase\Repo\Specials\SpecialDispatchStats
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0-or-later
 */
class SpecialDispatchStatsTest extends SpecialPageTestBase {

	/** @var DispatchStats */
	private $dispatchStats;

	protected function newSpecialPage(): SpecialDispatchStats {
		if ( $this->dispatchStats === null ) {
			throw new \BadMethodCallException( '$this->dispatchStats must be set before calling this method!' );
		}

		return new SpecialDispatchStats(
			$this->dispatchStats
		);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->dispatchStats = null;
	}

	public function testExecute_empty(): void {
		$dispatchStatsMock = $this->createMock( DispatchStats::class );
		$dispatchStatsMock->method( 'getDispatchStats' )->willReturn( [
			'numberOfChanges' => 0,
			'numberOfEntities' => 0,
			'freshestTime' => null,
			'stalestTime' => null,
		] );
		$this->dispatchStats = $dispatchStatsMock;

		[ $output ] = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-dispatchstats-intro', $output );
		$this->assertStringContainsString( 'wikibase-dispatchstats-empty', $output );
	}

	public function testExecute_exact(): void {
		$dispatchStatsMock = $this->createMock( DispatchStats::class );
		$dispatchStatsMock->method( 'getDispatchStats' )->willReturn( [
			'numberOfChanges' => 3,
			'numberOfEntities' => 2,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
		$this->dispatchStats = $dispatchStatsMock;

		[ $output ] = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-dispatchstats-intro', $output );
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-oldest: 15:51, 18 (october) 2021)',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-newest: 15:56, 18 (october) 2021)',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-number-of-changes-in-queue: 3',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-number-of-entities-in-queue: 2',
			$output
		);
	}

	public function testExecute_estimated(): void {
		$dispatchStatsMock = $this->createMock( DispatchStats::class );
		$dispatchStatsMock->method( 'getDispatchStats' )->willReturn( [
			'estimatedNumberOfChanges' => 10000,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
		$this->dispatchStats = $dispatchStatsMock;

		[ $output ] = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-dispatchstats-intro', $output );
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-oldest: 15:51, 18 (october) 2021)',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-newest: 15:56, 18 (october) 2021)',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-estimate: 10,000)',
			$output
		);
	}

	public function testExecute_minimum(): void {
		$dispatchStatsMock = $this->createMock( DispatchStats::class );
		$dispatchStatsMock->method( 'getDispatchStats' )->willReturn( [
			'minimumNumberOfChanges' => 5000,
			'freshestTime' => '20211018155646',
			'stalestTime' => '20211018155100',
		] );
		$this->dispatchStats = $dispatchStatsMock;

		[ $output ] = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'wikibase-dispatchstats-intro', $output );
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-oldest: 15:51, 18 (october) 2021)',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-newest: 15:56, 18 (october) 2021)',
			$output
		);
		$this->assertStringContainsString(
			'(wikibase-dispatchstats-above: 5,000)',
			$output
		);
	}
}
