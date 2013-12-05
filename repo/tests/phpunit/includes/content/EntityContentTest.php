<?php

namespace Wikibase\Test;

use IContextSource;
use Wikibase\Entity;
use MediaWikiTestCase;
use ParserOptions;
use RequestContext;
use Title;
use Wikibase\EntityContent;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;

/**
 * @covers Wikibase\EntityContent
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityContentTest extends MediaWikiTestCase {

	protected $permissions;
	protected $old_user;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->permissions = $wgGroupPermissions;
		$this->old_user = $wgUser;

		\TestSites::insertIntoDb();
	}

	function tearDown() {
		global $wgGroupPermissions;
		global $wgUser;

		$wgGroupPermissions = $this->permissions;

		if ( $this->old_user ) { // should not be null, but sometimes, it is
			$wgUser = $this->old_user;
		}

		if ( $wgUser ) { // should not be null, but sometimes, it is
			// reset rights cache
			$wgUser->addGroup( "dummy" );
			$wgUser->removeGroup( "dummy" );
		}

		parent::tearDown();
	}

	/**
	 * @return string
	 */
	protected abstract function getContentClass();

	/**
	 * @param array $data
	 *
	 * @return EntityContent
	 */
	protected function newFromArray( array $data ) {
		$class = $this->getContentClass();
		return $class::newFromArray( $data );
	}

	/**
	 * @return EntityContent
	 */
	protected function newEmpty() {
		$class = $this->getContentClass();
		return $class::newEmpty();
	}

	/**
	 * Tests @see Wikibase\Entity::getTextForSearchIndex
	 *
	 * @dataProvider getTextForSearchIndexProvider
	 *
	 * @param EntityContent $entityContent
	 * @param string $pattern
	 */
	public function testGetTextForSearchIndex( EntityContent $entityContent, $pattern ) {
		$text = $entityContent->getTextForSearchIndex();
		$this->assertRegExp( $pattern . 'm', $text );
	}

	public function getTextForSearchIndexProvider() {
		$entityContent = $this->newEmpty();
		$entityContent->getEntity()->setLabel( 'en', "cake" );

		return array(
			array( $entityContent, '/^cake$/' )
		);
	}

	public function testSaveFlags() {
		\Wikibase\StoreFactory::getStore()->getTermIndex()->clear();

		$entityContent = $this->newEmpty();
		$prefix = get_class( $this ) . '/';

		// try to create without flags
		$entityContent->getEntity()->setLabel( 'en', $prefix . 'one' );
		$status = $entityContent->save( 'create item' );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue(
			$status->hasMessage( 'edit-gone-missing' ),
			'try to create without flags, edit gone missing'
		);

		// try to create with EDIT_UPDATE flag
		$entityContent->getEntity()->setLabel( 'en', $prefix . 'two' );
		$status = $entityContent->save( 'create item', null, EDIT_UPDATE );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue(
			$status->hasMessage( 'edit-gone-missing' ),
			'edit gone missing, try to create with EDIT_UPDATE'
		);

		// try to create with EDIT_NEW flag
		$entityContent->getEntity()->setLabel( 'en', $prefix . 'three' );
		$status = $entityContent->save( 'create item', null, EDIT_NEW );
		$this->assertTrue(
			$status->isOK(),
			'create with EDIT_NEW flag for ' .
			$entityContent->getEntity()->getId()->getPrefixedId()
		);

		// ok, the item exists now in the database.

		// try to save with EDIT_NEW flag
		$entityContent->getEntity()->setLabel( 'en', $prefix . 'four' );
		$status = $entityContent->save( 'create item', null, EDIT_NEW );
		$this->assertFalse( $status->isOK(), "save should have failed" );
		$this->assertTrue(
			$status->hasMessage( 'edit-already-exists' ),
			'try to save with EDIT_NEW flag, edit already exists'
		);

		// try to save with EDIT_UPDATE flag
		$entityContent->getEntity()->setLabel( 'en', $prefix . 'five' );
		$status = $entityContent->save( 'create item', null, EDIT_UPDATE );
		$this->assertTrue(
			$status->isOK(),
			'try to save with EDIT_UPDATE flag, save failed'
		);

		// try to save without flags
		$entityContent->getEntity()->setLabel( 'en', $prefix . 'six' );
		$status = $entityContent->save( 'create item' );
		$this->assertTrue( $status->isOK(), 'try to save without flags, save failed' );
	}

	public function testRepeatedSave() {
		\Wikibase\StoreFactory::getStore()->getTermIndex()->clear();

		$entityContent = $this->newEmpty();
		$prefix = get_class( $this ) . '/';

		// create
		$entityContent->getEntity()->setLabel( 'en', $prefix . "First" );
		$status = $entityContent->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), 'create, save failed, status ok' );
		$this->assertTrue( $status->isGood(), 'create, status is good' );

		// change
		$prev_id = $entityContent->getWikiPage()->getLatest();
		$entityContent->getEntity()->setLabel( 'en', $prefix . "Second" );
		$status = $entityContent->save( 'modify item', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), 'change, status ok' );
		$this->assertTrue( $status->isGood(), 'change, status good' );
		$this->assertNotEquals( $prev_id, $entityContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// change again
		$prev_id = $entityContent->getWikiPage()->getLatest();
		$entityContent->getEntity()->setLabel( 'en', $prefix . "Third" );
		$status = $entityContent->save( 'modify item again', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), 'change again, status ok' );
		$this->assertTrue( $status->isGood(), 'change again, status good' );
		$this->assertNotEquals( $prev_id, $entityContent->getWikiPage()->getLatest(), "revision ID should change on edit" );

		// save unchanged
		$prev_id = $entityContent->getWikiPage()->getLatest();
		$status = $entityContent->save( 'save unmodified', null, EDIT_UPDATE );
		$this->assertTrue( $status->isOK(), 'save unchanged, save failed, status ok' );
		$this->assertEquals( $prev_id, $entityContent->getWikiPage()->getLatest(), "revision ID should stay the same if no change was made" );
	}

	public function dataCheckPermissions() {
		// FIXME: this is testing for some specific configuration and will break if the config is changed
		return array(
			array( //0: read allowed
				'read',
				'user',
				array( 'read' => true ),
				false,
				true,
			),
			array( //1: edit and createpage allowed for new item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( //2: edit allowed but createpage not allowed for new item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( //3: edit allowed but createpage not allowed for existing item
				'edit',
				'user',
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( //4: edit not allowed for existing item
				'edit',
				'user',
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
			array( //5: delete not allowed
				'delete',
				'user',
				array( 'read' => true, 'delete' => false ),
				false,
				false,
			),
		);
	}

	protected function prepareItemForPermissionCheck( $group, $permissions, $create ) {
		global $wgUser;

		// TODO: Figure out what is leaking the sysop group membership
		$wgUser->removeGroup('sysop');

		$content = $this->newEmpty();

		if ( $create ) {
			$label = get_class( $this ) . '/' . $group . '/' . implode( ', ', $permissions );
			$content->getEntity()->setLabel( 'de', $label );
			$content->save( "testing", null, EDIT_NEW );
		}

		if ( !in_array( $group, $wgUser->getEffectiveGroups() ) ) {
			$wgUser->addGroup( $group );
		}

		if ( $permissions !== null ) {
			PermissionsHelper::applyPermissions( array(
				'*' => $permissions,
				'user' => $permissions,
				$group => $permissions,
			) );
		}

		return $content;
	}

	/**
	 * @dataProvider dataCheckPermissions
	 */
	public function testCheckPermission( $action, $group, $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );

		$status = $content->checkPermission( $action );

		$this->assertEquals( $expectedOK, $status->isOK() );
	}

	/**
	 * @dataProvider dataCheckPermissions
	 */
	public function testUserCan( $action, $group, $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( $group, $permissions, $create );

		$content->checkPermission( $action );

		$this->assertEquals( $expectedOK, $content->userCan( $action ) );
	}


	public function dataUserCanEdit() {
		return array(
			array( //0: edit and createpage allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( //1: edit allowed but createpage not allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				false,
				false,
			),
			array( //2: edit allowed but createpage not allowed for existing item
				array( 'read' => true, 'edit' => true, 'createpage' => false ),
				true,
				true,
			),
			array( //3: edit not allowed for existing item
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
		);
	}

	/**
	 * @dataProvider dataUserCanEdit
	 */
	public function testUserCanEdit( $permissions, $create, $expectedOK ) {
		$content = $this->prepareItemForPermissionCheck( 'user', $permissions, $create );

		$this->assertEquals( $expectedOK, $content->userCanEdit() );
	}

	public function testGetParserOutput() {
		$content = $this->newEmpty();

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$this->assertInstanceOf( '\ParserOutput', $parserOutput );
		$this->assertEquals( EntityContent::STATUS_EMPTY, $parserOutput->getProperty( 'wb-status' ) );
	}

	public function dataPageProperties() {
		$cases = array();

		$cases['empty'] = array(
			array(),
			array( 'wb-status' => 'empty', 'wb-claims' => 0 )
		);

		$cases['labels'] = array(
			array( 'label' => array( 'en' => 'Foo' ) ),
			array( 'wb-status' => 'ok', 'wb-claims' => 0 )
		);

		$cases['claims'] = array(
			array( 'claims' => array( array( 'm' => array( 'value', 83, 'string', 'foo' ), 'q' => array(), 'g' => '$testing$' ) ) ),
			array( 'wb-status' => 'ok', 'wb-claims' => 1 )
		);

		return $cases;
	}

	/**
	 * @dataProvider dataPageProperties
	 *
	 * @param array $entityData
	 * @param array $expectedProps
	 */
	public function testPageProperties( array $entityData, array $expectedProps ) {
		$content = $this->newFromArray( $entityData );

		$title = \Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title, null, null, false );

		foreach ( $expectedProps as $name => $value ) {
			$this->assertEquals( $value, $parserOutput->getProperty( $name ), "page property $name");
		}
	}

	public function dataGetEntityStatus() {
		$label = array( 'language' => 'de', 'value' => 'xyz' );

		return array(
			'empty' => array( array(), EntityContent::STATUS_EMPTY ),
			'labels' => array(
				array( 'labels' => array( 'de' => $label ) ),
				EntityContent::STATUS_EMPTY
			),
		);
	}

	/**
	 * @dataProvider dataGetEntityStatus
	 */
	public function testGetEntityStatus( array $entityData, $status ) {
		$content = $this->newFromArray( $entityData );
		$actual = $content->getEntityStatus();

		$this->assertEquals( $status, $actual );
	}

	public function dataGetEntityPageProperties() {
		$claim = array( 'm' => array( 'novalue', 11 ), 'q' => array(), 'g' => 'P11x' );

		return array(
			'empty' => array(
				array(),
				array(
					'wb-status' => EntityContent::STATUS_EMPTY,
					'wb-claims' => 0,
				)
			),

			'claims' => array(
				array( 'claims' => array( 'P11a' => $claim ) ),
				array(
					'wb-status' => EntityContent::STATUS_OK,
					'wb-claims' => 1,
				)
			),
		);
	}

	/**
	 * @dataProvider dataGetEntityPageProperties
	 */
	public function testGetEntityPageProperties( array $entityData, $pageProps ) {
		$content = $this->newFromArray( $entityData );
		$actual = $content->getEntityPageProperties();

		foreach ( $pageProps as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual );
			$this->assertEquals( $value, $actual[$key], $key );
		}
	}

	public function dataGetEntityView() {
		$context = new RequestContext();
		$context->setLanguage( 'de' );

		$options = new ParserOptions();
		$options->setUserLang( 'nl' );

		$fallbackChain = new LanguageFallbackChain( array(
			LanguageWithConversion::factory( $context->getLanguage() )
		) );

		return array(
			array( $context, null, null ),
			array( null, $options, null ),
			array( $context, $options, null ),

			array( $context, null, $fallbackChain ),
			array( null, $options, $fallbackChain ),
			array( $context, $options, $fallbackChain ),
		);
	}

	/**
	 * @dataProvider dataGetEntityView
	 *
	 * @param IContextSource $context
	 * @param ParserOptions $parserOptions
	 * @param LanguageFallbackChain $fallbackChain
	 */
	public function testGetEntityView(
		IContextSource $context = null,
		ParserOptions $parserOptions = null,
		LanguageFallbackChain $fallbackChain = null
	) {
		$content = $this->newEmpty();
		$view = $content->getEntityView( $context, $parserOptions, $fallbackChain );

		$this->assertInstanceOf( 'Wikibase\EntityView', $view );

		if ( $parserOptions ) {
			// NOTE: the view must be using the language from the parser options.
			$this->assertEquals( $view->getLanguage()->getCode(), $parserOptions->getUserLang() );
		} elseif ( $content ) {
			$this->assertEquals( $view->getLanguage()->getCode(), $context->getLanguage()->getCode() );
		}
	}

}
