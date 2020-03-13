<?php


namespace Wikibase\Repo\Tests\Hooks;

use MediaWikiTestCase;
use Psr\Log\LoggerInterface;
use SkinTemplate;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\BuildNavUrlsHookHandler;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Hooks\BuildNavUrlsHookHandler
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class BuildNavUrlsHookHandlerTest extends MediaWikiTestCase {

	private $entityNamespaceLookup;
	private $entityIdLookup;
	private $entityLookup;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|SkinTemplate
	 */
	private $skin;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|Title
	 */
	private $mockTitle;
	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject|EntityId
	 */
	private $mockEntityId;
	/**
	 * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	public function setUp(): void {
		parent::setUp();

		$this->mockEntityId = $this->createMock( EntityId::class );

		$this->entityLookup = $this->createMock( EntityLookup::class );

		$this->entityIdLookup = $this->createMock( EntityIdLookup::class );

		$this->entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );

		$this->skin = $this->createMock( SkinTemplate::class );

		$this->mockTitle = $this->createMock( Title::class );

		$this->logger = $this->createMock( LoggerInterface::class );

		$this->entityLookup
			->method( 'hasEntity' )
			->willReturn( true );

		$this->entityIdLookup
			->method( 'getEntityIdForTitle' )
			->willReturn( $this->mockEntityId );

		$this->entityNamespaceLookup
			->method( 'isNamespaceWithEntities' )
			->willReturn( true );

		$this->skin->method( 'getTitle' )->willReturn( $this->mockTitle );
	}

	public function testNewFromGlobalState() {
		$this->assertInstanceOf(
			BuildNavUrlsHookHandler::class,
			BuildNavUrlsHookHandler::newFromGlobalState()
		);
	}

	public function testBuildConceptUri() {
		$resultArray = $this->getHookHandler()->buildConceptUris( $this->skin );

		$this->assertArrayHasKey( 'wb-concept-uri', $resultArray );
	}

	public function test_buildConceptUris_WithNoTitleReturnsEmptyArray() {
		$skin = $this->createMock( SkinTemplate::class );
		$skin->method( 'getTitle' )->willReturn( null );

		$this->assertArrayEquals( [], $this->getHookHandler()->buildConceptUris( $skin ) );
	}

	public function test_buildConceptUris_WithNoEntityIdReturnsEmptyArray() {
		$this->entityIdLookup = $this->createMock( EntityIdLookup::class );
		$this->entityIdLookup->method( 'getEntityIdForTitle' )->willReturn( null );

		$this->assertArrayEquals( [], $this->getHookHandler()->buildConceptUris( $this->skin ) );
	}

	public function testGetValidEntityId() {
		$this->assertSame( $this->getHookHandler()->getValidEntityId( $this->mockTitle ), $this->mockEntityId );
	}

	public function test_getValidEntityId_WithNoNamespaceReturnsNull() {
		$this->entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );
		$this->entityNamespaceLookup->method( 'isNamespaceWithEntities' )->willReturn( false );

		$this->assertNull( $this->getHookHandler()->getValidEntityId( $this->mockTitle ) );
	}

	public function test_getValidEntityId_WhenRedirectSkipEntityCheck() {
		$this->mockTitle = $this->createMock( Title::class );
		$this->mockTitle->method( 'isRedirect' )->willReturn( true );

		$this->entityLookup
			->expects( $this->never() )
			->method( 'hasEntity' );

		$this->assertSame( $this->getHookHandler()->getValidEntityId( $this->mockTitle ), $this->mockEntityId );
	}

	public function test_getValidEntityId_WhenEntityLookupThrowsReturnsNull() {
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->entityLookup->method( 'hasEntity' )->willThrowException( new EntityLookupException( $this->mockEntityId ) );

		$this->assertNull( $this->getHookHandler()->getValidEntityId( $this->mockTitle ) );
	}

	public function test_getValidEntityId_WhenEntityLookupThrowsLogsWarning() {
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->entityLookup->method( 'hasEntity' )->willThrowException( new EntityLookupException( $this->mockEntityId ) );

		$this->logger
			->expects( $this->once() )
			->method( 'warning' );

		$this->getHookHandler()->getValidEntityId( $this->mockTitle );
	}

	public function test_getValidEntityId_WithNoEntityReturnsNull() {
		$this->entityLookup = $this->createMock( EntityLookup::class );
		$this->entityLookup->method( 'hasEntity' )->willReturn( false );

		$this->assertNull( $this->getHookHandler()->getValidEntityId( $this->mockTitle ) );
	}

	private function getHookHandler(): TestingAccessWrapper {
		$baseUri = 'http://foo';
		$hookHandler = new BuildNavUrlsHookHandler(
			$baseUri,
			$this->entityIdLookup,
			$this->entityLookup,
			$this->entityNamespaceLookup,
			$this->logger
		);

		return TestingAccessWrapper::newFromObject( $hookHandler );
	}
}
