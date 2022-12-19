<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use EnhancedChangesList;
use Language;
use MediaWikiIntegrationTestCase;
use OldChangesList;
use RecentChange;
use User;
use Wikibase\Client\Hooks\ChangesListLinesHandler;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChange;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;

/**
 * @covers \Wikibase\Client\Hooks\ChangesListLinesHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Matěj Suchánek
 */
class ChangesListLinesHandlerTest extends MediaWikiIntegrationTestCase {

	private function getChangeFactory( int $times = 0 ): ExternalChangeFactory {
		$changeFactory = $this->createMock( ExternalChangeFactory::class );
		$changeFactory->expects( $this->exactly( $times ) )
			->method( 'newFromRecentChange' )
			->willReturn( $this->createMock( ExternalChange::class )
			);

		return $changeFactory;
	}

	private function getRecentChange( string $source, array $attributes = [] ): RecentChange {
		$recentChange = new RecentChange;
		$recentChange->setAttribs( array_merge( [
			'rc_source' => $source,
			'rc_namespace' => NS_MAIN,
			'rc_title' => 'Test',
			'rc_cur_id' => 12,
		], $attributes ) );
		return $recentChange;
	}

	private function getWikibaseChange( array $attributes = [] ): RecentChange {
		$recentChange = $this->getRecentChange( RecentChangeFactory::SRC_WIKIBASE, $attributes );
		$recentChange->counter = 1;
		return $recentChange;
	}

	/**
	 * @dataProvider nonWikibaseChangeProvider
	 */
	public function testOldChangesListLineNotTouched( $source ) {
		$formatter = $this->createMock( ChangeLineFormatter::class );
		$formatter->expects( $this->never() )
			->method( 'format' );
		$handler = new ChangesListLinesHandler(
			$this->getChangeFactory(),
			$formatter
		);

		$handler->onOldChangesListRecentChangesLine(
			$this->createMock( OldChangesList::class ),
			$line,
			$this->getRecentChange( $source ),
			$classes
		);
	}

	/**
	 * @dataProvider nonWikibaseChangeProvider
	 */
	public function testEnhancedChangesListBlockLineDataOnlyTouchedFlag( $source ) {
		$handler = new ChangesListLinesHandler(
			$this->getChangeFactory(),
			$this->createMock( ChangeLineFormatter::class )
		);

		$data = [];
		$handler->onEnhancedChangesListModifyBlockLineData(
			$this->createMock( EnhancedChangesList::class ),
			$data,
			$this->getRecentChange( $source )
		);

		$this->assertEquals( [ 'recentChangesFlags' => [ 'wikibase-edit' => false ] ], $data );
	}

	/**
	 * @dataProvider nonWikibaseChangeProvider
	 */
	public function testEnhancedChangesListLineDataOnlyTouchedFlag( $source ) {
		$handler = new ChangesListLinesHandler(
			$this->getChangeFactory(),
			$this->createMock( ChangeLineFormatter::class )
		);

		$data = [];
		$classes = [];
		$handler->onEnhancedChangesListModifyLineData(
			$this->createMock( EnhancedChangesList::class ),
			$data,
			[],
			$this->getRecentChange( $source ),
			$classes
		);

		$this->assertEquals( [ 'recentChangesFlags' => [ 'wikibase-edit' => false ] ], $data );
		$this->assertSame( [], $classes );
	}

	public function nonWikibaseChangeProvider() {
		return [
			[ RecentChange::SRC_EDIT ],
			[ RecentChange::SRC_NEW ],
			[ RecentChange::SRC_LOG ],
			[ RecentChange::SRC_CATEGORIZE ],
		];
	}

	public function testOldChangesListLine() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->createMock( ChangeLineFormatter::class );
		$formatter->expects( $this->once() )
			->method( 'format' )
			->willReturn( 'Formatted line' );

		$changesList = $this->createMock( OldChangesList::class );
		$changesList->expects( $this->once() )
			->method( 'recentChangesFlags' )
			->willReturn( 'flags' );
		$changesList->method( 'getUser' )
			->willReturn( $this->createMock( User::class ) );
		$changesList->method( 'getLanguage' )
			->willReturn( $this->createMock( Language::class ) );

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$line = '';
		$classes = [];
		$handler->onOldChangesListRecentChangesLine(
			$changesList,
			$line,
			$this->getWikibaseChange(),
			$classes
		);
		$this->assertEquals( 'Formatted line', $line );
		$this->assertSame( [], $classes );
	}

	public function oldChangesListLineFlagsProvider() {
		return [
			[
				[],
				'wikibase-edit',
			],
			[
				[ 'rc_minor' => true, 'rc_bot' => true ],
				'wikibase-edit,minor,bot',
			],
			[
				[ 'rc_minor' => 0, 'rc_bot' => 1 ],
				'wikibase-edit,bot',
			],
			[
				[ 'rc_minor' => true, 'rc_bot' => false ],
				'wikibase-edit,minor',
			],
		];
	}

	/**
	 * @dataProvider oldChangesListLineFlagsProvider
	 */
	public function testOldChangesListLineFlags( array $attributes, string $expected ) {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->createMock( ChangeLineFormatter::class );
		$formatter->expects( $this->once() )
			->method( 'format' )
			->willReturnArgument( 3 ); // $flags

		$changesList = $this->createMock( OldChangesList::class );
		$changesList->expects( $this->once() )
			->method( 'recentChangesFlags' )
			->willReturnCallback( function ( $flags, $sep ) {
				return implode( ',', array_keys( $flags, true ) );
			} );
		$changesList->method( 'getUser' )
			->willReturn( $this->createMock( User::class ) );
		$changesList->method( 'getLanguage' )
			->willReturn( $this->createMock( Language::class ) );

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$line = '';
		$classes = [];
		$handler->onOldChangesListRecentChangesLine(
			$changesList,
			$line,
			$this->getWikibaseChange( $attributes ),
			$classes
		);
		$this->assertEquals( $expected, $line );
		$this->assertSame( [], $classes );
	}

	public function testEnhancedChangesListModifyBlockLineData() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->createMock( ChangeLineFormatter::class );
		$formatter->expects( $this->once() )
			->method( 'formatDataForEnhancedBlockLine' );

		$changesList = $this->createMock( EnhancedChangesList::class );
		$changesList->method( 'getUser' )
			->willReturn( $this->createMock( User::class ) );
		$changesList->method( 'getLanguage' )
			->willReturn( $this->createMock( Language::class ) );

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$data = [];
		$handler->onEnhancedChangesListModifyBlockLineData(
			$changesList,
			$data,
			$this->getWikibaseChange()
		);
	}

	public function testEnhancedChangesListModifyLineData() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->createMock( ChangeLineFormatter::class );
		$formatter->expects( $this->once() )
			->method( 'formatDataForEnhancedLine' );

		$changesList = $this->createMock( EnhancedChangesList::class );
		$changesList->method( 'getUser' )
			->willReturn( $this->createMock( User::class ) );
		$changesList->method( 'getLanguage' )
			->willReturn( $this->createMock( Language::class ) );

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$data = [];
		$classes = [];
		$handler->onEnhancedChangesListModifyLineData(
			$changesList,
			$data,
			[],
			$this->getWikibaseChange(),
			$classes
		);
	}

}
