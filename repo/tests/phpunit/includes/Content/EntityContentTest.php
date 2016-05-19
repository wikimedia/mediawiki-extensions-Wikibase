<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Diff\Patcher\PatcherException;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\EntityContent;
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
 * @license GPL-2.0+
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

	protected function setUp() {
		global $wgGroupPermissions, $wgUser;

		parent::setUp();

		$this->originalGroupPermissions = $wgGroupPermissions;
		$this->originalUser = $wgUser;

		$this->entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();
	}

	protected function tearDown() {
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
	 * @return EntityId
	 */
	abstract protected function getDummyId();

	/**
	 * @param EntityId|null $entityId
	 *
	 * @return EntityContent
	 */
	abstract protected function newEmpty( EntityId $entityId = null );

	/**
	 * @dataProvider getTextForSearchIndexProvider
	 */
	public function testGetTextForSearchIndex( EntityContent $entityContent, $expected ) {
		$this->assertSame( $expected, $entityContent->getTextForSearchIndex() );
	}

	private function setLabel( EntityDocument $entity, $lang, $text ) {
		if ( $entity instanceof FingerprintProvider ) {
			$entity->getFingerprint()->setLabel( $lang, $text );
		} else {
			throw new \InvalidArgumentException( 'FingerprintProvider expected!' );
		}
	}

	public function getTextForSearchIndexProvider() {
		$entityContent = $this->newEmpty();
		$this->setLabel( $entityContent->getEntity(), 'en', "cake" );

		return array(
			array( $entityContent, 'cake' )
		);
	}

	public function testWikibaseTextForSearchIndex() {
		$entityContent = $this->newEmpty();
		$this->setLabel( $entityContent->getEntity(), 'en', "cake" );

		$this->mergeMwGlobalArrayValue( 'wgHooks', array(
			'WikibaseTextForSearchIndex' => array(
				function ( $actualEntityContent, &$text ) use ( $entityContent ) {
					$this->assertSame( $entityContent, $actualEntityContent );
					$this->assertSame( 'cake', $text );

					$text .= "\nHOOK";
					return true;
				},
			),
		) );

		$text = $entityContent->getTextForSearchIndex();
		$this->assertSame( "cake\nHOOK", $text, 'Text for search index should be updated by the hook' );
	}

	public function testWikibaseTextForSearchIndex_abort() {
		$entityContent = $this->newEmpty();
		$this->setLabel( $entityContent->getEntity(), 'en', "cake" );

		$this->mergeMwGlobalArrayValue( 'wgHooks', array(
			'WikibaseTextForSearchIndex' => array(
				function () {
					return false;
				},
			),
		) );

		$text = $entityContent->getTextForSearchIndex();
		$this->assertSame( '', $text, 'Text for search index should be empty if the hook returned false' );
	}

	public function testGetParserOutput() {
		$content = $this->newEmpty();

		//@todo: Use a fake ID, no need to hit the database once we
		//       got rid of the rest of the storage logic.
		$this->entityStore->assignFreshId( $content->getEntity() );

		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title );

		$expectedUsedOptions = array( 'userlang', 'editsection' );
		$actualOptions = $parserOutput->getUsedOptions();
		$this->assertEquals(
			$expectedUsedOptions,
			$actualOptions,
			'Cache-split flags are not what they should be',
			0.0,
			1,
			true
		);

		$this->assertInstanceOf( ParserOutput::class, $parserOutput );
		$this->assertSame( EntityContent::STATUS_EMPTY, $parserOutput->getProperty( 'wb-status' ) );
	}

	public function providePageProperties() {
		$cases = array();
		$emptyContent = $this->newEmpty( $this->getDummyId() );

		$cases['empty'] = array(
			$emptyContent,
			array( 'wb-status' => EntityContent::STATUS_EMPTY, 'wb-claims' => 0 )
		);

		$contentWithLabel = $this->newEmpty( $this->getDummyId() );
		$this->setLabel( $contentWithLabel->getEntity(), 'en', 'Foo' );

		$cases['labels'] = array(
			$contentWithLabel,
			array( 'wb-status' => EntityContent::STATUS_STUB, 'wb-claims' => 0 )
		);

		return $cases;
	}

	/**
	 * @dataProvider providePageProperties
	 */
	public function testPageProperties( EntityContent $content, array $expectedProps ) {
		$title = Title::newFromText( 'Foo' );
		$parserOutput = $content->getParserOutput( $title, null, null, false );

		foreach ( $expectedProps as $name => $expected ) {
			$actual = $parserOutput->getProperty( $name );
			$this->assertSame( $expected, $actual, "page property $name" );
		}
	}

	public function provideGetEntityStatus() {
		$contentWithLabel = $this->newEmpty();
		$this->setLabel( $contentWithLabel->getEntity(), 'de', 'xyz' );

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
	public function testGetEntityStatus( EntityContent $content, $expected ) {
		$this->assertSame( $expected, $content->getEntityStatus() );
	}

	abstract public function provideGetEntityId();

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
		$this->setLabel( $labeledEntityContent->getEntity(), 'de', 'xyz' );

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
	public function testGetEntityPageProperties( EntityContent $content, array $pageProps ) {
		$actual = $content->getEntityPageProperties();

		foreach ( $pageProps as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual );
			$this->assertSame( $value, $actual[$key], $key );
		}

		$this->assertArrayEquals( array_keys( $pageProps ), array_keys( $actual ) );
	}

	public function diffProvider() {
		$empty = $this->newEmpty( $this->getDummyId() );

		$spam = $this->newEmpty( $this->getDummyId() );
		$this->setLabel( $spam->getEntity(), 'en', 'Spam' );

		$ham = $this->newEmpty( $this->getDummyId() );
		$this->setLabel( $ham->getEntity(), 'en', 'Ham' );

		$spamToHam = new DiffOpChange( 'Spam', 'Ham' );
		$spamToHamDiff = new EntityDiff( array(
			'label' => new Diff( array( 'en' => $spamToHam ) ),
		) );

		return array(
			'empty' => array( $empty, $empty, new EntityContentDiff( new EntityDiff(), new Diff() ) ),
			'same' => array( $ham, $ham, new EntityContentDiff( new EntityDiff(), new Diff() ) ),
			'spam to ham' => array( $spam, $ham, new EntityContentDiff( $spamToHamDiff, new Diff() ) ),
		);
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetDiff( EntityContent $a, EntityContent $b, EntityContentDiff $expected ) {
		$actual = $a->getDiff( $b );

		$this->assertInstanceOf( EntityContentDiff::class, $actual );

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
		$this->setLabel( $spam->getEntity(), 'en', 'Spam' );

		$ham = $this->newEmpty( $this->getDummyId() );
		$this->setLabel( $ham->getEntity(), 'en', 'Ham' );

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
	 */
	public function testGetPatchedCopy( EntityContent $base, EntityContentDiff $patch, EntityContent $expected = null ) {
		if ( $expected === null ) {
			$this->setExpectedException( PatcherException::class );
		}

		$actual = $base->getPatchedCopy( $patch );

		if ( $expected !== null ) {
			$this->assertTrue( $expected->equals( $actual ), 'equals()' );
		}
	}

	public function copyProvider() {
		$empty = $this->newEmpty();
		$labels = $this->newEmpty();

		$this->setLabel( $labels->getEntity(), 'en', 'Foo' );

		return array(
			'empty' => array( $empty ),
			'labels' => array( $labels ),
		);
	}

	/**
	 * @dataProvider copyProvider
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
		$this->setLabel( $labels1->getEntity(), 'en', 'Foo' );

		$labels2 = $this->newEmpty();
		$this->setLabel( $labels2->getEntity(), 'de', 'Foo' );

		return array(
			'empty' => array( $empty, $empty, true ),
			'same labels' => array( $labels1, $labels1, true ),
			'different labels' => array( $labels1, $labels2, false ),
		);
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( EntityContent $a, EntityContent $b, $expected ) {
		$this->assertSame( $expected, $a->equals( $b ) );
		$this->assertSame( $expected, $b->equals( $a ) );
	}

	private function createTitleForEntity( EntityDocument $entity ) {
		// NOTE: needs database access
		$this->entityStore->assignFreshId( $entity );
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$title = $titleLookup->getTitleForId( $entity->getId() );

		if ( !$title->exists( Title::GAID_FOR_UPDATE ) ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
			$store->saveEntity( $entity, 'test', $GLOBALS['wgUser'] );

			// $title lies, make a new one
			$title = Title::makeTitleSafe( $title->getNamespace(), $title->getText() );
		}

		// sanity check - page must exist now
		$this->assertTrue( $title->exists( Title::GAID_FOR_UPDATE ), 'sanity check: exists()' );

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
