<?php

namespace Wikibase\Test;

use MWException;
use Title;
use User;
use Wikibase\Repo\EntityNamespaceLookup;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @covers Wikibase\EditEntityAction
 * @covers Wikibase\SubmitEntityAction
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
 * @group WikibaseRepo
 *
 * @group Database
 * @group medium
 */
class EditEntityActionTest extends ActionTestCase {

	public function setup() {
		parent::setup();

		static $user = null;

		if ( !$user ) {
			$user = User::newFromId( 0 );
			$user->setName( '127.0.0.1' );
		}

		$this->setMwGlobals( 'wgUser', $user );

		// Remove handlers for the "OutputPageParserOutput" hook
		$this->mergeMwGlobalArrayValue( 'wgHooks', array( 'OutputPageParserOutput' => array() ) );
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( "Berlin" );

		$action = $this->createAction( "edit", $page );
		$this->assertInstanceOf( 'Wikibase\EditEntityAction', $action );

		$action = $this->createAction( "submit", $page );
		$this->assertInstanceOf( 'Wikibase\SubmitEntityAction', $action );
	}

	protected function adjustRevisionParam( $key, array &$params, WikiPage $page ) {
		if ( !isset( $params[$key] ) || ( is_int( $params[$key] ) && $params[$key] > 0 ) ) {
			return;
		}

		if ( is_array( $params[$key] ) ) {
			$page = $this->getTestItemPage( $params[$key][0] );
			$ofs = (int)$params[$key][1];

			$params[$key] = 0;
		} else {
			$ofs = (int)$params[$key];
		}

		$rev = $page->getRevision();

		if ( !$rev ) {
			return;
		}

		for ( $i = abs($ofs); $i > 0; $i -= 1 ) {
			$rev = $rev->getPrevious();
			if ( !$rev ) {
				throw new MWException( "Page " . $page->getTitle()->getPrefixedDBkey() . " does not have " . ( abs($ofs) +1 ) . " revisions" );
			}
		}

		$params[ $key ] = $rev->getId();
	}

	public function provideUndoForm() {
		// based upon well known test items defined in ActionTestCase::makeTestItemData

		$cases = array(
			array( //0: edit, no parameters
				'edit',   // action
				'Berlin', // handle
				array(),  // params
				false,    // post
				null,     // user
				'/id="[^"]*\bwb-item\b[^"]*"/',     // htmlPattern: should show an item
			),

			array( //1: submit, no parameters
				'submit', // action
				'Berlin', // handle
				array(),  // params
				false,    // post
				null,     // user
				'/id="[^"]*\bwb-item\b[^"]*"/',     // htmlPattern: should show an item
			),

			// -- show undo form -----------------------------------
			array( //2: // undo form with legal undo
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undo' => 0, // current revision
				),
				false,    // post
				null,     // user
				'/undo-success/', // htmlPattern: should be a success
			),

			array( //3: // undo form with legal undo and undoafter
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undo' => 0, // current revision
					'undoafter' => -1, // previous revision
				),
				false,    // post
				null,     // user
				'/undo-success/', // htmlPattern: should be a success
			),

			array( //4: // undo form with illegal undo == undoafter
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undo' => -1, // previous revision
					'undoafter' => -1, // previous revision
				),
				false,    // post
				null,     // user
				'/wikibase-undo-samerev/', // htmlPattern: should contain error
			),

			array( //5: // undo form with legal undoafter
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undoafter' => -1, // previous revision
				),
				false,    // post
				null,     // user
				'/undo-success/', // htmlPattern: should be a success
			),

			array( //6: // undo form with illegal undo
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undo' => -2, // first revision
				),
				false,    // post
				null,     // user
				'/wikibase-undo-firstrev/', // htmlPattern: should contain error
			),

			array( //7: // undo form with illegal undoafter
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undoafter' => 0, // current revision
				),
				false,    // post
				null,     // user
				'/wikibase-undo-samerev/', // htmlPattern: should contain error
			),

			// -- show restore form -----------------------------------
			array( //8: // restore form with legal restore
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'restore' => -1, // previous revision
				),
				false,    // post
				null,     // user
				'/class="diff/', // htmlPattern: should be a success and contain a diff (undo-success is not shown for restore)
			),

			array( //9: // restore form with illegal restore
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'restore' => 0, // current revision
				),
				false,    // post
				null,     // user
				'/wikibase-undo-samerev/', // htmlPattern: should contain error
			),

			// -- bad revision -----------------------------------
			array( //10: // undo bad revision
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undo' => 12345678, // bad revision
				),
				false,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			array( //11: // undoafter bad revision with good undo
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undo' => 0, // current revision
					'undoafter' => 12345678, // bad revision
				),
				false,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			array( //12: // undoafter bad revision
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'undoafter' => 12345678, // bad revision
				),
				false,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			array( //13: // restore bad revision
				'edit',   // action
				'Berlin', // handle
				array(    // params
					'restore' => 12345678, // bad revision
				),
				false,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			// -- bad page -----------------------------------
			array( //14: // non-existing page
				'edit',   // action
				Title::newFromText( "XXX", $this->getItemNamespace() ),
				array(    // params
					'restore' => array( "London", 0 ), // ok revision
				),
				false,    // post
				null,     // user
				'/missing-article/', // htmlPattern: should contain error
			),

			array( //15: // undo revision from different pages
				'edit',   // action class
				"Berlin", // handle
				array(    // params
					'undo' => array( "London", 0 ), // wrong page
				),
				false,    // post
				null,     // user
				'/wikibase-undo-badpage/', // htmlPattern: should contain error
			),

			array( //16: // undoafter revision from different pages
				'edit',   // action class
				"Berlin", // handle
				array(    // params
					'undoafter' => array( "London", -1 ), // wrong page
				),
				false,    // post
				null,     // user
				'/wikibase-undo-badpage/', // htmlPattern: should contain error
			),

			array( //17: // restore revision from different pages
				'edit',   // action class
				"Berlin", // handle
				array(    // params
					'restore' => array( "London", -1 ), // wrong page
				),
				false,    // post
				null,     // user
				'/wikibase-undo-badpage/', // htmlPattern: should contain error
			),

		);

		if ( self::shouldTestRedirects() ) {
			// -- show undo form for redirect -----------------------------------
			$cases[] = array( //18: // undo form with legal undo
				'edit',   // action
				'Berlin2', // handle
				array(    // params
					'undo' => 0, // current revision
				),
				false,    // post
				null,     // user
				'/undo-success/', // htmlPattern: should be a success
			);
		}

		return $cases;
	}

	/**
	 * @dataProvider provideUndoForm
	 */
	public function testUndoForm( $action, $page, array $params, $post = false, User $user = null, $htmlPattern = null, $expectedProps = null ) {
		$this->tryUndoAction( $action, $page, $params, $post, $user, $htmlPattern, $expectedProps );
	}

	public function provideUndoSubmit() {
		// based upon well known test items defined in ActionTestCase::makeTestItemData
		return array(
			array( //0: submit with legal undo, but don't post
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => 0,     // current revision
				),
				false,    // post
				null,     // user
				null,     // htmlPattern
				array(
					'redirect' => '/[&?]action=edit&undo=\d+/', // redirect to undo form
				)
			),

			array( //1: submit with legal undo, but omit wpSave
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpEditToken' => true, // automatic token
					'undo' => 0,     // current revision
				),
				true,    // post
				null,     // user
				null,     // htmlPattern
				array(
					'redirect' => '/[&?]action=edit&undo=\d+/', // redirect to undo form
				)
			),

			// -- show undo form -----------------------------------
			array( //2: // undo form with legal undo
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => 0,     // current revision
				),
				true,    // post
				null,    // user
				null,    // htmlPattern
				array(
					'redirect' => '![:/=]Q\d+$!' // expect success and redirect to page
				),
			),

			array( //3: // undo form with legal undo and undoafter
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => 0, // current revision
					'undoafter' => -1, // previous revision
				),
				true,    // post
				null,    // user
				null,    // htmlPattern
				array(
					'redirect' => '![:/=]Q\d+$!' // expect success and redirect to page
				),
			),

			array( //4: // undo form with illegal undo == undoafter
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => -1, // previous revision
					'undoafter' => -1, // previous revision
				),
				true,    // post
				null,     // user
				'/wikibase-undo-samerev/', // htmlPattern: should contain error
			),

			array( //5: // undo form with legal undoafter
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undoafter' => -1, // previous revision
				),
				true,    // post
				null,     // user
				null,    // htmlPattern
				array(
					'redirect' => '![:/=]Q\d+$!' // expect success and redirect to page
				),
			),

			array( //6: // undo form with illegal undo
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => -2, // first revision
				),
				true,    // post
				null,     // user
				'/wikibase-undo-firstrev/', // htmlPattern: should contain error
			),

			array( //7: // undo form with illegal undoafter
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undoafter' => 0, // current revision
				),
				true,    // post
				null,     // user
				'/wikibase-undo-samerev/', // htmlPattern: should contain error
			),

			// -- show restore form -----------------------------------
			array( //8: // restore form with legal restore
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'restore' => -1, // previous revision
				),
				true,    // post
				null,     // user
				null,    // htmlPattern
				array(
					'redirect' => '![:/=]Q\d+$!' // expect success and redirect to page
				),
			),

			array( //9: // restore form with illegal restore
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'restore' => 0, // current revision
				),
				true,    // post
				null,     // user
				'/wikibase-undo-samerev/', // htmlPattern: should contain error
			),

			// -- bad revision -----------------------------------
			array( //10: // undo bad revision
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => 12345678, // bad revision
				),
				true,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			array( //11: // undoafter bad revision with good undo
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => 0, // current revision
					'undoafter' => 12345678, // bad revision
				),
				true,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			array( //12: // undoafter bad revision
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undoafter' => 12345678, // bad revision
				),
				true,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			array( //13: // restore bad revision
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'restore' => 12345678, // bad revision
				),
				true,    // post
				null,     // user
				'/undo-norev/', // htmlPattern: should contain error
			),

			// -- bad page -----------------------------------
			array( //14: // non-existing page
				'submit', // action
				Title::newFromText( "XXX", $this->getItemNamespace() ),
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'restore' => array( "London", 0 ), // ok revision
				),
				true,    // post
				null,     // user
				'/missing-article/', // htmlPattern: should contain error
			),

			array( //15: // undo revision from different pages
				'submit', // action
				"Berlin", // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undo' => array( "London", 0 ), // wrong page
				),
				true,    // post
				null,     // user
				'/wikibase-undo-badpage/', // htmlPattern: should contain error
			),

			array( //16: // undoafter revision from different pages
				'submit', // action
				"Berlin", // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'undoafter' => array( "London", -1 ), // wrong page
				),
				true,    // post
				null,     // user
				'/wikibase-undo-badpage/', // htmlPattern: should contain error
			),

			array( //17: // restore revision from different pages
				'submit', // action
				"Berlin", // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // automatic token
					'restore' => array( "London", -1 ), // wrong page
				),
				true,    // post
				null,     // user
				'/wikibase-undo-badpage/', // htmlPattern: should contain error
			),

			// -- bad token -----------------------------------
			array( //18: submit with legal undo, but wrong token
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => 'xyz', // bad token
					'undo' => 0,     // current revision
				),
				true,    // post
				null,     // user
				'/token_suffix_mismatch/',     // htmlPattern: should contain error
			),

			// -- incomplete form -----------------------------------
			array( //19: submit without undo/undoafter/restore
				'submit', // action
				'Berlin', // handle
				array(    // params
					'wpSave' => 1,
					'wpEditToken' => true, // bad token
				),
				true,    // post
				null,     // user
				'/id="[^"]*\bwb-item\b[^"]*"/',     // htmlPattern: should show item
			),

		);
	}

	/**
	 * @dataProvider provideUndoSubmit
	 */
	public function testUndoSubmit( $action, $page, array $params, $post = false, User $user = null, $htmlPattern = null, $expectedProps = null ) {
		if ( is_string( $page ) ) {
			self::resetTestItem( $page );
		}

		$this->tryUndoAction( $action, $page, $params, $post, $user, $htmlPattern, $expectedProps );

		if ( is_string( $page ) ) {
			self::resetTestItem( $page );
		}
	}

	/**
	 * @param string $action
	 * @param WikiPage|Title|string $page
	 * @param array $params
	 * @param bool $post
	 * @param User $user
	 * @param null $htmlPattern
	 * @param null $expectedProps
	 */
	protected function tryUndoAction( $action, $page, array $params, $post = false, User $user = null, $htmlPattern = null, $expectedProps = null ) {
		if ( $user ) {
			$this->setUser( $user );
		}

		if ( is_string( $page ) ) {
			$page = $this->getTestItemPage( $page );
		} else if ( $page instanceof Title ) {
			$page = WikiPage::factory( $page );
		}

		$this->adjustRevisionParam( 'undo', $params, $page );
		$this->adjustRevisionParam( 'undoafter', $params, $page );
		$this->adjustRevisionParam( 'restore', $params, $page );

		if ( isset( $params['wpEditToken'] ) && $params['wpEditToken'] === true ) {
			$params['wpEditToken'] = $this->getToken( $page->getTitle(), 'edit' ); //TODO: $user
		}

		$out = $this->callAction( $action, $page, $params, $post );

		if ( $htmlPattern !== null && $htmlPattern !== false ) {
			$this->assertRegExp( $htmlPattern, $out->getHTML() );
		}

		if ( $expectedProps ) {
			foreach ( $expectedProps as $p => $pattern ) {
				$func = 'get' . ucfirst( $p );
				$act = call_user_func( array( $out, $func ) );

				if ( $pattern === true ) {
					$this->assertNotEmpty( $act, $p );
				} else if ( $pattern === false ) {
					$this->assertEmpty( $act, $p );
				} else {
					$this->assertRegExp( $pattern, $act, $p );
				}
			}
		}
	}

	public static function provideUndoRevisions() {

		// based upon well known test items defined in ActionTestCase::makeTestItemData

		return array(
			array( //0: undo last revision
				'Berlin', //handle
				array(
					'undo' => 0,  // last revision
				),
				array( //expected
					'descriptions' => array(
						'de' => 'Stadt in Brandenburg',
						'en' => 'City in Germany',
					),
				),
			),

			array( //1: undo previous revision
				'Berlin', //handle
				array(
					'undo' => -1,  // previous revision
				),
				array( //expected
					'descriptions' => array(
						'de' => 'Hauptstadt von Deutschland',
					),
				)
			),

			array( //2: undo last and previous revision
				'Berlin', //handle
				array(
					'undo' => 0,  // current revision
					'undoafter' => -2,  // first revision
				),
				array( //expected
					'descriptions' => array(
						'de' => 'Stadt in Deutschland',
					),
				)
			),

			array( //3: undoafter first revision (conflict, no change)
				'Berlin', //handle
				array(
					'undoafter' => -2,  // first revision
				),
				array( //expected
					'descriptions' => array(
						'de' => 'Stadt in Deutschland',
					),
				)
			),

			array( //4: restore previous revision
				'Berlin', //handle
				array(
					'restore' => -1,  // previous revision
				),
				array( //expected
					'descriptions' => array(
						'de' => 'Stadt in Brandenburg',
						'en' => 'City in Germany',
					),
				)
			),

			array( //5: restore first revision
				'Berlin', //handle
				array(
					'restore' => -2,  // first revision
				),
				array( //expected
					'descriptions' => array(
						'de' => 'Stadt in Deutschland',
					),
				)
			),
		);
	}

	/**
	 * @dataProvider provideUndoRevisions
	 */
	public function testUndoRevisions( $handle, array $params, array $expected ) {
		self::resetTestItem( $handle );

		$page = $this->getTestItemPage( $handle );

		$this->adjustRevisionParam( 'undo', $params, $page );
		$this->adjustRevisionParam( 'undoafter', $params, $page );
		$this->adjustRevisionParam( 'restore', $params, $page );

		if ( !isset( $params['wpEditToken'] ) ) {
			$params['wpEditToken'] = $this->getToken( $page->getTitle(), 'edit' );
		}

		if ( !isset( $params['wpSave'] ) ) {
			$params['wpSave'] = 1;
		}

		$out = $this->callAction( 'submit', $page, $params, true );

		$this->assertRegExp( '![:/=]Q\d+$!', $out->getRedirect(), "successful operation should return a redirect" );

		$item = $this->loadTestItem( $handle );

		if ( isset( $expected['labels'] ) ) {
			$this->assertArrayEquals( $expected['labels'], $item->getLabels(), false, true );
		}

		if ( isset( $expected['descriptions'] ) ) {
			$this->assertArrayEquals( $expected['descriptions'], $item->getDescriptions(), false, true );
		}

		if ( isset( $expected['aliases'] ) ) {
			$this->assertArrayEquals( $expected['aliases'], $item->getAllAliases(), false, true );
		}

		if ( isset( $expected['sitelinks'] ) ) {
			$actual = array();

			foreach ( $item->getSiteLinks() as $siteLink ) {
				$actual[$siteLink->getSiteId()] = $siteLink->getPageName();
			}

			$this->assertArrayEquals( $expected['sitelinks'], $actual, false, true );
		}

		self::resetTestItem( $handle );
	}

	public static function provideUndoPermissions() {
		return array(
			array( //0
				'edit',
				array(
					'*' => array( 'edit' => false ),
					'user' => array( 'edit' => false ),
				),
				'/permissions-errors/'
			),

			array( //1
				'submit',
				array(
					'*' => array( 'edit' => false ),
					'user' => array( 'edit' => false ),
				),
				'/permissions-errors/'
			),
		);
	}

	/**
	 * @dataProvider provideUndoPermissions
	 */
	public function testUndoPermissions( $action, $permissions, $error ) {
		$handle = "London";

		self::resetTestItem( $handle );

		$this->applyPermissions( $permissions );

		$page = $this->getTestItemPage( $handle );

		$params = array(
			'wpEditToken' => $this->getToken( $page->getTitle(), 'edit' ),
			'wpSave' => 1,
			'undo' => $page->getLatest(),
		);

		$out = $this->callAction( $action, $page, $params, true );

		if ( $error ) {
			$this->assertRegExp( $error, $out->getHTML() );

			$this->assertEmpty( $out->getRedirect(), "operation should not trigger a redirect" );
		} else {
			$this->assertRegExp( '![:/=]Q\d+$!', $out->getRedirect(), "successful operation should return a redirect" );
		}

		self::resetTestItem( $handle );
	}

	private function getItemNamespace() {
		 $entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		 return $entityNamespaceLookup->getEntityNamespace( CONTENT_MODEL_WIKIBASE_ITEM );
	}
}
