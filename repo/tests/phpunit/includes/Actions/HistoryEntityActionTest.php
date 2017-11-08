<?php

namespace Wikibase\Repo\Tests\Actions;

use Article;
use HashConfig;
use IContextSource;
use MWContentSerializationException;
use OutputPage;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Title;
use User;
use WebRequest;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\HistoryEntityAction;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\HistoryEntityAction
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class HistoryEntityActionTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return Article
	 */
	private function getArticle() {
		$page = $this->getMockBuilder( Article::class )
			->disableOriginalConstructor()
			->getMock();
		$page->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( 'Page title' ) ) );
		$page->expects( $this->never() )
			->method( 'getPage' )
			// Deserializing the full entity may fail, see https://gerrit.wikimedia.org/r/262881
			->will( $this->throwException( new MWContentSerializationException() ) );

		return $page;
	}

	/**
	 * @return OutputPage
	 */
	private function getOutput() {
		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		return $output;
	}

	/**
	 * @param PHPUnit_Framework_MockObject_MockObject $output
	 *
	 * @return IContextSource
	 */
	private function getContext( PHPUnit_Framework_MockObject_MockObject $output ) {
		$context = $this->getMock( IContextSource::class );
		$context->method( 'getConfig' )
			->will( $this->returnValue( new HashConfig( [
				'UseFileCache' => false,
				'UseMediaWikiUIEverywhere' => false,
				'Localtimezone' => 'UTC',
			] ) ) );
		$context->method( 'getRequest' )
			->will( $this->returnValue( new WebRequest() ) );
		$context->method( 'getUser' )
			->will( $this->returnValue( new User() ) );
		$context->method( 'msg' )
			->will( $this->returnCallback( function() {
				return call_user_func_array( 'wfMessage', func_get_args() )->inLanguage( 'qqx' );
			} ) );

		$context->method( 'getOutput' )
			->will( $this->returnValue( $output ) );

		$output->expects( $this->once() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		return $context;
	}

	public function pageTitleProvider() {
		return [
			'fallback to parent' => [
				null,
				null,
				'(history-title: Page title)'
			],
			'without label' => [
				new ItemId( 'Q1' ),
				null,
				'(wikibase-history-title-without-label: Q1)'
			],
			'with label' => [
				new ItemId( 'Q2' ),
				new Term( 'qqx', 'Label' ),
				'(wikibase-history-title-with-label: Q2, Label)'
			],
		];
	}

	/**
	 * @dataProvider pageTitleProvider
	 */
	public function testGetPageTitle( ItemId $entityId = null, Term $label = null, $expected ) {
		$entityIdLookup = $this->getMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnValue( $entityId ) );

		$labelLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelLookup->method( 'getLabel' )
			->will( $this->returnValue( $label ) );

		$output = $this->getOutput();
		$output->expects( $this->once() )
			->method( 'setPageTitle' )
			->with( $expected );

		$action = new HistoryEntityAction(
			$this->getArticle(),
			$this->getContext( $output ),
			$entityIdLookup,
			$labelLookup
		);
		$action->show();
	}

}
