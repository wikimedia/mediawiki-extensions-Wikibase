<?php

namespace Wikibase\Repo\Tests\Content;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\Patcher\PatcherException;
use InvalidArgumentException;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Rdbms\IDBAccessObject;

/**
 * @covers \Wikibase\Repo\Content\EntityContent
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class EntityContentTestCase extends MediaWikiIntegrationTestCase {

	/** @var array */
	private $originalGroupPermissions;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	protected function setUp(): void {
		global $wgGroupPermissions;

		parent::setUp();

		$this->originalGroupPermissions = $wgGroupPermissions;

		$this->entityStore = WikibaseRepo::getEntityStore();
	}

	protected function tearDown(): void {
		global $wgGroupPermissions;

		$wgGroupPermissions = $this->originalGroupPermissions;

		parent::tearDown();
	}

	/**
	 * @return EntityId
	 */
	abstract protected static function getDummyId();

	/**
	 * @return string
	 */
	abstract protected static function getEntityType();

	/**
	 * @return EntityContent An entirely empty content object with no EntityHolder and no entity.
	 */
	abstract protected static function newEmpty();

	/**
	 * @param EntityId|null $entityId
	 *
	 * @return EntityContent A content object that contains an entity that is empty.
	 */
	abstract protected static function newBlank( EntityId $entityId = null );

	public function testIsEmpty() {
		$this->assertTrue( $this->newEmpty()->isEmpty(), 'empty' );
		$this->assertTrue( $this->newBlank()->isEmpty(), 'blank' );
	}

	/**
	 * @dataProvider getTextForSearchIndexProvider
	 */
	public function testGetTextForSearchIndex( EntityContent $entityContent, $expected ) {
		$this->assertSame( $expected, $entityContent->getTextForSearchIndex() );
	}

	private static function setLabel( EntityDocument $entity, $languageCode, $text ) {
		if ( !( $entity instanceof LabelsProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a LabelsProvider' );
		}

		$entity->getLabels()->setTextForLanguage( $languageCode, $text );
	}

	public static function getTextForSearchIndexProvider() {
		$entityContent = static::newBlank();
		self::setLabel( $entityContent->getEntity(), 'en', "cake" );

		return [
			[ $entityContent, 'cake' ],
		];
	}

	public function testWikibaseTextForSearchIndex() {
		$entityContent = $this->newBlank();
		$this->setLabel( $entityContent->getEntity(), 'en', "cake" );

		$this->setTemporaryHook(
			'WikibaseTextForSearchIndex',
			function ( $actualEntityContent, &$text ) use ( $entityContent ) {
				$this->assertSame( $entityContent, $actualEntityContent );
				$this->assertSame( 'cake', $text );

				$text .= "\nHOOK";
				return true;
			}
		);

		$text = $entityContent->getTextForSearchIndex();
		$this->assertSame( "cake\nHOOK", $text, 'Text for search index should be updated by the hook' );
	}

	public function testWikibaseTextForSearchIndex_abort() {
		$entityContent = $this->newBlank();
		$this->setLabel( $entityContent->getEntity(), 'en', "cake" );

		$this->setTemporaryHook(
			'WikibaseTextForSearchIndex',
			static function () {
				return false;
			}
		);

		$text = $entityContent->getTextForSearchIndex();
		$this->assertSame( '', $text, 'Text for search index should be empty if the hook returned false' );
	}

	abstract public static function provideGetEntityId();

	/**
	 * @dataProvider provideGetEntityId
	 */
	public function testGetEntityId( callable $contentAndExpectedFactory ) {
		[ $content, $expected ] = $contentAndExpectedFactory( $this );
		$actual = $content->getEntityId();

		$this->assertEquals( $expected, $actual );
	}

	abstract public static function provideContentObjectsWithoutId();

	/**
	 * @dataProvider provideContentObjectsWithoutId
	 */
	public function testGetEntityIdExceptions( EntityContent $content ) {
		$this->expectException( RuntimeException::class );
		$content->getEntityId();
	}

	public static function provideGetEntityPageProperties() {
		return [
			'empty' => [
				fn ( self $self ) => $self->newBlank(),
				[
					'wb-claims' => 0,
				],
			],
			'labels' => [
				function ( self $self ) {
					$labeledEntityContent = $self->newBlank();
					$self->setLabel( $labeledEntityContent->getEntity(), 'de', 'xyz' );
					return $labeledEntityContent;
				},
				[
					'wb-claims' => 0,
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetEntityPageProperties
	 */
	public function testGetEntityPageProperties( callable $contentFactory, array $pageProps ) {
		$content = $contentFactory( $this );
		$actual = $content->getEntityPageProperties();

		foreach ( $pageProps as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual );
			$this->assertSame( $value, $actual[$key], $key );
		}

		$this->assertArrayEquals( array_keys( $pageProps ), array_keys( $actual ) );
	}

	public static function diffProvider() {
		$entityType = static::getEntityType();

		$empty = static::newEmpty();

		$blank = static::newBlank( static::getDummyId() );

		$spam = static::newBlank( static::getDummyId() );
		static::setLabel( $spam->getEntity(), 'en', 'Spam' );

		$ham = static::newBlank( static::getDummyId() );
		static::setLabel( $ham->getEntity(), 'en', 'Ham' );

		$emptyToHamDiff = new EntityDiff( [
			'label' => new Diff( [ 'en' => new DiffOpAdd( 'Ham' ) ] ),
		] );

		$blankToHamDiff = new EntityDiff( [
			'label' => new Diff( [ 'en' => new DiffOpAdd( 'Ham' ) ] ),
		] );

		$spamToHamDiff = new EntityDiff( [
			'label' => new Diff( [ 'en' => new DiffOpChange( 'Spam', 'Ham' ) ] ),
		] );

		return [
			'empty' => [
				fn () => [ $empty, $empty ],
				new EntityContentDiff( new EntityDiff(), new Diff(), $entityType ),
			],
			'blank' => [
				fn () => [ $blank, $blank ],
				new EntityContentDiff( new EntityDiff(), new Diff(), $entityType ),
			],
			'same' => [
				fn () => [ $ham, $ham ],
				new EntityContentDiff( new EntityDiff(), new Diff(), $entityType ),
			],
			'empty to ham' => [
				fn () => [ $empty, $ham ],
				new EntityContentDiff( $emptyToHamDiff, new Diff(), $entityType ),
			],
			'blank to ham' => [
				fn () => [ $blank, $ham ],
				new EntityContentDiff( $blankToHamDiff, new Diff(), $entityType ),
			],
			'spam to ham' => [
				fn () => [ $spam, $ham ],
				new EntityContentDiff( $spamToHamDiff, new Diff(), $entityType ),
			],
		];
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGetDiff( callable $entityContentFactory, EntityContentDiff $expected ) {
		[ $a, $b ] = $entityContentFactory( $this );
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

		// HACK: For empty -> content values, this is expecting [ 'entity' => new DiffOpAdd( 'P100' ) ] etc.
		if ( count( $expectedRedirectOps ) !== count( $actualRedirectOps ) ) {
			return;
		}

		$this->assertArrayEquals( $expectedRedirectOps, $actualRedirectOps, false, true );
	}

	public static function patchedCopyProvider() {
		$spam = static::newBlank( static::getDummyId() );
		$entityType = static::getEntityType();
		static::setLabel( $spam->getEntity(), 'en', 'Spam' );

		$ham = static::newBlank( static::getDummyId() );
		static::setLabel( $ham->getEntity(), 'en', 'Ham' );

		$spamToHamDiff = new EntityDiff( [
			'label' => new Diff( [ 'en' => new DiffOpChange( 'Spam', 'Ham' ) ] ),
		] );

		return [
			'empty' => [
				fn () => [
					$spam,
					new EntityContentDiff( new EntityDiff(), new Diff(), $entityType ),
					$spam,
				],
			],
			'spam to ham' => [
				fn () => [
					$spam,
					new EntityContentDiff( $spamToHamDiff, new Diff(), $entityType ),
					$ham,
				],
			],
		];
	}

	/**
	 * @dataProvider patchedCopyProvider
	 */
	public function testGetPatchedCopy( callable $contentFactory ) {
		[ $base, $patch, $expected ] = $contentFactory( $this );

		if ( $expected === null ) {
			$this->expectException( PatcherException::class );
		}

		$actual = $base->getPatchedCopy( $patch );

		if ( $expected !== null ) {
			$this->assertTrue( $expected->equals( $actual ), 'equals()' );
			// Warning, the equals above does not compare the IDs of non-redirects!
			$this->assertEquals( $expected->getEntityId(), $actual->getEntityId() );
		}
	}

	public static function copyProvider() {
		$labels = static::newBlank();
		static::setLabel( $labels->getEntity(), 'en', 'Foo' );

		return [
			'no entity' => [ fn ( self $self ) => $self->newEmpty() ],
			'empty entity' => [ fn ( self $self ) => $self->newBlank() ],
			'labels' => [ fn () => $labels ],
		];
	}

	/**
	 * @dataProvider copyProvider
	 */
	public function testCopy( callable $contentFactory ) {
		$content = $contentFactory( $this );
		$copy = $content->copy();
		$this->assertTrue( $content->equals( $copy ), 'Copy must be equal to the original.' );
		$this->assertSame( get_class( $content ), get_class( $copy ), 'Copy must have the same type.' );
	}

	public static function equalsProvider() {
		$empty = static::newEmpty();

		$labels1 = static::newBlank();
		static::setLabel( $labels1->getEntity(), 'en', 'Foo' );

		$labels2 = static::newBlank();
		static::setLabel( $labels2->getEntity(), 'de', 'Foo' );

		return [
			'empty' => [ fn () => [ $empty, $empty ], true ],
			'same labels' => [ fn () => [ $labels1, $labels1 ], true ],
			'different labels' => [ fn () => [ $labels1, $labels2 ], false ],
			'empty and not empty' => [ fn () => [ $empty, $labels1 ], false ],
			'not empty and empty' => [ fn () => [ $labels1, $empty ], false ],
		];
	}

	/**
	 * @dataProvider equalsProvider
	 */
	public function testEquals( callable $contentFactory, $expected ) {
		[ $a, $b ] = $contentFactory( $this );
		$this->assertSame( $expected, $a->equals( $b ) );
		$this->assertSame( $expected, $b->equals( $a ) );
	}

	private function createTitleForEntity( EntityDocument $entity ) {
		// NOTE: needs database access
		$this->entityStore->assignFreshId( $entity );
		$titleLookup = WikibaseRepo::getEntityTitleStoreLookup();
		$title = $titleLookup->getTitleForId( $entity->getId() );

		if ( !$title->exists( IDBAccessObject::READ_LATEST ) ) {
			$store = WikibaseRepo::getEntityStore();
			$store->saveEntity( $entity, 'test', $this->getTestUser()->getUser() );

			// $title lies, make a new one
			$title = Title::makeTitleSafe( $title->getNamespace(), $title->getText() );
		}

		// sanity check - page must exist now
		$this->assertTrue( $title->exists( IDBAccessObject::READ_LATEST ), 'sanity check: exists()' );

		return $title;
	}

	public static function entityRedirectProvider() {
		return [
			'empty' => [
				fn ( self $self ) => $self->newEmpty(),
				null,
			],
		];
	}

	/**
	 * @dataProvider entityRedirectProvider
	 */
	public function testGetEntityRedirect( callable $contentProvider, EntityRedirect $redirect = null ) {
		$content = $contentProvider( $this );
		$this->assertEquals( $content->getEntityRedirect(), $redirect );

		if ( $redirect === null ) {
			$this->assertNull( $content->getRedirectTarget() );
		} else {
			$this->assertNotNull( $content->getRedirectTarget() );
		}
	}

}
