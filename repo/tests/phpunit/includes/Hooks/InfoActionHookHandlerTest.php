<?php

namespace Wikibase\Repo\Tests\Hooks;

use IContextSource;
use RequestContext;
use Title;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\Sql\SqlSubscriptionLookup;

/**
 * @covers Wikibase\Repo\Hooks\InfoActionHookHandler
 *
 * @group WikibaseRepo
 * @group InfoActionHookHandler
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class InfoActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( $expected, $context, $pageInfo, $enabled, $subscriptions, $message ) {
		$hookHandler = $this->newHookHandler( $enabled, $subscriptions );
		$pageInfo = $hookHandler->handle( $context, $pageInfo );
		echo 'Actual';
		var_dump( $pageInfo );
		echo 'Expected';
		var_dump( $expected );
		$this->assertEquals( $expected, $pageInfo, $message );
	}

	public function handleProvider() {
		$context = $this->getContext();

		$cases = [];

		$cases[] = array(
			array(
				'header-properties' => array(
					array(
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>dewiki</li><li>enwiki</li></ul>",
					),
				)
			),
			$context, array( 'header-properties' => array() ), true, [ 'dewiki', 'enwiki' ],
			'dewiki and enwiki'
		);

		$cases[] = array(
			array( 'header-properties' => array(
					array(
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>elwiki</li></ul>",
					),
				)
			),
			$context,
			array( 'header-properties' => array() ),
			false,
			[ 'elwiki' ],
			'elwiki'
		);

		$cases[] = array(
			array(
				'header-properties' => array(
					array(
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						$context->msg( 'wikibase-pageinfo-subscription-none' )->escaped()
					)
				)
			),
			$context, array( 'header-properties' => array() ), true, false,
			'no subscription'
		);

		return $cases;
	}

	/**
	 * @param bool $enabled
	 * @param ItemId $entityId
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, $subscriptions ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		$subLookup = $this->getMockBuilder( SqlSubscriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$subLookup->expects( $this->any() )
			->method( 'queryIdBasedSubscriptions' )
			->will( $this->returnValue( $subscriptions ) );

		$siteLookup = $this->getMockBuilder( SiteLookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getSite' ] )
			->getMock();

		$siteLookup->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnValue( false ) );

		$hookHandler = new InfoActionHookHandler(
			$namespaceLookup,
			$subLookup,
			$siteLookup
		);

		return $hookHandler;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Q4' ) );

		$context = new RequestContext();
		$context->setTitle( $title );

		$context->setLanguage( 'en' );

		return $context;
	}

}
