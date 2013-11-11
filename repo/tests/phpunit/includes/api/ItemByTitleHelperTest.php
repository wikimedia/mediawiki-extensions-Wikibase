<?php
namespace Wikibase\Test;

use Wikibase\Api\ApiWikibase;
use Wikibase\Api\ItemByTitleHelper;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SiteLinkCache;
use Wikibase\StringNormalizer;

/**
 * Tests for the ItemByTitleHelper api helper class.
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class ItemByTitleHelperTest extends \MediaWikiTestCase {

	public function getSiteStoreMock() {
		$dummySite = new \MediaWikiSite();

		$siteStoreMock = $this->getMockBuilder( '\SiteStore' )
			->disableOriginalConstructor()
			->getMock();

		$siteStoreMock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnValue( $dummySite ) );

		return $siteStoreMock;
	}

	/**
	 * Gets a mock ApiWikibase object which excepts a certain number
	 * of calls to certain (sub)methods
	 *
	 * @param integer $expectedResultBuilderCalls How many times do we expect the result builder to be called
	 * @param bool $expectDieUsage Whether we expect ApiWikibase::dieUsage to be called
	 * @return ApiWikibase
	 */
	public function getApiWikibaseMock( $expectedResultBuilderCalls = 0, $expectDieUsage = false ) {
		$apiWikibaseMock = $this->getMockBuilder( '\Wikibase\Api\ApiWikibase' )
			->disableOriginalConstructor()
			->getMock();

		$apiWikibaseMock
			->expects( $expectDieUsage ? $this->once() : $this->never() )
			->method( 'dieUsage' )
			->will( $this->throwException( new \UsageException( 'MockUsageExceptionMessage', 'MockUsageExceptionCode' ) ) );

		$apiResultBuilderMock = $this->getMockBuilder( 'Wikibase\Api\ResultBuilder' )
			->disableOriginalConstructor()
			->getMock();
		$apiResultBuilderMock->expects( $this->any() )
			->method( 'addMissingEntity' );
		$apiResultBuilderMock->expects( $this->any() )
			->method( 'addNormalizedTitle' );

		$apiWikibaseMock->expects( $this->exactly( $expectedResultBuilderCalls ) )
			->method( 'getResultBuilder' )
			->will( $this->returnValue( $apiResultBuilderMock ) );

		return $apiWikibaseMock;
	}

	/**
	 * @param integer|null $entityId
	 * @return SiteLinkCache
	 */
	public function getSiteLinkCacheMock( $entityId = null ) {
		$siteLinkCacheMock = $this->getMockBuilder( '\Wikibase\SiteLinkCache' )
			->disableOriginalConstructor()
			->getMock();

		if ( !is_null( $entityId ) ) {
			$siteLinkCacheMock->expects( $this->any() )
				->method( 'getItemIdForLink' )
				->will( $this->returnValue( $entityId ) );
		}

		return $siteLinkCacheMock;
	}

	public function testGetEntityIdsSuccess() {
		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

		$expectedEntityId = new ItemId( 'q123' );
		$expectedEntityId = $entityIdFormatter->format( $expectedEntityId );

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getApiWikibaseMock( 0 ),
			$this->getSiteLinkCacheMock( 123 ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = array( 'FooSite' );
		$titles = array( 'Berlin', 'London' );

		$entityIds = $itemByTitleHelper->getEntityIds( $sites, $titles, false );

		foreach( $entityIds as $entityId ) {
			$this->assertEquals( $expectedEntityId, $entityId );
		}
	}

	/**
	 * Try to get an entity id for a page that's normalized with normalization.
	 */
	public function testGetEntityIdNormalized() {
		$itemByTitleHelper = new ItemByTitleHelper(
		// Two values should be added: The normalization and the failure to find an entity
			$this->getApiWikibaseMock( 2 ),
			$this->getSiteLinkCacheMock( false ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = array( 'FooSite' );
		$titles = array( 'berlin_germany' );

		$entityIds = $itemByTitleHelper->getEntityIds( $sites, $titles, true );

		// Still nothing could be found
		$this->assertEquals( array(), $entityIds );
	}

	/**
	 * Tries to get entity ids for two pages which don't exist.
	 * Makes sure that the failures are added to the API result.
	 */
	public function testGetEntityIdsNotFound() {
		$itemByTitleHelper = new ItemByTitleHelper(
		// Two result values should be added (for both titles which wont be found)
			$this->getApiWikibaseMock( 2 ),
			$this->getSiteLinkCacheMock( false ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = array( 'FooSite' );
		$titles = array( 'Berlin', 'London' );

		$entityIds = $itemByTitleHelper->getEntityIds( $sites, $titles, false );
	}

	/**
	 * Makes sure the request will fail if we want normalization for two titles
	 */
	public function testGetEntityIdsNormalizationNotAllowed() {
		$this->setExpectedException( 'UsageException' );

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getApiWikibaseMock( 0, true ),
			$this->getSiteLinkCacheMock( 1 ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$sites = array( 'FooSite' );
		$titles = array( 'Berlin', 'London' );

		$entityIds = $itemByTitleHelper->getEntityIds( $sites, $titles, true );
	}

	static public function normalizeTitleProvider() {
		return array(
			array(
				'foo_bar',
				123,
				// The normalization should be noted
				1
			),
			array(
				'Bar',
				false,
				// Already normalized
				0
			),
		);
	}

	/**
	 * @dataProvider normalizeTitleProvider
	 */
	public function testNormalizeTitle( $title, $expectedEntityId, $expectedAddValueCount ) {
		$dummySite = new \MediaWikiSite();

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getApiWikibaseMock( $expectedAddValueCount ),
			$this->getSiteLinkCacheMock( $expectedEntityId ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$entityId = $itemByTitleHelper->normalizeTitle( $title, $dummySite );

		$this->assertEquals( $expectedEntityId, $entityId );
		// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
		$this->assertEquals( \Title::newFromText( $title )->getPrefixedText(), $title );
	}

	public function testNoSites(){
		$this->setExpectedException( 'UsageException' );

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getApiWikibaseMock( null, true ),
			$this->getSiteLinkCacheMock( 123 ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$itemByTitleHelper->getEntityIds( array( ), array( 'barfoo' ), false );
	}

}
