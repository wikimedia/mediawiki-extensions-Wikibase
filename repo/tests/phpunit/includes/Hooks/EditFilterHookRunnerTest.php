<?php

namespace Wikibase\Repo\Tests\Hooks;

use Content;
use FauxRequest;
use IContextSource;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemContent;
use Wikibase\Repo\Hooks\EditFilterHookRunner;

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
	 * @return EditFilterHookRunner
	 */
	public function getEditFilterHookRunner() {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );

		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getSerialization(), NS_MAIN );
			} ) );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getNamespaceForType' )
			->will( $this->returnValue( NS_MAIN ) );

		$entityContentFactory = $this->getMockBuilder( 'Wikibase\Repo\Content\EntityContentFactory' )
			->disableOriginalConstructor()
			->getMock();
		$entityContentFactory->expects( $this->any() )
			->method( 'newFromEntity' )
			->with( $this->isInstanceOf( 'Wikibase\DataModel\Entity\Entity' ) )
			->will( $this->returnValue( ItemContent::newEmpty() ) );
		$entityContentFactory->expects( $this->any() )
			->method( 'newFromRedirect' )
			->with( $this->isInstanceOf( 'Wikibase\DataModel\Entity\EntityRedirect' ) )
			->will( $this->returnValue( ItemContent::newEmpty() ) );

		return new EditFilterHookRunner(
			$entityTitleLookup,
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

		$runner = $this->getEditFilterHookRunner();
		$status = $runner->run(
			new Item(),
			User::newFromName( 'EditFilterHookRunnerTestUser' ),
			'summary'
		);
		$this->assertTrue( $status->isGood() );
	}

	public function runData() {
		return array(
			'good existing item' => array(
				Status::newGood(),
				new Item( new ItemId( 'Q444' ) ),
				array(
					'status' => Status::newGood(),
					'title' => 'Q444',
					'namespace' => NS_MAIN,
				)
			),
			'fatal existing item' => array(
				Status::newFatal( 'foo' ),
				new Item( new ItemId( 'Q444' ) ),
				array(
					'status' => Status::newFatal( 'foo' ),
					'title' => 'Q444',
					'namespace' => NS_MAIN,
				)
			),
			'good new item' => array(
				Status::newGood(),
				new Item(),
				array(
					'status' => Status::newGood(),
					'title' => 'NewItem',
					'namespace' => NS_MAIN,
				)
			),
			'fatal new item' => array(
				Status::newFatal( 'bar' ),
				new Item(),
				array(
					'status' => Status::newFatal( 'bar' ),
					'title' => 'NewItem',
					'namespace' => NS_MAIN,
				)
			),
			'good existing entityredirect' => array(
				Status::newGood(),
				new EntityRedirect( new ItemId( 'Q12' ), new ItemId( 'Q13' ) ),
				array(
					'status' => Status::newGood(),
					'title' => 'Q12',
					'namespace' => NS_MAIN,
				)
			),
			'fatal existing entityredirect' => array(
				Status::newFatal( 'baz' ),
				new EntityRedirect( new ItemId( 'Q12' ), new ItemId( 'Q13' ) ),
				array(
					'status' => Status::newFatal( 'baz' ),
					'title' => 'Q12',
					'namespace' => NS_MAIN,
				)
			),
		);
	}

	/**
	 * @param Status $inputStatus
	 * @param Entity|EntityRedirect|null $new
	 * @param array $expected
	 *
	 * @dataProvider runData
	 */
	public function testRun_hooksAreCalled( Status $inputStatus, $new, array $expected ) {
		$hooks = array_merge(
			$GLOBALS['wgHooks'],
			array( 'EditFilterMergedContent' => array() )
		);

		$testCase = $this;

		$hooks['EditFilterMergedContent'][] =
			function(
				IContextSource $context,
				Content $content,
				Status $status,
				$summary,
				User $user,
				$minoredit
			) use ( $testCase, $expected, $inputStatus )
			{
				$testCase->assertEquals( $expected['title'], $context->getTitle()->getFullText() );
				$testCase->assertSame( $context->getTitle(), $context->getWikiPage()->getTitle() );
				$testCase->assertEquals( $expected['namespace'], $context->getTitle()->getNamespace() );
				$testCase->assertEquals( ItemContent::newEmpty(), $content );
				$testCase->assertTrue( $status->isGood() );
				$testCase->assertTrue( is_string( $summary ) );
				$testCase->assertEquals( 'EditFilterHookRunnerTestUser', $user->getName() );
				$testCase->assertTrue( is_bool( $minoredit ) );

				//Change the status
				$status->merge( $inputStatus );
			};

		$this->setMwGlobals( array(
			'wgHooks' => $hooks
		) );

		$runner = $this->getEditFilterHookRunner();
		$status = $runner->run(
			$new,
			User::newFromName( 'EditFilterHookRunnerTestUser' ),
			'summary'
		);
		$this->assertEquals( $expected['status'], $status );
	}

}
