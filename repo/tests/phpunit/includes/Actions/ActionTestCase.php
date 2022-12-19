<?php

namespace Wikibase\Repo\Tests\Actions;

use Action;
use Article;
use Exception;
use FauxRequest;
use MediaWikiIntegrationTestCase;
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
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ActionTestCase extends MediaWikiIntegrationTestCase {

	/** @var User */
	protected $user;

	protected function setUp(): void {
		parent::setUp();

		$testUser = new \TestUser( 'ActionTestUser' );
		$user = $testUser->getUser();
		$user->setId( 123456789 );
		$this->user = $user;

		$this->setRequest( new FauxRequest() );
		$this->setMwGlobals( [
			'wgGroupPermissions' => [ '*' => [ 'edit' => true, 'read' => true, 'createpage' => true ] ],
		] );

		$this->setUserLang( 'qqx' );
	}

	/**
	 * @var EntityDocument[]|EntityRedirect[] List of EntityDocument or EntityRedirect objects,
	 *      with logical handles as keys.
	 */
	private static $testItems = [];

	/**
	 * @var bool[] List of items that have redirects in their revision history,
	 * with logical handles as keys.
	 */
	private const TEST_ITEMS_WITH_REDIRECTS = [
		'Berlin2' => true,
		'Berlin3' => true,
	];

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
		$item->setLabel( 'de', 'London' );
		$items['Berlin2'][] = $item;

		$items['Berlin2'][] = 'London'; // redirect to London

		$item = new Item();
		$item->setLabel( 'de', 'London' );
		$items['Berlin2'][] = $item; // revert redirect

		$item = new Item();
		$items['Berlin2'][] = $item;

		$items['Berlin2'][] = 'Berlin'; // redirect to Berlin

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$items['Berlin3'][] = $item;

		$items['Berlin3'][] = 'London'; // redirect to London

		$items['Berlin3'][] = 'Berlin'; // redirect to Berlin

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$items['Berlin3'][] = $item;

		$item = new Item();
		$item->setLabel( 'de', 'Berlin' );
		$item->setDescription( 'de', 'Stadt in Maryland, USA' );
		$items['Berlin3'][] = $item;

		return $items;
	}

	/**
	 * Creates an action and supplies it with a fake web request.
	 *
	 * @param string $actionName The name of the action to call
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool $wasPosted
	 *
	 * @return Action
	 */
	protected function createAction( string $actionName, WikiPage $page, array $params = null, $wasPosted = false ) {
		global $wgLang;

		if ( $params == null ) {
			$params = [];
		}

		$context = new RequestContext();
		$context->setRequest( new FauxRequest( $params, $wasPosted ) );
		$context->setUser( $this->user );     // determined by setUser()
		$context->setLanguage( $wgLang ); // qqx as per setUp()
		$context->setWikiPage( $page );
		$article = Article::newFromWikiPage( $page, $context );

		// Must be set separately, similar to what MediaWiki::performRequest() does.
		// Currently used in ViewEntityActionTest.
		if ( !empty( $params['printable'] ) ) {
			$context->getOutput()->setPrintable();
		}

		return Action::factory( $actionName, $article, $context );
	}

	/**
	 * Calls the desired action using a fake web request.
	 * This calls the show() method on the target action.
	 *
	 * @param string|Action $action The action to call; may be an action name or existing instance
	 * @param WikiPage  $page the wiki page to call the action on
	 * @param array|null $params request parameters
	 * @param bool $wasPosted
	 *
	 * @return OutputPage
	 * @throws MWException
	 */
	protected function callAction( $action, WikiPage $page, array $params, $wasPosted ) {
		if ( is_string( $action ) ) {
			$action = $this->createAction( $action, $page, $params, $wasPosted );

			if ( !$action ) {
				throw new MWException( "unknown action: $action" );
			}
		}

		$action->show();

		return $action->getContext()->getOutput();
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
			$item = $this->createTestContent( $revisions );
			self::$testItems[$handle] = $item;
		}
	}

	/**
	 * Creates a test item defined by $revisions.
	 *
	 * @todo Provide this for all kinds of entities.
	 *
	 * @param array $revisions List of EntityDocument or string. String values represent redirects.
	 *
	 * @return Item|EntityRedirect
	 * @throws MWException
	 * @throws RuntimeException
	 */
	private function createTestContent( array $revisions ) {
		/** @var EntityRevision $rev */
		$id = null;
		$result = null;

		foreach ( $revisions as $entity ) {
			$flags = ( $id !== null ) ? EDIT_UPDATE : EDIT_NEW;
			$result = $this->createTestContentRevision( $entity, $id, $this->user, $flags );

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

		// If $entity is a string, treat it as a redirect target.
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
		$store = WikibaseRepo::getEntityStore();
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

		$store = WikibaseRepo::getEntityStore();
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
			if (
				$page->getLatest() == $item->revid
				&& !isset( self::TEST_ITEMS_WITH_REDIRECTS[$handle] )
			) {
				return; // current data is fresh
			}

			// delete current data (either the data itself is stale,
			// or a redirect target may have been reset in the meantime)
			$page->doDeleteArticleReal( "Testing", $this->getTestSysop()->getUser() );
		}

		// re-create item
		$itemData = $this->makeTestItemData();
		$revisions = $itemData[ $handle ];

		$item = $this->createTestContent( $revisions );
		self::$testItems[ $handle ] = $item;
	}

	protected function loadTestItem( string $handle ): Item {
		$page = $this->getTestItemPage( $handle );
		/** @var ItemContent $content */
		$content = $page->getContent();

		return $content->getItem();
	}

	protected function loadTestRedirect( string $handle ): string {
		$page = $this->getTestItemPage( $handle );
		/** @var ItemContent $content */
		$content = $page->getContent();
		$target = $content->getEntityRedirect()->getTargetId();

		foreach ( self::$testItems as $otherHandle => $testItem ) {
			if ( $testItem instanceof Item && $testItem->getId()->equals( $target ) ) {
				return $otherHandle;
			}
		}

		throw new RuntimeException( "No handle found for target $target of redirect $handle" );
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
		$title = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $itemId );

		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		return $page;
	}

}
