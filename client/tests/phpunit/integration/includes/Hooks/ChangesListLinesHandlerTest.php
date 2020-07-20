<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use EnhancedChangesList;
use Language;
use MediaWikiIntegrationTestCase;
use OldChangesList;
use RecentChange;
use Title;
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

	private function getChangeLineFormatter() {
		return $this->createMock( ChangeLineFormatter::class );
	}

	private function getChangeFactory( int $times = 0 ): ExternalChangeFactory {
		$changeFactory = $this->createMock( ExternalChangeFactory::class );
		$changeFactory->expects( $this->exactly( $times ) )
			->method( 'newFromRecentChange' )
			->willReturn( $this->getMockBuilder( ExternalChange::class )
				->disableOriginalConstructor()
				->getMock()
			);

		return $changeFactory;
	}

	private function getRecentChange( string $source ): RecentChange {
		$recentChange = new RecentChange;
		$recentChange->setAttribs( [ 'rc_source' => $source ] );
		return $recentChange;
	}

	private function getWikibaseChange(): RecentChange {
		$recentChange = $this->getRecentChange( RecentChangeFactory::SRC_WIKIBASE );
		$recentChange->counter = 1;
		$recentChange->mTitle = $this->createMock( Title::class );
		return $recentChange;
	}

	/**
	 * @dataProvider nonWikibaseChangeProvider
	 */
	public function testOldChangesListLineNotTouched( $source ) {
		$formatter = $this->getChangeLineFormatter();
		$formatter->expects( $this->never() )
			->method( 'format' );
		$handler = new ChangesListLinesHandler(
			$this->getChangeFactory(),
			$formatter
		);
		$changesList = $changesList = $this->createMock( OldChangesList::class );

		$recentChange = $this->getRecentChange( $source );

		$handler->onOldChangesListRecentChangesLine(
			$changesList,
			$line,
			$recentChange,
			$classes
		);
	}

	/**
	 * @dataProvider nonWikibaseChangeProvider
	 */
	public function testEnhancedChangesListBlockLineDataOnlyTouchedFlag( $source ) {
		$handler = new ChangesListLinesHandler(
			$this->getChangeFactory(),
			$this->getChangeLineFormatter()
		);
		$changesList = $changesList = $this->createMock( EnhancedChangesList::class );

		$recentChange = $this->getRecentChange( $source );

		$data = [];
		$handler->onEnhancedChangesListModifyBlockLineData(
			$changesList,
			$data,
			$recentChange
		);

		$this->assertEquals( [ 'recentChangesFlags' => [ 'wikibase-edit' => false ] ], $data );
	}

	/**
	 * @dataProvider nonWikibaseChangeProvider
	 */
	public function testEnhancedChangesListLineDataOnlyTouchedFlag( $source ) {
		$handler = new ChangesListLinesHandler(
			$this->getChangeFactory(),
			$this->getChangeLineFormatter()
		);
		$changesList = $changesList = $this->createMock( EnhancedChangesList::class );

		$recentChange = $this->getRecentChange( $source );

		$data = [];
		$classes = [];
		$handler->onEnhancedChangesListModifyLineData(
			$changesList,
			$data,
			[],
			$recentChange,
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
			[ RecentChange::SRC_CATEGORIZE ]
		];
	}

	public function testOldChangesListLine() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->getChangeLineFormatter();
		$formatter->expects( $this->once() )
			->method( 'format' )
			->willReturn( 'Formatted line' );

		$changesList = $changesList = $this->createMock( OldChangesList::class );
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

	public function testEnhancedChangesListModifyBlockLineData() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->getChangeLineFormatter();
		$formatter->expects( $this->once() )
			->method( 'formatDataForEnhancedBlockLine' );

		$changesList = $changesList = $this->createMock( EnhancedChangesList::class );
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

		$formatter = $this->getChangeLineFormatter();
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
