<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use IContextSource;
use ParserOptions;
use PHPUnit_Framework_Assert;
use RequestContext;
use Title;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityDiff;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;
use Wikibase\EntityContent;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

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

	private $originalGroupPermissions;
	private $originalUser;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->originalGroupPermissions = $wgGroupPermissions;
		$this->originalUser = $wgUser;

		$this->entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();
	}

	function tearDown() {
		global $wgGroupPermissions;
		global $wgUser;

		$wgGroupPermissions = $this->originalGroupPermissions;

		if ( $this->originalUser ) { // should not be null, but sometimes, it is
			$wgUser = $this->originalUser;
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
	 * @return EntityId
	 */
	protected abstract function getDummyId();

	/**
	 * @param EntityId $id
	 *
	 * @return EntityContent
	 */
	protected function newEmpty( EntityId $id = null ) {
		$class = $this->getContentClass();
		$empty = $class::newEmpty();

		if ( $id ) {
			$empty->getEntity()->setId( $id );
		}

		return $empty;
	}

	/**
	 * @param EntityId $id
	 * @param EntityId $target
	 *
	 * @return EntityContent
	 */
	protected function newRedirect( EntityId $id, EntityId $target ) {
		//FIXME: use the respective EntityHandler instead of going via the global title lookup!
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$title = $titleLookup->getTitleForId( $target );

		$class = $this->getContentClass();
		return $class::newFromRedirect( new EntityRedirect( $id, $target ), $title );
	}

	/**
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

	public function testWikibaseTextForSearchIndex() {
		global $wgHooks;

		$entityContent = $this->newEmpty();
		$entityContent->getEntity()->setLabel( 'en', "cake" );

		$this->stashMwGlobals( 'wgHooks' );
		$wgHooks['WikibaseTextForSearchIndex'][] =
			function ( $actualEntityContent, &$text ) use ( $entityContent ) {
				PHPUnit_Framework_Assert::assertSame( $entityContent, $actualEntityContent );
				PHPUnit_Framework_Assert::assertRegExp( '/cake/m', $text );

				$text .= "\nHOOK";
				return true;
			};

		$text = $entityContent->getTextForSearchIndex();
		$this->assertRegExp( '/cake.*HOOK/s', $text, 'Text for search index should be updated by the hook' );
	}

	public function testWikibaseTextForSearchIndex_abort() {
		global $wgHooks;

		$entityContent = $this->newEmpty();
		$entityContent->getEntity()->setLabel( 'en', "cake" );

		$this->stashMwGlobals( 'wgHooks' );
		$wgHooks['WikibaseTextForSearchIndex'][] = function () { return false; };

		$text = $entityContent->getTextForSearchIndex();
		$this->assertEquals( '', $text, 'Text for search index should be empty if the hook returned false' );
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
			$this->newEmpty(),
			array( 'wb-status' => EntityContent::STATUS_EMPTY, 'wb-claims' => 0 )
		);

		$contentWithLabel = $this->newEmpty();
		$contentWithLabel->getEntity()->setLabel( 'en', 'Foo' );

		$cases['labels'] = array(
			$contentWithLabel,
			array( 'wb-status' => EntityContent::STATUS_STUB, 'wb-claims' => 0 )
		);

		return $cases;
	}

	/**
	 * @dataProvider providePageProperties
	 */
	public function _testPageProperties( EntityContent $content, array $expectedProps ) {
		$title = \Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title, null, null, false );

		foreach ( $expectedProps as $name => $expected ) {
			$actual = $parserOutput->getProperty( $name );
			$this->assertEquals( $expected, $actual, "page property $name");
		}
	}

	public function provideGetEntityStatus() {
		$contentWithLabel = $this->newEmpty();
		$contentWithLabel->getEntity()->setLabel( 'de', 'xyz' );

		return array(
			'empty' => array(
				$this->newEmpty(),
				EntityContent::STATUS_EMPTY
			),
			'labels' => array(
				$contentWithLabel,
				EntityContent::STATUS_STUB
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityStatus
	 */
	public function testGetEntityStatus( EntityContent $content, $status ) {
		$actual = $content->getEntityStatus();

		$this->assertEquals( $status, $actual );
	}

	public abstract function provideGetEntityId();

	/**
	 * @dataProvider provideGetEntityId
	 */
	public function testGetEntityId( EntityContent $content, $expected ) {
		$actual = $content->getEntityId();

		$this->assertEquals( $expected, $actual );
	}

	public function provideGetEntityPageProperties() {
		$empty = $this->newEmpty();

		$labeledEntityContent = $this->newEmpty();
		$fingerprint = $labeledEntityContent->getEntity()->getFingerprint();
		$fingerprint->getLabels()->setTerm( new Term( 'de', 'xyz' ) );
		$labeledEntityContent->getEntity()->setFingerprint( $fingerprint );

		return array(
			'empty' => array(
				$empty,
				array(
					'wb-status' => EntityContent::STATUS_EMPTY,
					'wb-claims' => 0,
				)
			),

			'labels' => array(
				$labeledEntityContent,
				array(
					'wb-status' => EntityContent::STATUS_STUB,
					'wb-claims' => 0,
				)
			),
		);
	}

	/**
	 * @dataProvider provideGetEntityPageProperties
	 */
	public function _testGetEntityPageProperties( EntityContent $content, $pageProps ) {
		$actual = $content->getEntityPageProperties();

		foreach ( $pageProps as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual );
			$this->assertEquals( $value, $actual[$key], $key );
		}

		$this->assertArrayEquals( array_keys( $pageProps ), array_keys( $actual ) );
	}

	public function diffProvider() {
		$empty = $this->newEmpty( $this->getDummyId() );

		$spam = $this->newEmpty( $this->getDummyId() );
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$ham = $this->newEmpty( $this->getDummyId() );
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

		$this->assertInstanceOf( 'Wikibase\Repo\Content\EntityContentDiff', $actual );

		$expectedEntityOps = $expected->getEntityDiff()->getOperations();
		$actualEntityOps = $actual->getEntityDiff()->getOperations();

		// HACK: ItemDiff always sets this, even if it's empty. Ignore.
		if ( isset( $actualEntityOps['claim'] ) && $actualEntityOps['claim']->isEmpty() ) {
			unset( $actualEntityOps['claim'] );
		}

		$this->assertArrayEquals( $expectedEntityOps, $actualEntityOps, false, true );

		$expectedRedirectOps = $expected->getRedirectDiff()->getOperations();
		$actualRedirectOps = $actual->getRedirectDiff()->getOperations();

		$this->assertArrayEquals( $expectedRedirectOps, $actualRedirectOps, false, true );
	}

	public function patchedCopyProvider() {
		$spam = $this->newEmpty( $this->getDummyId() );
		$spam->getEntity()->setLabel( 'en', 'Spam' );

		$ham = $this->newEmpty( $this->getDummyId() );
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

	private function createTitleForEntity( Entity $entity ) {
		// NOTE: needs database access
		$this->entityStore->assignFreshId( $entity );
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$title = $titleLookup->getTitleForId( $entity->getId() );

		if ( !$title->exists() ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			$store->saveEntity( $entity, 'test', $GLOBALS['wgUser'] );

			// $title lies, make a new one
			$title = Title::makeTitleSafe( $title->getNamespace(), $title->getText() );
		}

		// sanity check - page must exist now
		$this->assertGreaterThan( 0, $title->getArticleID(), 'sanity check: getArticleID()' );
		$this->assertTrue( $title->exists(), 'sanity check: exists()' );

		return $title;
	}

	public function testGetSecondaryDataUpdates() {
		$entityContent = $this->newEmpty();
		$title = $this->createTitleForEntity( $entityContent->getEntity() );

		// NOTE: $title->exists() must be true.
		$updates = $entityContent->getSecondaryDataUpdates( $title );

		$this->assertDataUpdates( $updates );
	}

	public function testGetDeletionUpdates() {
		$entityContent = $this->newEmpty();
		$title = $this->createTitleForEntity( $entityContent->getEntity() );

		$updates = $entityContent->getDeletionUpdates( new WikiPage( $title ) );

		$this->assertDataUpdates( $updates );
	}

	private function assertDataUpdates( $updates ) {
		$this->assertInternalType( 'array', $updates );
		$this->assertContainsOnlyInstancesOf( 'DataUpdate', $updates );
	}

	public function entityRedirectProvider() {
		return array(
			'empty' => array( $this->newEmpty(), null ),
		);
	}

	/**
	 * @dataProvider entityRedirectProvider
	 */
	public function testGetEntityRedirect( EntityContent $content, EntityRedirect $redirect = null ) {
		$this->assertEquals( $content->getEntityRedirect(), $redirect );

		if ( $redirect === null ) {
			$this->assertNull( $content->getRedirectTarget() );
		} else {
			$this->assertNotNull( $content->getRedirectTarget() );
		}
	}

}
