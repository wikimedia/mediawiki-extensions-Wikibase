<?php

namespace Wikibase\Test;

use User, WebRequest, WikiPage, Action, IContextSource;
use RequestContext;
use FauxRequest;
use MediaWikiTestCase;
use OutputPage;
use Status;

use \Wikibase\Item;
use \Wikibase\ItemObject;
use \Wikibase\ItemContent;

/**
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @todo: move this to core (except the test item stuff of course)
 */
class ActionTestCase extends MediaWikiTestCase {

	protected $savedGlobals;
	protected $permissionsChanged = false;

	public function setUp() {
		parent::setUp();

		$this->savedGlobals = array();

		$this->savedGlobals['wgUser'] = $GLOBALS['wgUser'];
		$this->savedGlobals['wgLang'] = $GLOBALS['wgLang'];
		$this->savedGlobals['wgRequest'] = $GLOBALS['wgRequest'];
		$this->savedGlobals['wgGroupPermissions'] = $GLOBALS['wgGroupPermissions'];

		global $wgLang;
		$wgLang = \Language::factory( 'qqx' );

		\ApiQueryInfo::resetTokenCache();
	}

	public function tearDown() {
		global $wgUser;

		foreach ( $this->savedGlobals as $k => $v ) {
			global $$k;
			$$k = $v;
		}

		\ApiQueryInfo::resetTokenCache();

		if ( $this->permissionsChanged ) {
			# reset rights cache
			$wgUser->addGroup( "dummy" );
			$wgUser->removeGroup( "dummy" );
		}

		parent::tearDown();
	}

	function applyPermissions( $permissions ) {
		global $wgGroupPermissions, $wgUser;

		if ( !$permissions ) {
			return;
		}

		foreach ( $permissions as $group => $rights ) {
			if ( !empty( $wgGroupPermissions[ $group ] ) ) {
				$wgGroupPermissions[ $group ] = array_merge( $wgGroupPermissions[ $group ], $rights );
			} else {
				$wgGroupPermissions[ $group ] = $rights;
			}
		}

		$this->permissionsChanged = true;

		# reset rights cache
		$wgUser->addGroup( "dummy" );
		$wgUser->removeGroup( "dummy" );
	}


	protected static $testItems = array();

	protected static function makeTestItemData() {
		$items = array();

		$item = ItemObject::newEmpty();
		$item->setLabel( 'de', 'Berlin' );
		$item->setDescription( 'de', 'Stadt in Deutschland' );
		$items['Berlin'][] = $item;

		$item = $item->copy();
		$item->setLabel( 'en', 'Berlin' );
		$item->setDescription( 'de', 'Stadt in Brandenburg' );
		$item->setDescription( 'en', 'City in Germany' );
		$items['Berlin'][] = $item;

		$item = $item->copy();
		$item->setDescription( 'de', 'Hauptstadt von Deutschland' );
		$items['Berlin'][] = $item;


		$item = ItemObject::newEmpty();
		$item->setLabel( 'en', 'London' );
		$items['London'][] = $item;

		$item = $item->copy();
		$item->setLabel( 'de', 'London' );
		$items['London'][] = $item;


		$item = ItemObject::newEmpty();
		$item->setLabel( 'de', 'Oslo' );
		$item->setLabel( 'en', 'Oslo' );
		$items['Oslo'][] = $item;

		return $items;
	}

	/**
	 * Creates an action and supplies it with a fake web request.
	 *
	 * @param String|\Action $action the action to call, may be an action name or class name.
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool       $post posted?
	 * @param array|null $session optional session data
	 *
	 * @return \Action
	 */
	protected function createAction( $action, WikiPage $page, array $params = null, $post = false, $session = null ) {
		global $wgUser,$wgLang;

		if ( $params == null ) {
			$params = array();
		}

		if ( $session == null ) {
			global $wgRequest;
			$session = $wgRequest->getSessionArray();
		}

		if ( !( $page instanceof \Article ) ) {
			$article = new \Article( $page->getTitle() );
		} else {
			$article = $page;
		}

		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, $post, $session ) );
		$context->setUser( $wgUser );     // determined by setUser()
		$context->setLanguage( $wgLang ); // qqx as per setUp()
		$context->setTitle( $article->getTitle() );

		$article->setContext( $context );

		if ( preg_match( '/^[a-z]+$/', $action ) ) {
			$action = Action::factory( $action, $article, $context );
		} else {
			$action = new $action( $article, $context );
		}

		return $action;
	}

	/**
	 * Calls the desired action using a fake web request.
	 * This calls the Show() method on the target action.
	 *
	 * @param String|\Action $action the action to call; may be an action name or class name
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool       $post posted?
	 * @param array|null $session optional session data
	 *
	 * @return OutputPage
	 * @throws \MWException
	 */
	protected function callAction( $action, WikiPage $page, array $params = null, $post = false, array $session = null ) {
		if ( is_string( $action ) ) {
			$action = $this->createAction( $action, $page, $params, $post, $session );

			if ( !$action ) {
				throw new \MWException( "unknown action: $action" );
			}
		}

		$action->show();

		return $action->getContext()->getOutput();
	}

	/**
	 * Returns a token
	 *
	 * @param \Title $title the page to return the token for
	 * @param String $for the action to return the token for, e.g. 'edit'.
	 *
	 * @return String the token
	 */
	protected function getToken( \Title $title, $for = 'edit' ) {
		$func = '\ApiQueryInfo::get' . ucfirst( $for ) . 'Token';

		$token = call_user_func( $func, $title->getArticleID(), $title );

		return $token;
	}

	/**
	 * Changes wgUser and resets any associated state
	 *
	 * @param \User $user the desired user
	 * @param array $session optional session data
	 */
	protected function setUser( User $user, array $session = null ) {
		global $wgUser;
		global $wgRequest;

		if ( $user->getName() != $wgUser->getName() ) {
			$wgUser = $user;
			\ApiQueryInfo::resetTokenCache();
		}

		if ( $session !== null ) {
			foreach ( $session as $k => $v ) {
				$wgRequest->setSessionData( $k, $v );
			}
		}
	}

	/**
	 * Creates the test items defined by makeTestItemData() in the database.
	 */
	public static function initTestItems() {
		if ( self::$testItems ) {
			return;
		}

		$itemData = self::makeTestItemData();

		/* @var Item $item */
		/* @var ItemContent $content */
		/* @var Status $status */

		foreach ( $itemData as $handle => $revisions ) {
			$item = self::createTestItem( $handle, $revisions );
			self::$testItems[$handle] = $item;
		}
	}

	/**
	 * Creates a test item defined by $revisions.
	 *
	 * @return Item
	 * @throws \MWException
	 */
	public static function createTestItem( $handle, $revisions ) {
		/* @var ItemContent $content */
		/* @var Item $item */

		$content = null;

		foreach ( $revisions as $item ) {
			if ( $content == null ) {
				$content = ItemContent::newFromItem( $item );
				$status = $content->save( "Creating test item '$handle'" );
			} else {
				$item->setId( $content->getItem()->getId() );
				$content = ItemContent::newFromItem( $item );
				$status = $content->save( "Changing test item '$handle'" );
			}

			if ( !$status->isOK() ) {
				throw new \MWException( "failed to generate test item" );
			}
		}

		$page = \WikiPage::factory( $content->getTitle() );
		$item = $content->getItem();

		$item->revid = $page->getLatest(); //XXX: hack - glue refid to item, so we can compare it later in resetTestItem()

		return $item;
	}

	/**
	 * Deletes and re-creates the given test item.
	 *
	 * @param String $handle
	 */
	public static function resetTestItem( $handle ) {
		/* @var Item $item */
		$item = self::$testItems[ $handle ];

		// check current data
		$page = static::getTestItemPage( $handle );
		if ( $page->getLatest() == $item->revid ) {
			return; // revid didn't change
		}

		// delete current data
		$page->doDeleteArticle( "Testing" );

		// re-create item
		$itemData = self::makeTestItemData();
		$revisions = $itemData[ $handle ];

		$item = self::createTestItem( $handle, $revisions );
		self::$testItems[ $handle ] = $item;
	}

	/**
	 * Deletes and re-creates the given test item.
	 *
	 * @param String $handle
	 * @return \Wikibase\Item
	 */
	public static function loadTestItem( $handle ) {
		$page = static::getTestItemPage( $handle );

		/* @var ItemContent $content */
		$content = $page->getContent();
		return $content->getItem();
	}

	/**
	 * Returns a well known test item for the given $handle, creating it in the database first if necessary.
	 *
	 * @param String $handle the test item's handle
	 *
	 * @return Item the item
	 * @throws \Exception if the handle is not known
	 */
	public static function getTestItem( $handle ) {
		self::initTestItems();

		if ( !isset( self::$testItems[$handle] ) ) {
			throw new \Exception( "unknown test item: $handle" );
		}

		return self::$testItems[$handle];
	}

	/**
	 * Returns a wiki page for a well known test item for the given $handle, creating it in the database first if necessary.
	 *
	 * @param String $handle the test item's handle
	 *
	 * @return \WikiPage the item's page
	 * @throws \Exception if the handle is not known
	 */
	public static function getTestItemPage( $handle ) {
		$item = self::getTestItem( $handle );
		$content = ItemContent::newFromItem( $item );
		$title = $content->getTitle();
		$page = \WikiPage::factory( $title );
		return $page;
	}
}