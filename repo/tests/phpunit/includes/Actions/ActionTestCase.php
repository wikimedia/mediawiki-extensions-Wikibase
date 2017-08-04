<?php

namespace Wikibase\Repo\Tests\Actions;

use Action;
use ApiQueryInfo;
use Article;
use Exception;
use FauxRequest;
use MWException;
use OutputPage;
use RequestContext;
use RuntimeException;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @license GPL-2.0+
 * @author Daniel Kinzler
 *
 * @todo: move this to core (except the test item stuff of course)
 */
class ActionTestCase extends \MediaWikiTestCase {

	private $permissionsChanged = false;

	protected function setUp() {
		parent::setUp();

		$testUser = new \TestUser( 'ActionTestUser' );
		$user = $testUser->getUser();
		$user->setId( 123456789 );

		$this->setMwGlobals( [
			'wgUser' => $user,
			'wgRequest' => new FauxRequest(),
			'wgGroupPermissions' => [ '*' => [ 'edit' => true, 'read' => true ] ]
		] );

		$this->setUserLang( 'qqx' );

		ApiQueryInfo::resetTokenCache();
	}

	protected function tearDown() {
		ApiQueryInfo::resetTokenCache();
		parent::tearDown();
	}

	protected function applyPermissions( $permissions ) {
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
		$wgUser->clearInstanceCache();
	}

	/**
	 * @var EntityDocument[]|EntityRedirect[] List of EntityDocument or EntityRedirect objects,
	 *      with logical handles as keys.
	 */
	private static $testItems = [];

	private function makeTestItemData() {
		$items = [];

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$item->setDescription( 'de', 'Stadt in Deutschland' );
		$items['Berlin'][] = $item;

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$item->setLabel( 'en', 'Berlin' );
		$item->setDescription( 'de', 'Stadt in Brandenburg' );
		$item->setDescription( 'en', 'City in Germany' );
		$items['Berlin'][] = $item;

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$item->setLabel( 'en', 'Berlin' );
		$item->setDescription( 'de', 'Hauptstadt von Deutschland' );
		$item->setDescription( 'en', 'City in Germany' );
		$items['Berlin'][] = $item;

		$item = new Item();
		$item->setLabel( 'en', 'London' );
		$items['London'][] = $item;

		$item = new Item();
		$item->setLabel( 'en', 'London' );
		$item->setLabel( 'de', 'London' );
		$items['London'][] = $item;

		$item = new Item();
		$item->setLabel( 'de', 'Oslo' );
		$item->setLabel( 'en', 'Oslo' );
		$items['Oslo'][] = $item;

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$items['Berlin2'][] = $item;

		// HACK: this revision is a redirect
		$items['Berlin2'][] = 'Berlin';

		return $items;
	}

	/**
	 * Creates an action and supplies it with a fake web request.
	 *
	 * @param string|Action $action The action to call, may be an action name or class name.
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool       $post posted?
	 * @param array|null $session optional session data
	 *
	 * @return Action
	 */
	protected function createAction( $action, WikiPage $page, array $params = null, $post = false, $session = null ) {
		global $wgLang,
			$wgRequest,
			$wgUser;

		if ( $params == null ) {
			$params = [];
		}

		if ( $session == null ) {
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

		// Must be set separately, similar to what MediaWiki::performRequest() does.
		// Currently used in ViewEntityActionTest.
		if ( !empty( $params['printable'] ) ) {
			$context->getOutput()->setPrintable();
		}

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
	 * @param string|Action $action The action to call; may be an action name or class name
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
	 * Changes wgUser and resets any associated state
	 *
	 * @param User $user the desired user
	 * @param array|null $session optional session data
	 */
	protected function setUser( User $user, array $session = null ) {
		global $wgRequest,
			$wgUser;

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
	private function initTestItems() {
		if ( self::$testItems ) {
			return;
		}

		$itemData = $this->makeTestItemData();

		foreach ( $itemData as $handle => $revisions ) {
			$item = $this->createTestContent( $handle, $revisions );
			self::$testItems[$handle] = $item;
		}
	}

	/**
	 * Creates a test item defined by $revisions.
	 *
	 * @todo Provide this for all kinds of entities.
	 *
	 * @param string $handle
	 * @param array $revisions List of EntityDocument or string. String values represent redirects.
	 *
	 * @return Item|EntityRedirect
	 * @throws MWException
	 * @throws RuntimeException
	 */
	private function createTestContent( $handle, array $revisions ) {
		global $wgUser;

		/** @var EntityRevision $rev */
		$id = null;
		$result = null;

		foreach ( $revisions as $entity ) {
			$flags = ( $id !== null ) ? EDIT_UPDATE : EDIT_NEW;
			$result = $this->createTestContentRevision( $entity, $id, $wgUser, $flags );

			if ( $result instanceof EntityRedirect ) {
				$id = $result->getEntityId();
			} else {
				$id = $result->getId();
			}
		}

		return $result;
	}

	/**
	 * @param EntityDocument|string $entity
	 * @param EntityId|null $id
	 * @param User $user
	 * @param int $flags
	 *
	 * @throws RuntimeException
	 * @return EntityDocument|EntityRedirect
	 */
	private function createTestContentRevision( $entity, $id, User $user, $flags ) {
		if ( $flags == EDIT_NEW ) {
			$comment = "Creating test item";
		} else {
			$comment = "Changing test item";
		}

		// HACK: If $entity is a string, treat it as a redirect target.
		// The redirect must not be the first revision.
		if ( is_string( $entity ) ) {
			if ( !$id ) {
				throw new RuntimeException( 'Can\'t create a redirect as the first revision of a test entity page.' );
			}

			$result = $this->createTestRedirect( $id, $entity, $comment, $user, $flags );
		} else {
			if ( $id ) {
				$entity->setId( $id );
			}

			$result = $this->createTestItem( $entity, $comment, $user, $flags );
		}

		return $result;
	}

	private function createTestItem( EntityDocument $entity, $comment, $user, $flags ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$rev = $store->saveEntity( $entity, $comment, $user, $flags );

		$result = $rev->getEntity();

		//XXX: hack - glue refid to item, so we can compare it later in resetTestItem()
		$result->revid = $rev->getRevisionId();
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
	private function createTestRedirect( EntityId $entityId, $targetHandle, $comment, User $user, $flags ) {
		$targetId = $this->getTestItemId( $targetHandle );
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
	 * @param string $handle
	 */
	protected function resetTestItem( $handle ) {
		if ( isset( self::$testItems[ $handle ] ) ) {
			$item = self::$testItems[ $handle ];

			// check current data
			$page = $this->getTestItemPage( $handle );
			if ( $page->getLatest() == $item->revid ) {
				return; // revid didn't change
			}

			// delete current data
			$page->doDeleteArticle( "Testing" );
		}

		// re-create item
		$itemData = $this->makeTestItemData();
		$revisions = $itemData[ $handle ];

		$item = $this->createTestContent( $handle, $revisions );
		self::$testItems[ $handle ] = $item;
	}

	/**
	 * Deletes and re-creates the given test item.
	 *
	 * @param string $handle
	 * @return Item
	 */
	protected function loadTestItem( $handle ) {
		$page = $this->getTestItemPage( $handle );
		/** @var ItemContent $content */
		$content = $page->getContent();

		return $content->getItem();
	}

	/**
	 * Returns the ID of a well known test item for the given $handle.
	 *
	 * @param string $handle The test item's handle
	 *
	 * @return EntityId the item's ID
	 * @throws Exception if the handle is not known
	 */
	private function getTestItemId( $handle ) {
		if ( !isset( self::$testItems[$handle] ) ) {
			throw new Exception( "Unknown test item $handle" );
		}

		$item = self::$testItems[$handle];

		if ( $item instanceof EntityRedirect ) {
			return $item->getEntityId();
		} else {
			return $item->getId();
		}
	}

	/**
	 * Returns a wiki page for a well known test item for the given $handle, creating it in the database first if necessary.
	 *
	 * @param string $handle The test item's handle
	 *
	 * @return WikiPage the item's page
	 * @throws Exception if the handle is not known
	 */
	protected function getTestItemPage( $handle ) {
		$this->initTestItems();

		$itemId = $this->getTestItemId( $handle );
		$title = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $itemId );

		$page = WikiPage::factory( $title );
		return $page;
	}

}
