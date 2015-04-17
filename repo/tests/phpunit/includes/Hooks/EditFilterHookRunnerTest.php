<?php

namespace Wikibase\Repo\Tests\Hooks;

use FauxRequest;
use IContextSource;
use RequestContext;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
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
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		return $titleLookup;
	}

	/**
	 * @param EntityTitleLookup $titleLookup
	 *
	 * @return EditFilterHookRunner
	 */
	public function getEditFilterHookRunner( $titleLookup ){
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		return new EditFilterHookRunner( $titleLookup, $context );
	}

	public function testRun_noHooksRegistered() {
		$hooks = array_merge(
			$GLOBALS['wgHooks'],
			array( 'EditFilterMergedContent' => array() )
		);
		$this->setMwGlobals( 'wgHooks', $hooks );

		$runner = $this->getEditFilterHookRunner( $this->getMockEntityTitleLookup() );
		$status = $runner->run( new Item(), User::newFromName( 'EditFilterHookRunnerTestUser' ), 'summary' );
		$this->assertTrue( $status->isGood() );
	}

	public function testRun_withNewEntity() {
		$hooks = array_merge(
			$GLOBALS['wgHooks'],
			array( 'EditFilterMergedContent' => array() )
		);

		$testCase = $this;

		$hooks['EditFilterMergedContent'][] = function( IContextSource $context ) use( $testCase ) {
			$entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();

			$page = $context->getWikiPage();
			$title = $page->getTitle();
			$contentModel = $title->getContentModel();
			$testCase->assertTrue( $entityContentFactory->isEntityContentModel( $contentModel ) );
		};

		$this->setMwGlobals( array(
			'wgHooks' => $hooks
		) );

		$item = new Item();
		$item->setLabel( 'en', 'omg' );

		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$runner = $this->getEditFilterHookRunner( $titleLookup );
		$runner->run( $item, User::newFromName( 'EditFilterHookRunnerTestUser' ), 'summary' );
	}

}