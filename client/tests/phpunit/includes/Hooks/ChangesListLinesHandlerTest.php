<?php

namespace Wikibase\Client\Tests\Hooks;

use EnhancedChangesList;
use MediaWikiTestCase;
use OldChangesList;
use RecentChange;
use Title;
use Wikibase\Client\Hooks\ChangesListLinesHandler;
use Wikibase\Client\RecentChanges\ChangeLineFormatter;
use Wikibase\Client\RecentChanges\ExternalChange;
use Wikibase\Client\RecentChanges\ExternalChangeFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;

/**
 * @covers Wikibase\Client\Hooks\ChangesListLinesHandler
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Matěj Suchánek
 */
class ChangesListLinesHandlerTest extends MediaWikiTestCase {

	/**
	 * @return ChangeLineFormatter
	 */
	private function getChangeLineFormatter() {
		$formatter = $this->getMockBuilder( ChangeLineFormatter::class )
			->disableOriginalConstructor()
			->getMock();

		return $formatter;
	}

	/**
	 * @param int $times
	 * @return ExternalChangeFactory
	 */
	private function getChangeFactory( $times = 0 ) {
		$changeFactory = $this->getMockBuilder( ExternalChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$changeFactory->expects( $this->exactly( $times ) )
			->method( 'newFromRecentChange' )
			->willReturn( $this->getMockBuilder( ExternalChange::class )
				->disableOriginalConstructor()
				->getMock()
			);

		return $changeFactory;
	}

	/**
	 * @param string
	 * @return RecentChange
	 */
	private function getRecentChange( $source ) {
		$recentChange = new RecentChange;
		$recentChange->setAttribs( [ 'rc_source' => $source ] );
		return $recentChange;
	}

	/**
	 * @return RecentChange
	 */
	private function getWikibaseChange() {
		$recentChange = $this->getRecentChange( RecentChangeFactory::SRC_WIKIBASE );
		$recentChange->counter = 1;
		$recentChange->mTitle = $this->getMock( Title::class );
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
		$changesList = $this->getMockBuilder( OldChangesList::class )
			->disableOriginalConstructor()
			->getMock();

		$recentChange = $this->getRecentChange( $source );

		$handler->doOldChangesListRecentChangesLine(
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
		$changesList = $this->getMockBuilder( EnhancedChangesList::class )
			->disableOriginalConstructor()
			->getMock();

		$recentChange = $this->getRecentChange( $source );

		$data = [];
		$handler->doEnhancedChangesListModifyBlockLineData(
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
		$changesList = $this->getMockBuilder( EnhancedChangesList::class )
			->disableOriginalConstructor()
			->getMock();

		$recentChange = $this->getRecentChange( $source );

		$data = [];
		$classes = [];
		$handler->doEnhancedChangesListModifyLineData(
			$changesList,
			$data,
			[],
			$recentChange,
			$classes
		);

		$this->assertEquals( [ 'recentChangesFlags' => [ 'wikibase-edit' => false ] ], $data );
		$this->assertEmpty( $classes );
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

		$changesList = $this->getMockBuilder( OldChangesList::class )
			->disableOriginalConstructor()
			->getMock();
		$changesList->expects( $this->once() )
			->method( 'recentChangesFlags' )
			->willReturn( 'flags' );

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$line = '';
		$classes = [];
		$handler->doOldChangesListRecentChangesLine(
			$changesList,
			$line,
			$this->getWikibaseChange(),
			$classes
		);
		$this->assertEquals( 'Formatted line', $line );
		$this->assertEmpty( $classes );
	}

	public function testEnhancedChangesListModifyBlockLineData() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->getChangeLineFormatter();
		$formatter->expects( $this->once() )
			->method( 'formatDataForEnhancedBlockLine' )
			->willReturn( true );

		$changesList = $this->getMockBuilder( EnhancedChangesList::class )
			->disableOriginalConstructor()
			->getMock();

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$data = [];
		$handler->doEnhancedChangesListModifyBlockLineData(
			$changesList,
			$data,
			$this->getWikibaseChange()
		);
	}

	public function testEnhancedChangesListModifyLineData() {
		$changeFactory = $this->getChangeFactory( 1 );

		$formatter = $this->getChangeLineFormatter();
		$formatter->expects( $this->once() )
			->method( 'formatDataForEnhancedLine' )
			->willReturn( true );

		$changesList = $this->getMockBuilder( EnhancedChangesList::class )
			->disableOriginalConstructor()
			->getMock();

		$handler = new ChangesListLinesHandler( $changeFactory, $formatter );

		$data = [];
		$classes = [];
		$handler->doEnhancedChangesListModifyLineData(
			$changesList,
			$data,
			[],
			$this->getWikibaseChange(),
			$classes
		);
	}

}
