<?php

namespace Wikibase\Repo\Tests\Actions;

use MediaWiki\MediaWikiServices;
use MWException;
use Title;
use User;
use Wikibase\Repo\Actions\EditEntityAction;
use Wikibase\Repo\Actions\SubmitEntityAction;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @covers \Wikibase\Repo\Actions\EditEntityAction
 * @covers \Wikibase\Repo\Actions\SubmitEntityAction
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 *
 * @group Action
 * @group Wikibase
 * @group WikibaseAction
 *
 * @group Database
 * @group medium
 */
class EditEntityActionTest extends ActionTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Remove handlers for the "OutputPageParserOutput" hook
		$this->clearHook( 'OutputPageParserOutput' );
	}

	public function testActionForPage() {
		$page = $this->getTestItemPage( 'Berlin' );

		$action = $this->createAction( 'edit', $page );
		$this->assertInstanceOf( EditEntityAction::class, $action );

		$action = $this->createAction( 'submit', $page );
		$this->assertInstanceOf( SubmitEntityAction::class, $action );
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

		$rev = $page->getRevisionRecord();

		if ( !$rev ) {
			return;
		}

		$revLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		for ( $i = abs( $ofs ); $i > 0; $i -= 1 ) {
			$rev = $revLookup->getPreviousRevision( $rev );
			if ( !$rev ) {
				throw new MWException( 'Page ' . $page->getTitle()->getPrefixedDBkey()
					. ' does not have ' . ( abs( $ofs ) + 1 ) . ' revisions' );
			}
		}

		$params[ $key ] = $rev->getId();
	}

	public function provideUndoForm() {
		// based upon well known test items defined in ActionTestCase::makeTestItemData

		yield 'edit, no parameters' => [
			'edit', // action
			'Berlin', // handle
			[], // params
			false, // post
			null, // user
			'/id="[^"]*\bwb-item\b[^"]*"/', // htmlPattern: should show an item
		];

		yield 'submit, no parameters' => [
			'submit', // action
			'Berlin', // handle
			[], // params
			false, // post
			null, // user
			'/id="[^"]*\bwb-item\b[^"]*"/', // htmlPattern: should show an item
		];

		// -- show undo form -----------------------------------
		yield 'undo form with legal undo' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undo' => 0, // current revision
			],
			false, // post
			null, // user
			'/undo-success/', // htmlPattern: should be a success
			[ // expectedProps
				'moduleStyles' => [ 'wikibase.alltargets' ],
			],
		];

		yield 'undo form with legal undo and undoafter' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undo' => 0, // current revision
				'undoafter' => -1, // previous revision
			],
			false, // post
			null, // user
			'/undo-success/', // htmlPattern: should be a success
			[ // expectedProps
				'moduleStyles' => [ 'wikibase.alltargets' ],
			],
		];

		yield 'undo form with illegal undo == undoafter' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undo' => -1, // previous revision
				'undoafter' => -1, // previous revision
			],
			false, // post
			null, // user
			'/wikibase-undo-samerev/', // htmlPattern: should contain error
		];

		yield 'undo form with legal undoafter' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undoafter' => -1, // previous revision
			],
			false, // post
			null, // user
			'/undo-success/', // htmlPattern: should be a success
			[ // expectedProps
				'moduleStyles' => [ 'wikibase.alltargets' ],
			],
		];

		yield 'undo form with illegal undo' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undo' => -2, // first revision
			],
			false, // post
			null, // user
			'/wikibase-undo-firstrev/', // htmlPattern: should contain error
		];

		yield 'undo form with illegal undoafter' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undoafter' => 0, // current revision
			],
			false, // post
			null, // user
			'/wikibase-undo-samerev/', // htmlPattern: should contain error
		];

		// -- show restore form -----------------------------------
		yield 'restore form with legal restore' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'restore' => -1, // previous revision
			],
			false, // post
			null, // user
			'/class="diff/', // htmlPattern: should be a success and contain a diff (undo-success is not shown for restore)
			[ // expectedProps
				'moduleStyles' => [ 'wikibase.alltargets' ],
			],
		];

		yield 'restore form with illegal restore' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'restore' => 0, // current revision
			],
			false, // post
			null, // user
			'/wikibase-undo-samerev/', // htmlPattern: should contain error
		];

		// -- bad revision -----------------------------------
		yield 'undo bad revision' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undo' => 12345678, // bad revision
			],
			false, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		yield 'undoafter bad revision with good undo' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undo' => 0, // current revision
				'undoafter' => 12345678, // bad revision
			],
			false, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		yield 'undoafter bad revision' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'undoafter' => 12345678, // bad revision
			],
			false, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		yield 'restore bad revision' => [
			'edit', // action
			'Berlin', // handle
			[ // params
				'restore' => 12345678, // bad revision
			],
			false, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		// -- bad page -----------------------------------
		yield 'non-existing page' => [
			'edit', // action
			Title::newFromTextThrow( 'XXX', $this->getItemNamespace() ),
			[ // params
				'restore' => [ 'London', 0 ], // ok revision
			],
			false, // post
			null, // user
			'/missing-article/', // htmlPattern: should contain error
		];

		yield 'undo revision from different pages' => [
			'edit', // action class
			'Berlin', // handle
			[ // params
				'undo' => [ 'London', 0 ], // wrong page
			],
			false, // post
			null, // user
			'/wikibase-undo-badpage/', // htmlPattern: should contain error
		];

		yield 'undoafter revision from different pages' => [
			'edit', // action class
			'Berlin', // handle
			[ // params
				'undoafter' => [ 'London', -1 ], // wrong page
			],
			false, // post
			null, // user
			'/wikibase-undo-badpage/', // htmlPattern: should contain error
		];

		yield 'restore revision from different pages' => [
			'edit', // action class
			'Berlin', // handle
			[ // params
				'restore' => [ 'London', -1 ], // wrong page
			],
			false, // post
			null, // user
			'/wikibase-undo-badpage/', // htmlPattern: should contain error
		];

		// -- show undo form for redirect -----------------------------------
		yield 'undo form for redirect with legal undo where latest revision is redirect' => [
			'edit', // action
			'Berlin2', // handle
			[ // params
				'undo' => 0, // current revision
			],
			false, // post
			null, // user
			'/undo-success/', // htmlPattern: should be a success
			[ // expectedProps
				'moduleStyles' => [ 'wikibase.alltargets' ],
			],
		];

		yield 'undo form for redirect with legal undo and undoafter where latest revision is redirect' => [
			'edit', // action
			'Berlin2', // handle
			[ // params
				'undo' => 0, // current revision
				'undoafter' => -4, // earlier revision where label was "London"
			],
			false, // post
			null, // user
			'/undo-success.*London<\/ins>/s', // htmlPattern: should be a success and add London (/s = PCRE_DOTALL)
		];

		yield 'undo form for redirect with legal undo where latest revision is not redirect' => [
			'edit', // action
			'Berlin3', // handle
			[ // params
				'undo' => 0, // current revision
			],
			false, // post
			null, // user
			'/undo-success.*USA<\/del>/s', // htmlPattern: should be a success and remove German description (/s = PCRE_DOTALL)
			[ // expectedProps
				'moduleStyles' => [ 'wikibase.alltargets' ],
			],
		];

		yield 'undo form for redirect with legal undo and undoafter where latest revision is not redirect' => [
			'edit', // action
			'Berlin3', // handle
			[ // params
				'undo' => 0, // current revision
				'undoafter' => -4, // earlier revision identical to previous revision
			],
			false, // post
			null, // user
			'/undo-success.*USA<\/del>/s', // htmlPattern: should be a success and remove German description (/s = PCRE_DOTALL)
		];

		yield 'undo form for redirect with legal undo and undoafter '
			. 'where latest revision is not redirect and non-redirect data is discarded' => [
			'edit', // action
			'Berlin3', // handle
			[ // params
			  'undo' => -1, // previous revision
			  'undoafter' => -2, // redirect revision
			],
			false, // post
			null, // user
			'/undo-success.*USA<\/del>/s', // htmlPattern: should be a success and remove German description (/s = PCRE_DOTALL)
		];

		yield 'undo form for redirect with illegal undo where latest revision is redirect' => [
			'edit', // action
			'Berlin2', // handle
			[ // params
				'undo' => -1, // previous revision
			],
			false, // post
			null, // user
			'/wikibase-undo-redirect-latestredirect/', // htmlPattern: should contain error
		];

		yield 'undo form for redirect with illegal undo and undoafter where latest revision is redirect' => [
			'edit', // action
			'Berlin2', // handle
			[ // params
				'undo' => -1, // previous revision
				'undoafter' => -4, // earlier revision
			],
			false, // post
			null, // user
			'/wikibase-undo-redirect-latestredirect/', // htmlPattern: should contain error
		];

		yield 'undo form for redirect with illegal undo where latest revision is not redirect' => [
			'edit', // action
			'Berlin3', // handle
			[ // params
				'undo' => -2, // revision that edited redirect target
			],
			false, // post
			null, // user
			'/wikibase-undo-redirect-latestnoredirect/', // htmlPattern: should contain error
		];

		yield 'undo form for redirect with illegal undo and undoafter where latest revision is not redirect' => [
			'edit', // action
			'Berlin3', // handle
			[ // params
				'undo' => -2, // revision with redirect target
				'undoafter' => -4, // non-redirect revision
			],
			false, // post
			null, // user
			'/wikibase-undo-redirect-latestnoredirect/', // htmlPattern: should contain error
		];
	}

	/**
	 * @dataProvider provideUndoForm
	 */
	public function testUndoForm(
		$action,
		$page,
		array $params,
		$post = false,
		User $user = null,
		$htmlPattern = null,
		array $expectedProps = null
	) {
		$this->tryUndoAction( $action, $page, $params, $post, $user, $htmlPattern, $expectedProps );
	}

	public function provideUndoSubmit() {
		// based upon well known test items defined in ActionTestCase::makeTestItemData
		yield "submit with legal undo, but don't post" => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
			],
			false, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '/[&?]action=edit&undo=\d+/', // redirect to undo form
			],
		];

		yield 'submit with legal undo, but omit wpSave' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '/[&?]action=edit&undo=\d+/', // redirect to undo form
			],
		];

		// -- show undo form -----------------------------------
		yield 'undo form with legal undo' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'undo form with legal undo and undoafter' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
				'undoafter' => -1, // previous revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'undo form with illegal undo == undoafter' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -1, // previous revision
				'undoafter' => -1, // previous revision
			],
			true, // post
			null, // user
			'/wikibase-undo-samerev/', // htmlPattern: should contain error
		];

		yield 'undo form with legal undoafter' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undoafter' => -1, // previous revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'undo form with illegal undo' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -2, // first revision
			],
			true, // post
			null, // user
			'/wikibase-undo-firstrev/', // htmlPattern: should contain error
		];

		yield 'undo form with illegal undoafter' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undoafter' => 0, // current revision
			],
			true, // post
			null, // user
			'/wikibase-undo-samerev/', // htmlPattern: should contain error
		];

		// -- show restore form -----------------------------------
		yield 'restore form with legal restore' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'restore' => -1, // previous revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'restore form with illegal restore' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'restore' => 0, // current revision
			],
			true, // post
			null, // user
			'/wikibase-undo-samerev/', // htmlPattern: should contain error
		];

		// -- bad revision -----------------------------------
		yield 'undo bad revision' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 12345678, // bad revision
			],
			true, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		yield 'undoafter bad revision with good undo' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
				'undoafter' => 12345678, // bad revision
			],
			true, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		yield 'undoafter bad revision' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undoafter' => 12345678, // bad revision
			],
			true, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		yield 'restore bad revision' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'restore' => 12345678, // bad revision
			],
			true, // post
			null, // user
			'/undo-norev/', // htmlPattern: should contain error
		];

		// -- bad page -----------------------------------
		yield 'non-existing page' => [
			'submit', // action
			Title::newFromTextThrow( 'XXX', $this->getItemNamespace() ),
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'restore' => [ 'London', 0 ], // ok revision
			],
			true, // post
			null, // user
			'/missing-article/', // htmlPattern: should contain error
		];

		yield 'undo revision from different pages' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => [ 'London', 0 ], // wrong page
			],
			true, // post
			null, // user
			'/wikibase-undo-badpage/', // htmlPattern: should contain error
		];

		yield 'undoafter revision from different pages' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undoafter' => [ 'London', -1 ], // wrong page
			],
			true, // post
			null, // user
			'/wikibase-undo-badpage/', // htmlPattern: should contain error
		];

		yield 'restore revision from different pages' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'restore' => [ 'London', -1 ], // wrong page
			],
			true, // post
			null, // user
			'/wikibase-undo-badpage/', // htmlPattern: should contain error
		];

		// -- bad token -----------------------------------
		yield 'submit with legal undo, but wrong token' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => 'xyz', // bad token
				'undo' => 0, // current revision
			],
			true, // post
			null, // user
			'/session_fail_preview/', // htmlPattern: should contain error
		];

		// -- incomplete form -----------------------------------
		yield 'submit without undo/undoafter/restore' => [
			'submit', // action
			'Berlin', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
			],
			true, // post
			null, // user
			'/id="[^"]*\bwb-item\b[^"]*"/', // htmlPattern: should show item
		];

		// -- redirect -----------------------------------
		yield 'submit for redirect with legal undo where latest revision is redirect' => [
			'submit', // action
			'Berlin2', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'submit for redirect with legal undo and undoafter where latest revision is redirect' => [
			'submit', // action
			'Berlin2', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
				'undoafter' => -4, // earlier revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'submit for redirect with legal undo where latest revision is not redirect' => [
			'submit', // action
			'Berlin3', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'submit for redirect with legal undo and undoafter where latest revision is not redirect' => [
			'submit', // action
			'Berlin3', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => 0, // current revision
				'undoafter' => -4, // earlier revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'undo form for redirect with legal undo and undoafter '
			. 'where latest revision is not redirect and non-redirect data is discarded' => [
			'submit', // action
			'Berlin3', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -1, // previous revision
				'undoafter' => -2, // redirect revision
			],
			true, // post
			null, // user
			null, // htmlPattern
			[
				'redirect' => '![:/=]Q\d+$!', // expect success and redirect to page
			],
		];

		yield 'submit for redirect with illegal undo where latest revision is redirect' => [
			'submit', // action
			'Berlin2', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -1, // previous revision
			],
			true, // post
			null, // user
			'/wikibase-undo-redirect-latestredirect/', // htmlPattern: should contain error
		];

		yield 'submit for redirect with illegal undo and undoafter where latest revision is redirect' => [
			'submit', // action
			'Berlin2', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -1, // previous revision
				'undoafter' => -4, // earlier revision
			],
			true, // post
			null, // user
			'/wikibase-undo-redirect-latestredirect/', // htmlPattern: should contain error
		];

		yield 'submit for redirect with illegal undo where latest revision is not redirect' => [
			'submit', // action
			'Berlin3', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -2,
			],
			true, // post
			null, // user
			'/wikibase-undo-redirect-latestnoredirect/', // htmlPattern: should contain error
		];

		yield 'submit for redirect with illegal undo and undoafter where latest revision is not redirect' => [
			'submit', // action
			'Berlin3', // handle
			[ // params
				'wpSave' => 1,
				'wpEditToken' => true, // automatic token
				'undo' => -2,
				'undoafter' => -4,
			],
			true, // post
			null, // user
			'/wikibase-undo-redirect-latestnoredirect/', // htmlPattern: should contain error
		];
	}

	/**
	 * @dataProvider provideUndoSubmit
	 */
	public function testUndoSubmit(
		$action,
		$page,
		array $params,
		$post = false,
		User $user = null,
		$htmlPattern = null,
		array $expectedProps = null
	) {
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
	 * @param User|null $user
	 * @param string|bool|null $htmlPattern
	 * @param string[]|null $expectedProps
	 */
	protected function tryUndoAction(
		$action,
		$page,
		array $params,
		$post = false,
		User $user = null,
		$htmlPattern = null,
		array $expectedProps = null
	) {
		if ( $user ) {
			$this->setUser( $user );
		}

		if ( is_string( $page ) ) {
			$page = $this->getTestItemPage( $page );
		} elseif ( $page instanceof Title ) {
			$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $page );
		}

		$this->adjustRevisionParam( 'undo', $params, $page );
		$this->adjustRevisionParam( 'undoafter', $params, $page );
		$this->adjustRevisionParam( 'restore', $params, $page );

		if ( isset( $params['wpEditToken'] ) && $params['wpEditToken'] === true ) {
			$params['wpEditToken'] = $this->user->getEditToken(); //TODO: $user
		}

		$out = $this->callAction( $action, $page, $params, $post );

		if ( $htmlPattern !== null && $htmlPattern !== false ) {
			$this->assertMatchesRegularExpression( $htmlPattern, $out->getHTML() );
		}

		if ( $expectedProps ) {
			foreach ( $expectedProps as $p => $pattern ) {
				$func = 'get' . ucfirst( $p );
				$act = call_user_func( [ $out, $func ] );

				if ( $pattern === true ) {
					$this->assertNotSame( '', $act, $p );
				} elseif ( $pattern === false ) {
					$this->assertSame( '', $act, $p );
				} elseif ( is_array( $pattern ) ) { // expected subset of actual
					$this->assertIsArray( $act, $p );
					foreach ( $pattern as $element ) {
						$this->assertContains( $element, $act, $p );
					}
				} else {
					$this->assertMatchesRegularExpression( $pattern, $act, $p );
				}
			}
		}
	}

	public function provideUndoRevisions() {
		// based upon well known test items defined in ActionTestCase::makeTestItemData

		yield 'undo last revision' => [
			'Berlin', //handle
			[
				'undo' => 0, // last revision
			],
			[ //expected
				'descriptions' => [
					'de' => 'Stadt in Brandenburg',
					'en' => 'City in Germany',
				],
			],
		];

		yield 'undo previous revision' => [
			'Berlin', //handle
			[
				'undo' => -1, // previous revision
			],
			[ //expected
				'descriptions' => [
					'de' => 'Hauptstadt von Deutschland',
				],
			],
		];

		yield 'undo last and previous revision' => [
			'Berlin', //handle
			[
				'undo' => 0, // current revision
				'undoafter' => -2, // first revision
			],
			[ //expected
				'descriptions' => [
					'de' => 'Stadt in Deutschland',
				],
			],
		];

		yield 'undoafter first revision (conflict, no change)' => [
			'Berlin', //handle
			[
				'undoafter' => -2, // first revision
			],
			[ //expected
				'descriptions' => [
					'de' => 'Stadt in Deutschland',
				],
			],
		];

		yield 'restore previous revision' => [
			'Berlin', //handle
			[
				'restore' => -1, // previous revision
			],
			[ //expected
				'descriptions' => [
					'de' => 'Stadt in Brandenburg',
					'en' => 'City in Germany',
				],
			],
		];

		yield 'restore first revision' => [
			'Berlin', //handle
			[
				'restore' => -2, // first revision
			],
			[ //expected
				'descriptions' => [
					'de' => 'Stadt in Deutschland',
				],
			],
		];

		yield 'undo last revision and revert redirect' => [
			'Berlin2', //handle
			[
				'undo' => 0, // current revision
			],
			[ //expected
				'labels' => [],
			],
		];

		yield 'undo last two revisions and turn back into redirect' => [
			'Berlin3', //handle
			[
				'undo' => 0, // current revision
				'undoafter' => -2, // redirect revision
			],
			[ //expected
				'redirect' => 'Berlin',
			],
		];

		yield 'undo second-to-last revision and turn back into redirect' => [
			'Berlin3', //handle
			[
				'undo' => -1, // previous revision
			],
			[ //expected
				'redirect' => 'Berlin',
			],
		];
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
			$params['wpEditToken'] = $this->user->getEditToken();
		}

		if ( !isset( $params['wpSave'] ) ) {
			$params['wpSave'] = 1;
		}

		$out = $this->callAction( 'submit', $page, $params, true );

		$this->assertMatchesRegularExpression( '![:/=]Q\d+$!', $out->getRedirect(), 'successful operation should return a redirect' );

		if ( isset( $expected['redirect'] ) ) {
			$targetHandle = $this->loadTestRedirect( $handle );
			$this->assertSame( $expected['redirect'], $targetHandle );
			return;
		}

		$item = $this->loadTestItem( $handle );

		if ( isset( $expected['labels'] ) ) {
			$this->assertArrayEquals( $expected['labels'], $item->getLabels()->toTextArray(), false, true );
		}

		if ( isset( $expected['descriptions'] ) ) {
			$this->assertArrayEquals( $expected['descriptions'], $item->getDescriptions()->toTextArray(), false, true );
		}

		if ( isset( $expected['aliases'] ) ) {
			$this->assertArrayEquals( $expected['aliases'], $item->getAliasGroups()->toTextArray(), false, true );
		}

		if ( isset( $expected['sitelinks'] ) ) {
			$actual = [];

			foreach ( $item->getSiteLinkList()->toArray() as $siteLink ) {
				$actual[$siteLink->getSiteId()] = $siteLink->getPageName();
			}

			$this->assertArrayEquals( $expected['sitelinks'], $actual, false, true );
		}

		self::resetTestItem( $handle );
	}

	public function provideUndoPermissions() {
		return [
			[ //0
				'edit',
				[
					'*' => [ 'edit' => false ],
					'user' => [ 'edit' => false ],
				],
				'/permissions-errors/',
			],

			[ //1
				'submit',
				[
					'*' => [ 'edit' => false ],
					'user' => [ 'edit' => false ],
				],
				'/permissions-errors/',
			],
		];
	}

	/**
	 * @dataProvider provideUndoPermissions
	 */
	public function testUndoPermissions( $action, array $permissions, $error ) {
		$handle = 'London';

		self::resetTestItem( $handle );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', $permissions );

		$page = $this->getTestItemPage( $handle );

		$params = [
			'wpEditToken' => $this->user->getEditToken(),
			'wpSave' => 1,
			'undo' => $page->getLatest(),
		];

		$out = $this->callAction( $action, $page, $params, true );

		if ( $error ) {
			$this->assertMatchesRegularExpression( $error, $out->getHTML() );

			$this->assertSame( '', $out->getRedirect(), 'operation should not trigger a redirect' );
		} else {
			$this->assertMatchesRegularExpression( '![:/=]Q\d+$!', $out->getRedirect(), 'successful operation should return a redirect' );
		}

		self::resetTestItem( $handle );
	}

	private function getItemNamespace(): int {
		 $entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();
		 return $entityNamespaceLookup->getEntityNamespace( 'item' );
	}

	/**
	 * Changes $this->user and resets any associated state
	 *
	 * @param User $user the desired user
	 */
	private function setUser( User $user ) {
		if ( $user->getName() !== $this->user->getName() ) {
			$this->user = $user;
		}
	}
}
