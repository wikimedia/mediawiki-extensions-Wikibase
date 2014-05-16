<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use IContextSource;
use MediaWikiTestCase;
use ParserOptions;
use RequestContext;
use Title;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityDiff;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\EntityContent;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\store\EntityStore;

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
		$argLists = array();

		$emptyDiff = new EntityDiff();

		$content0 = $this->newEmpty();
		$entity0 = $content0->getEntity();

		$content1 = $this->newEmpty();
		$entity1 = $content1->getEntity();

		$expected = new EntityDiff();

		$argLists[] = array( $content0, $content1, $expected );

		$content0 = $this->newEmpty();
		$entity0 = $content0->getEntity();
		$entity0->addAliases( 'nl', array( 'bah' ) );
		$entity0->addAliases( 'de', array( 'bah' ) );

		$content1 = $this->newEmpty();
		$entity1 = $content1->getEntity();
		$entity1->addAliases( 'en', array( 'foo', 'bar' ) );
		$entity1->addAliases( 'nl', array( 'bah', 'baz' ) );

		$entity1->setDescription( 'en', 'onoez' );

		$expected = new EntityDiff( array(
			'aliases' => new Diff( array(
					'en' => new Diff( array(
							new DiffOpAdd( 'foo' ),
							new DiffOpAdd( 'bar' ),
						), false ),
					'de' => new Diff( array(
							new DiffOpRemove( 'bah' ),
						), false ),
					'nl' => new Diff( array(
							new DiffOpAdd( 'baz' ),
						), false )
				) ),
			'description' => new Diff( array(
					'en' => new DiffOpAdd( 'onoez' ),
				) ),
		) );

		$argLists[] = array( $content0, $content1, $expected );

		$content0 = $content1->copy();
		$content1 = $content1->copy();
		$expected = clone $emptyDiff;

		$argLists[] = array( $content0, $content1, $expected );

		$content0 = $this->newEmpty();
		$entity0 = $content0->getEntity();

		$content1 = $this->newEmpty();
		$entity1 = $content1->getEntity();
		$entity1->setLabel( 'en', 'onoez' );

		$expected = new EntityDiff( array(
			'label' => new Diff( array(
					'en' => new DiffOpAdd( 'onoez' ),
				) ),
		) );

		$argLists[] = array( $content0, $content1, $expected );

		//TODO: test redirects once we support diffs for them!
		return $argLists;
	}

	/**
	 * @dataProvider diffProvider
	 *
	 * @param EntityContent $content0
	 * @param EntityContent $content1
	 * @param Diff $expected
	 */
	public function testDiffEntities( EntityContent $content0, EntityContent $content1, Diff $expected ) {
		$actual = $content0->getDiff( $content1 );

		$this->assertInstanceOf( '\Wikibase\EntityDiff', $actual );
		$this->assertEquals( count( $expected ), count( $actual ) );

		// TODO: equality check
		// (simple serialize does not work, since the order is not relevant, and not only on the top level)
	}

	public function patchProvider() {
		$claim0 = new Claim( new PropertyNoValueSnak( 42 ) );
		$claim1 = new Claim( new PropertySomeValueSnak( 42 ) );
		$claim2 = new Claim( new PropertyValueSnak( 42, new StringValue( 'ohi' ) ) );
		$claim3 = new Claim( new PropertyNoValueSnak( 1 ) );

		$claim0->setGuid( 'claim0' );
		$claim1->setGuid( 'claim1' );
		$claim2->setGuid( 'claim2' );
		$claim3->setGuid( 'claim3' );

		$argLists = array();


		$source = $this->newEmpty();
		$patch = new EntityDiff();
		$expected = $source->copy();

		$argLists[] = array( $source, $patch, $expected );


		$source = $this->newEmpty();
		$sourceEntity = $source->getEntity();
		$sourceEntity->setLabel( 'en', 'foo' );
		$sourceEntity->setLabel( 'nl', 'bar' );
		$sourceEntity->setDescription( 'de', 'foobar' );
		$sourceEntity->setAliases( 'en', array( 'baz', 'bah' ) );
		$sourceEntity->addClaim( $claim1 );

		$patch = new EntityDiff();
		$expected = clone $source;

		$argLists[] = array( $source, $patch, $expected );


		$source = clone $source;

		$patch = new EntityDiff( array(
			'description' => new Diff( array(
					'de' => new DiffOpChange( 'foobar', 'onoez' ),
					'en' => new DiffOpAdd( 'foobar' ),
				), true ),
		) );

		$expected = $source->copy();
		$expectedEntity = $expected->getEntity();
		$expectedEntity->setDescription( 'de', 'onoez' );
		$expectedEntity->setDescription( 'en', 'foobar' );

		$argLists[] = array( $source, $patch, $expected );


		$source = $this->newEmpty();
		$sourceEntity = $source->getEntity();
		$sourceEntity->addClaim( $claim0 );
		$sourceEntity->addClaim( $claim1 );
		$patch = new EntityDiff( array( 'claim' => new Diff( array(
				'claim0' => new DiffOpRemove( $claim0 ),
				'claim2' => new DiffOpAdd( $claim2 ),
				'claim3' => new DiffOpAdd( $claim3 )
			), false ) ) );

		$expected = $this->newEmpty();
		$expectedEntity = $expected->getEntity();
		$expectedEntity->addClaim( $claim1 );
		$expectedEntity->addClaim( $claim2 );
		$expectedEntity->addClaim( $claim3 );

		$argLists[] = array( $source, $patch, $expected );

		//TODO: test redirects once we support diffs for them!
		return $argLists;
	}

	/**
	 * @dataProvider patchProvider
	 *
	 * @param EntityContent $source
	 * @param Diff $patch
	 * @param EntityContent $expected
	 */
	public function testGetPatched( EntityContent $source, Diff $patch, EntityContent $expected ) {
		$patched = $source->getPatched( $patch );
		$this->assertTrue( $expected->equals( $patched ) );
	}

	//TODO: test getUndoContent

}
