<?php

namespace Wikibase\Test;

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
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\HistoryEntityAction;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\HistoryEntityAction
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class HistoryEntityActionTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param string $title
	 *
	 * @return Article
	 */
	private function getPage( $title ) {
		$page = $this->getMockBuilder( Article::class )
			->disableOriginalConstructor()
			->getMock();
		$page->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( $title ) ) );

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
		$context->expects( $this->once() )
			->method( 'getConfig' )
			->will( $this->returnValue( new HashConfig( array(
				'UseFileCache' => false,
				'UseMediaWikiUIEverywhere' => false,
			) ) ) );
		$context->expects( $this->any() )
			->method( 'getRequest' )
			->will( $this->returnValue( new WebRequest() ) );
		$context->expects( $this->any() )
			->method( 'getUser' )
			->will( $this->returnValue( new User() ) );
		$context->expects( $this->any() )
			->method( 'msg' )
			->will( $this->returnCallback( function() {
				return call_user_func_array( 'wfMessage', func_get_args() )->inLanguage( 'qqx' );
			} ) );

		$context->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $output ) );

		$output->expects( $this->once() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		return $context;
	}

	public function testGivenUnDeserializableRevision_historyActionDoesNotFail() {
		$page = $this->getPage( 'Page title' );
		$page->expects( $this->any() )
			->method( 'getPage' )
			->will( $this->throwException( new MWContentSerializationException() ) );

		$output = $this->getOutput();
		$output->expects( $this->once() )
			->method( 'setPageTitle' )
			->with( '(history-title: Page title)' );

		$action = new HistoryEntityAction(
			$page,
			$this->getContext( $output ),
			$this->getEntityIdLookup(),
			$this->getMock( LabelDescriptionLookup::class )
		);
		$action->show();
	}

	private function getEntityIdLookup() {
		$entityIdLookup = $this->getMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnCallback( function( Title $title ) {
				if ( preg_match( '/^Q(\d+)$/', $title->getText(), $m ) ) {
					return new ItemId( $m[0] );
				}

				return null;
			} ) );

		return $entityIdLookup;
	}

}
