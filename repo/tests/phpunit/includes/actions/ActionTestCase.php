<?php

namespace Wikibase\Test;

use Action;
use ApiQueryInfo;
use Article;
use ContentHandler;
use Exception;
use FauxRequest;
use Language;
use MWException;
use OutputPage;
use RequestContext;
use RuntimeException;
use Title;
use User;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityRevision;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @todo: move this to core (except the test item stuff of course)
 */
class ActionTestCase extends \MediaWikiTestCase {

	protected $permissionsChanged = false;

	/**
	 * The language to set as the user language.
	 * 'qqx' is used per default to allow matching against message keys in the output.
	 *
	 * @var string
	 */
	protected $languageCode = 'qqx';

	public function setUp() {
		parent::setUp();

		$testUser = new \TestUser( 'ActionTestUser' );
		$user = $testUser->getUser();
		$user->setId( 123456789 );

		$this->setMwGlobals( array(
			'wgUser' => $user,
			'wgLang' => Language::factory( $this->languageCode ),
			'wgRequest' => new FauxRequest(),
			'wgGroupPermissions' => $GLOBALS['wgGroupPermissions'], // todo: use standard permissions
		) );

		ApiQueryInfo::resetTokenCache();
	}

	public function tearDown() {
		ApiQueryInfo::resetTokenCache();
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

		// reset rights cache
		$wgUser->addGroup( "dummy" );
		$wgUser->removeGroup( "dummy" );
	}

	protected static function shouldTestRedirects() {
		$handler = ContentHandler::getForModelID( CONTENT_MODEL_WIKIBASE_ITEM );
		return $handler->supportsRedirects();
	}

	/**
	 * @var array List of Entity or EntityRedirect objects, with logical handles as keys.
	 */
	private static $testItems = array();

	protected static function makeTestItemData() {
		$items = array();

		$item = Item::newEmpty();
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


		$item = Item::newEmpty();
		$item->setLabel( 'en', 'London' );
		$items['London'][] = $item;

		$item = $item->copy();
		$item->setLabel( 'de', 'London' );
		$items['London'][] = $item;


		$item = Item::newEmpty();
		$item->setLabel( 'de', 'Oslo' );
		$item->setLabel( 'en', 'Oslo' );
		$items['Oslo'][] = $item;

		if ( self::shouldTestRedirects() ) {
			$item = Item::newEmpty();
			$item->setLabel( 'de', 'Berlin' );
			$items['Berlin2'][] = $item;

			// HACK: this revision is a redirect
			$items['Berlin2'][] = 'Berlin';
		}

		return $items;
	}

	/**
	 * Creates an action and supplies it with a fake web request.
	 *
	 * @param String|Action $action the action to call, may be an action name or class name.
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool       $post posted?
	 * @param array|null $session optional session data
	 *
	 * @return Action
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

		if ( !( $page instanceof Article ) ) {
			$article = new Article( $page->getTitle() );
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
	 * This calls the show() method on the target action.
	 *
	 * @param String|Action $action the action to call; may be an action name or class name
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool       $post posted?
	 * @param array|null $session optional session data
	 *
	 * @return OutputPage
	 * @throws MWException
	 */
	protected function callAction( $action, WikiPage $page, array $params = null, $post = false, array $session = null ) {
		if ( is_string( $action ) ) {
			$action = $this->createAction( $action, $page, $params, $post, $session );

			if ( !$action ) {
				throw new MWException( "unknown action: $action" );
			}
		}

		$action->show();

		return $action->getContext()->getOutput();
	}

	/**
	 * Returns a token
	 *
	 * @param Title $title the page to return the token for
	 * @param String $for the action to return the token for, e.g. 'edit'.
	 *
	 * @return String the token
	 */
	protected function getToken( Title $title, $for = 'edit' ) {
		global $wgUser;

		switch ( $for ) {
			case 'edit':
				return $wgUser->getEditToken();

			default:
				throw new MWException( "unknown token action: $for" );
		}
	}

	/**
	 * Changes wgUser and resets any associated state
	 *
	 * @param User $user the desired user
	 * @param array $session optional session data
	 */
	protected function setUser( User $user, array $session = null ) {
		global $wgUser;
		global $wgRequest;

		if ( $user->getName() != $wgUser->getName() ) {
			$wgUser = $user;
			ApiQueryInfo::resetTokenCache();
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

		foreach ( $itemData as $handle => $revisions ) {
			$item = self::createTestContent( $handle, $revisions );
			self::$testItems[$handle] = $item;
		}
	}

	/**
	 * Creates a test item defined by $revisions.
	 *
	 * @param string $handle
	 * @param array $revisions List of Entity or string. String values represent redirects.
	 *
	 * @return Item|EntityRedirect
	 * @throws MWException
	 * @throws RuntimeException
	 */
	private static function createTestContent( $handle, array $revisions ) { //@todo: provide this for all kinds of entities.
		global $wgUser;

		/** @var EntityRevision $rev */
		$id = null;
		$result = null;

		/** @var Entity|string $item */
		foreach ( $revisions as $item ) {
			$flags = ( $id !== null ) ? EDIT_UPDATE : EDIT_NEW;
			$result = self::createTestContentRevision( $item, $id, $wgUser, $flags );

			if ( $result instanceof EntityRedirect ) {
				$id = $result->getEntityId();
			} else {
				$id = $result->getId();
			}
		}

		return $result;
	}

	/**
	 * @param Entity|string $item
	 * @param EntityId|null $id
	 * @param User $user
	 * @param int $flags
	 *
	 * @throws RuntimeException
	 *
	 * @return Entity|EntityRedirect
	 */
	private static function createTestContentRevision( $item, $id, User $user, $flags ) {
		if ( $flags == EDIT_NEW ) {
			$comment = "Creating test item";
		} else {
			$comment = "Changing test item";
		}

		// HACK: If $item is a string, treat it as a redirect target.
		// The redirect must not be the first revision.
		if ( is_string( $item ) ) {
			if ( !$id ) {
				throw new RuntimeException( 'Can\'t create a redirect as the first revision of a test entity page.' );
			}

			$result = self::createTestRedirect( $id, $item, $comment, $user, $flags );
		} else {
			if ( $id ) {
				$item->setId( $id );
			}

			$result = self::createTestItem( $item, $comment, $user, $flags );
		}

		return $result;
	}

	private static function createTestItem( Item $item, $comment, $user, $flags ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$rev = $store->saveEntity( $item, $comment, $user, $flags );

		$result = $rev->getEntity();

		//XXX: hack - glue refid to item, so we can compare it later in resetTestItem()
		$result->revid = $rev->getRevision();
		return $result;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $targetHandle
	 * @param string $comment
	 * @param User $user
	 * @param int $flags
	 *
	 * @return EntityRedirect
	 * @throws RuntimeException
	 */
	private static function createTestRedirect( EntityId $entityId, $targetHandle, $comment, $user, $flags ) {
		if ( !isset( self::$testItems[ $targetHandle ] ) ) {
			throw new RuntimeException( 'Unknown redirect target handle ' . $targetHandle );
		}

		$target = self::$testItems[ $targetHandle ];

		if ( $target instanceof EntityRedirect ) {
			$targetId = $target->getEntityId();
		} else {
			$targetId = $target->getId();
		}

		$redirect = new EntityRedirect( $entityId, $targetId );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$revId = $store->saveRedirect( $redirect, $comment, $user, $flags );

		$result = $redirect;

		//XXX: hack - glue refid to item, so we can compare it later in resetTestItem()
		$result->revid = $revId;
		return $result;
	}

	/**
	 * Deletes and re-creates the given test item.
	 *
	 * @param String $handle
	 */
	public static function resetTestItem( $handle ) {
		if ( isset( self::$testItems[ $handle ] ) ) {
			$item = self::$testItems[ $handle ];

			// check current data
			$page = static::getTestItemPage( $handle );
			if ( $page->getLatest() == $item->revid ) {
				return; // revid didn't change
			}

			// delete current data
			$page->doDeleteArticle( "Testing" );
		}

		// re-create item
		$itemData = self::makeTestItemData();
		$revisions = $itemData[ $handle ];

		$item = self::createTestContent( $handle, $revisions );
		self::$testItems[ $handle ] = $item;
	}

	/**
	 * Deletes and re-creates the given test item.
	 *
	 * @param String $handle
	 * @return Item
	 */
	public static function loadTestItem( $handle ) {
		$page = static::getTestItemPage( $handle );
		/** @var ItemContent $content */
		$content = $page->getContent();

		return $content->getItem();
	}

	/**
	 * Returns a well known test item for the given $handle, creating it in the database first if necessary.
	 *
	 * @param String $handle the test item's handle
	 *
	 * @return Item the item
	 * @throws Exception if the handle is not known
	 */
	public static function getTestItem( $handle ) {
		self::initTestItems();

		if ( !isset( self::$testItems[$handle] ) ) {
			throw new Exception( "unknown test item: $handle" );
		}

		if ( !( self::$testItems[$handle] instanceof Item ) ) {
			throw new Exception( "not an item: $handle" );
		}

		return self::$testItems[$handle];
	}

	/**
	 * Returns the ID of a well known test item for the given $handle,
	 * creating it in the database first if necessary.
	 *
	 * @param String $handle the test item's handle
	 *
	 * @return EntityId the item's ID
	 * @throws Exception if the handle is not known
	 */
	public static function getTestItemId( $handle ) {
		self::initTestItems();

		if ( !isset( self::$testItems[$handle] ) ) {
			throw new Exception( "unknown test item: $handle" );
		}

		if ( self::$testItems[$handle] instanceof EntityRedirect ) {
			$redirect = self::$testItems[$handle];
			return $redirect->getEntityId();
		} else {
			$entity = self::$testItems[$handle];
			return $entity->getId();
		}
	}

	/**
	 * Returns a wiki page for a well known test item for the given $handle, creating it in the database first if necessary.
	 *
	 * @param String $handle the test item's handle
	 *
	 * @return WikiPage the item's page
	 * @throws Exception if the handle is not known
	 */
	public static function getTestItemPage( $handle ) {
		$itemId = self::getTestItemId( $handle );
		$title = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $itemId );

		$page = WikiPage::factory( $title );
		return $page;
	}

}
