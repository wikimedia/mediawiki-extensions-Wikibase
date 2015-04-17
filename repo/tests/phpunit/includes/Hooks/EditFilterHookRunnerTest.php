<?php

namespace Wikibase\Repo\Tests\Hooks;

use FauxRequest;
use IContextSource;
use RequestContext;
use Status;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Hooks\EditFilterHookRunner
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class EditFilterHookRunnerTest extends \MediaWikiTestCase {

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockEntityTitleLookup() {
		return $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
	}

	/**
	 * @param EntityTitleLookup $titleLookup
	 *
	 * @return EditFilterHookRunner
	 */
	public function getEditFilterHookRunner( EntityTitleLookup $titleLookup ){
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$entityContentFactory = $this->getMockBuilder( 'Wikibase\Repo\Content\EntityContentFactory' )
			->disableOriginalConstructor()
			->getMock();
		$entityContentFactory->expects( $this->any() )
			->method( 'newFromEntity' )
			->with( $this->isInstanceOf( 'Wikibase\DataModel\Entity\Entity' ) )
			->will( $this->returnValue( ItemContent::newEmpty() ) );
		return new EditFilterHookRunner(
			$titleLookup,
			$entityContentFactory,
			$context
		);
	}

	public function testRun_noHooksRegisteredGoodStatus() {
		$hooks = array_merge(
			$GLOBALS['wgHooks'],
			array( 'EditFilterMergedContent' => array() )
		);
		$this->setMwGlobals( 'wgHooks', $hooks );

		$runner = $this->getEditFilterHookRunner( $this->getMockEntityTitleLookup() );
		$status = $runner->run( new Item(), User::newFromName( 'EditFilterHookRunnerTestUser' ), 'summary' );
		$this->assertTrue( $status->isGood() );
	}

	public function runData() {
		return array(
			array( Status::newGood() ),
			array( Status::newFatal( 'foo' ) ),
		);
	}

	/**
	 * @dataProvider runData
	 */
	public function testRun_hooksAreCalled( $expectedStatus ) {
		$hooks = array_merge(
			$GLOBALS['wgHooks'],
			array( 'EditFilterMergedContent' => array() )
		);

		$testCase = $this;

		$hooks['EditFilterMergedContent'][] = function( $context, $content, Status $status, $summary, $user, $minoredit ) use( $testCase, $expectedStatus ) {
			$testCase->assertInstanceOf( 'IContextSource', $context );
			$testCase->assertInstanceOf( 'Content', $content );
			$testCase->assertInstanceOf( 'Status', $status );
			$testCase->assertTrue( is_string( $summary ) );
			$testCase->assertInstanceOf( 'User', $user );
			$testCase->assertTrue( is_bool( $minoredit ) );
			//Change the status
			$status->merge( $expectedStatus );
		};

		$this->setMwGlobals( array(
			'wgHooks' => $hooks
		) );

		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$runner = $this->getEditFilterHookRunner( $titleLookup );
		$status = $runner->run(
			new Item(),
			User::newFromName( 'EditFilterHookRunnerTestUser' ),
			'summary'
		);
		$this->assertEquals( $expectedStatus, $status );
	}

}