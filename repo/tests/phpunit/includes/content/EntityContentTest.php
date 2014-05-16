<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use IContextSource;
use ParserOptions;
use RequestContext;
use Title;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\DataModel\Entity\EntityDiff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\EntityContent;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Store\EntityStore;
use WikiPage;
use WikitextContent;

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
abstract class EntityContentTest extends \MediaWikiTestCase {

	protected $permissions;
	protected $old_user;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->permissions = $wgGroupPermissions;
		$this->old_user = $wgUser;

		$this->entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();
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
	 * @param EntityId $target
	 *
	 * @return EntityContent
	 */
	protected function newRedirect( EntityId $target ) {
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$title = $titleLookup->getTitleForId( $target );

		$class = $this->getContentClass();
		return $class::newRedirect( $title );
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

	/**
	 * Prepares entity data from test cases for use in a new EntityContent.
	 * This allows subclasses to inject required fields into the entity data array.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function prepareEntityData( array $data ) {
		return $data;
	}

	public function testGetParserOutput() {
		$content = $this->newEmpty();

		//@todo: Use a fake ID, no need to hit the database once we
		//       got rid of the rest of the storage logic.
		$this->entityStore->assignFreshId( $content->getEntity() );

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$this->assertInstanceOf( '\ParserOutput', $parserOutput );
		$this->assertEquals( EntityContent::STATUS_EMPTY, $parserOutput->getProperty( 'wb-status' ) );
	}

	public function providePageProperties() {
		$cases = array();

		$cases['empty'] = array(
			array(),
			array( 'wb-status' => EntityContent::STATUS_EMPTY, 'wb-claims' => 0 )
		);

		$cases['labels'] = array(
			array( 'label' => array( 'en' => 'Foo' ) ),
			array( 'wb-status' => EntityContent::STATUS_STUB, 'wb-claims' => 0 )
		);

		$cases['claims'] = array(
			array( 'claims' => array( array( 'm' => array( 'value', 83, 'string', 'foo' ), 'q' => array(), 'g' => '$testing$' ) ) ),
			array( 'wb-claims' => 1 )
		);

		return $cases;
	}

	/**
	 * @dataProvider providePageProperties
	 *
	 * @param array $entityData
	 * @param array $expectedProps
	 */
	public function testPageProperties( array $entityData, array $expectedProps ) {
		$content = $this->newFromArray( $this->prepareEntityData( $entityData ) );

		$title = \Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title, null, null, false );

		foreach ( $expectedProps as $name => $expected ) {
			$actual = $parserOutput->getProperty( $name );
			$this->assertEquals( $expected, $actual, "page property $name");
		}
	}

	public function provideGetEntityStatus() {
		$label = array( 'language' => 'de', 'value' => 'xyz' );
		$claim = array( 'm' => array( 'novalue', 83, ), 'q' => array(), 'g' => '$testing$' );

		return array(
			'empty' => array(
				array(),
				EntityContent::STATUS_EMPTY
			),
			'labels' => array(
				array( 'label' => array( 'de' => $label ) ),
				EntityContent::STATUS_STUB
			),
			'claims' => array(
				array( 'claims' => array( $claim ) ),
				EntityContent::STATUS_NONE
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityStatus
	 */
	public function testGetEntityStatus( array $entityData, $status ) {
		$content = $this->newFromArray( $this->prepareEntityData( $entityData ) );
		$actual = $content->getEntityStatus();

		$this->assertEquals( $status, $actual );
	}

	public function provideGetEntityId() {
		return array(
			'no id' => array( $this->newEmpty(), null ),
		);
	}

	/**
	 * @dataProvider provideGetEntityId
	 */
	public function testGetEntityId( EntityContent $content, $expected ) {
		$actual = $content->getEntityId();

		$this->assertEquals( $expected, $actual );
	}

	public function provideGetEntityPageProperties() {
		$label = array( 'language' => 'de', 'value' => 'xyz' );
		$claim = array( 'm' => array( 'novalue', 11 ), 'q' => array(), 'g' => 'P11x' );

		return array(
			'empty' => array(
				array(),
				array(
					'wb-status' => EntityContent::STATUS_EMPTY,
					'wb-claims' => 0,
				)
			),

			'labels' => array(
				array( 'label' => array( 'de' => $label ) ),
				array(
					'wb-status' => EntityContent::STATUS_STUB,
					'wb-claims' => 0,
				)
			),

			'claims' => array(
				array( 'claims' => array( 'P11a' => $claim ) ),
				array(
					'wb-claims' => 1,
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityPageProperties
	 */
	public function testGetEntityPageProperties( array $entityData, $pageProps ) {
		$content = $this->newFromArray( $this->prepareEntityData( $entityData ) );
		$actual = $content->getEntityPageProperties();

		foreach ( $pageProps as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual );
			$this->assertEquals( $value, $actual[$key], $key );
		}

		$this->assertArrayEquals( array_keys( $pageProps ), array_keys( $actual ) );
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

	public function diffProvider() {
		$empty = $this->newEmpty();

		$spam = $this->newEmpty();
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$ham = $this->newEmpty();
		$ham->getEntity()->setLabel( 'en', 'Ham' );

		$spamToHam = new DiffOpChange( 'Spam', 'Ham' );
		$spamToHamDiff = new EntityDiff( array(
			'label' => new Diff( array( 'en' => $spamToHam ) ),
		) );

		return array(
			'empty' => array( $empty, $empty, new EntityContentDiff( new EntityDiff(), new Diff() ) ),
			'same' => array( $ham, $ham, new EntityContentDiff(  new EntityDiff(), new Diff()  ) ),
			'spam to ham' => array( $spam, $ham, new EntityContentDiff( $spamToHamDiff, new Diff() ) ),
		);
	}

	/**
	 * @dataProvider diffProvider
	 *
	 * @param EntityContent $a
	 * @param EntityContent $b
	 * @param EntityContentDiff $expected
	 */
	public function testGetDiff( EntityContent $a, EntityContent $b, EntityContentDiff $expected ) {
		$actual = $a->getDiff( $b );

		$expectedOps = $expected->getOperations();
		$actualOps = $actual->getOperations();

		// HACK: ItemDiff always sets this, even if it's empty. Ignore.
		if ( isset( $actualOps['claim'] ) && $actualOps['claim']->isEmpty() ) {
			unset( $actualOps['claim'] );
		}

		$this->assertArrayEquals( $expectedOps, $actualOps, true );
	}

	public function patchedCopyProvider() {
		$spam = $this->newEmpty();
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$ham = $this->newEmpty();
		$ham->getEntity()->setLabel( 'en', 'Ham' );

		$spamToHam = new DiffOpChange( 'Spam', 'Ham' );
		$spamToHamDiff = new EntityDiff( array(
			'label' => new Diff( array( 'en' => $spamToHam ) ),
		) );

		return array(
			'empty' => array( $spam, new EntityContentDiff( new EntityDiff(), new Diff() ), $spam ),
			'spam to ham' => array( $spam, new EntityContentDiff( $spamToHamDiff, new Diff() ), $ham ),
		);
	}

	/**
	 * @dataProvider patchedCopyProvider
	 *
	 * @param EntityContent $base
	 * @param EntityContentDiff $patch
	 * @param EntityContent $expected
	 */
	public function testGetPatchedCopy( EntityContent $base, EntityContentDiff $patch, EntityContent $expected = null ) {
		if ( $expected === null ) {
			$this->setExpectedException( 'Diff\Patcher\PatcherException' );
		}

		$actual = $base->getPatchedCopy( $patch );

		if ( $expected !== null ) {
			$this->assertTrue( $expected->equals( $actual ), 'equals()' );
		}
	}

	public function copyProvider() {
		$empty = $this->newEmpty();
		$labels = $this->newEmpty();

		$labels->getEntity()->setLabel( 'en', 'Foo' );

		return array(
			'empty' => array( $empty ),
			'labels' => array( $labels ),
		);
	}

	/**
	 * @dataProvider copyProvider
	 * @param EntityContent $content
	 */
	public function testCopy( EntityContent $content ) {
		$copy = $content->copy();
		$this->assertNotSame( $content, $copy, 'Copy must not be the same instance.' );
		$this->assertTrue( $content->equals( $copy ), 'Copy must be equal to the original.' );
		$this->assertSame( get_class( $content ), get_class( $copy ), 'Copy must have the same type.' );
		$this->assertEquals( $content->getNativeData(), $copy->getNativeData(), 'Copy must have the same data.' );
	}


	public function equalsProvider() {
		$empty = $this->newEmpty();

		$labels1 = $this->newEmpty();
		$labels1->getEntity()->setLabel( 'en', 'Foo' );

		$labels2 = $this->newEmpty();
		$labels2->getEntity()->setLabel( 'de', 'Foo' );

		return array(
			'empty' => array( $empty, $empty, true ),
			'same labels' => array( $labels1, $labels1, true ),
			'different labels' => array( $labels1, $labels2, false ),
		);
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( EntityContent $a, EntityContent $b, $equals ) {

		$actual = $a->equals( $b );
		$this->assertEquals( $equals, $actual );

		$actual = $b->equals( $a );
		$this->assertEquals( $equals, $actual );
	}

	protected function getExistingPageTitle( EntityContent $content ) {
		// NOTE: needs database access
		$this->entityStore->assignFreshId( $content->getEntity() );
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$title = $titleLookup->getTitleForId( $content->getEntity()->getId() );

		if ( !$title->exists() ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			$store->saveEntity( $content->getEntity(), 'test', $GLOBALS['wgUser'] );
		}

		return $title;
	}

	public function testGetSecondaryDataUpdates() {
		$empty = $this->newEmpty();
		$title = $this->getExistingPageTitle( $empty );

		// NOTE: $title->exists() must be true.
		$updates = $empty->getSecondaryDataUpdates( $title );

		$this->assertDataUpdates( $updates );
	}

	public function testGetDeletionUpdates() {
		$empty = $this->newEmpty();
		$title = $this->getExistingPageTitle( $empty );

		$updates = $empty->getDeletionUpdates( new WikiPage( $title ) );

		$this->assertDataUpdates( $updates );
	}

	protected function assertDataUpdates( $updates ) {

		$this->assertInternalType( 'array', $updates );

		foreach ( $updates as $update ) {
			$this->assertInstanceOf( 'DataUpdate', $update );
		}
	}
}
