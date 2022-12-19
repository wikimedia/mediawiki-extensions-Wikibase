<?php

namespace Wikibase\Repo\Tests\Api;

use ApiBase;
use ApiUsageException;
use HashSiteStore;
use MediaWikiSite;
use Site;
use SiteLookup;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityByLinkedTitleLookup;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Repo\Api\EntityByTitleHelper;
use Wikibase\Repo\Api\ResultBuilder;

/**
 * @covers \Wikibase\Repo\Api\ItemByTitleHelper
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @author Addshore
 */
class EntityByTitleHelperTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return SiteLookup
	 */
	public function getSiteLookupMock() {
		$site = $this->createMock( Site::class );

		$site->method( 'getGlobalId' )
			->willReturn( 'FooSite' );

		return new HashSiteStore( [ $site ] );
	}

	/**
	 * Gets a mock ResultBuilder object which excepts a certain number of calls to certain methods
	 *
	 * @param int $expectedNormalizedTitle number of expected call to this method
	 * @return ResultBuilder
	 */
	public function getResultBuilderMock( $expectedNormalizedTitle = 0 ) {
		$apiResultBuilderMock = $this->createMock( ResultBuilder::class );
		$apiResultBuilderMock->expects( $this->exactly( $expectedNormalizedTitle ) )
			->method( 'addNormalizedTitle' );

		return $apiResultBuilderMock;
	}

	/**
	 * @param mixed $itemId
	 *
	 * @return EntityByLinkedTitleLookup
	 */
	private function getEntityByLinkedTitleLookupMock( $itemId ) {
		$siteLinkLookupMock = $this->createMock( EntityByLinkedTitleLookup::class );

		$siteLinkLookupMock->method( 'getEntityIdForLinkedTitle' )
				->willReturn( $itemId );

		return $siteLinkLookupMock;
	}

	public function testGetEntityIdsSuccess() {
		$expectedEntityId = new ItemId( 'Q123' );
		$expectedEntityId = $expectedEntityId->getSerialization();

		$entityByTitleHelper = new EntityByTitleHelper(
			$this->createMock( ApiBase::class ),
			$this->getResultBuilderMock(),
			$this->getEntityByLinkedTitleLookupMock( new ItemId( 'Q123' ) ),
			$this->getSiteLookupMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'Berlin', 'London' ];

		list( $entityIds, ) = $entityByTitleHelper->getEntityIds( $sites, $titles, false );

		foreach ( $entityIds as $entityId ) {
			$this->assertEquals( $expectedEntityId, $entityId );
		}
	}

	/**
	 * Try to get an entity id for a page that's normalized with normalization.
	 */
	public function testGetEntityIdNormalized() {
		$entityByTitleHelper = new EntityByTitleHelper(
			$this->createMock( ApiBase::class ),
			// Two values should be added: The normalization and the failure to find an entity
			$this->getResultBuilderMock( 1 ),
			$this->getEntityByLinkedTitleLookupMock( null ),
			$this->getSiteLookupMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'berlin_germany' ];

		list( $entityIds, ) = $entityByTitleHelper->getEntityIds( $sites, $titles, true );

		// Still nothing could be found
		$this->assertEquals( [], $entityIds );
	}

	/**
	 * Tries to get entity ids for two pages which don't exist.
	 * Makes sure that the failures are added to the API result.
	 */
	public function testGetEntityIdsNotFound() {
		$entityByTitleHelper = new EntityByTitleHelper(
			$this->createMock( ApiBase::class ),
			// Two result values should be added (for both titles which wont be found)
			$this->getResultBuilderMock(),
			$this->getEntityByLinkedTitleLookupMock( false ),
			$this->getSiteLookupMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'Berlin', 'London' ];

		$entityByTitleHelper->getEntityIds( $sites, $titles, false );
	}

	/**
	 * Makes sure the request will fail if we want normalization for two titles
	 */
	public function testGetEntityIdsNormalizationNotAllowed() {
		$this->expectException( ApiUsageException::class );

		$entityByTitleHelper = new EntityByTitleHelper(
			$this->createMock( ApiBase::class ),
			$this->getResultBuilderMock(),
			$this->getEntityByLinkedTitleLookupMock( 1 ),
			$this->getSiteLookupMock(),
			new StringNormalizer()
		);

		$sites = [ 'FooSite' ];
		$titles = [ 'Berlin', 'London' ];

		$entityByTitleHelper->getEntityIds( $sites, $titles, true );
	}

	public function normalizeTitleProvider() {
		return [
			[
				'foo_bar',
				123,
				// The normalization should be noted
				1,
			],
			[
				'Bar',
				false,
				// Already normalized
				0,
			],
		];
	}

	/**
	 * @dataProvider normalizeTitleProvider
	 */
	public function testNormalizeTitle( $title, $expectedEntityId, $expectedAddNormalizedCalls ) {
		$dummySite = new MediaWikiSite();

		$entityByTitleHelper = new EntityByTitleHelper(
			$this->createMock( ApiBase::class ),
			$this->getResultBuilderMock( $expectedAddNormalizedCalls ),
			$this->getEntityByLinkedTitleLookupMock( $expectedEntityId ),
			$this->getSiteLookupMock(),
			new StringNormalizer()
		);

		$entityByTitleHelper->normalizeTitle( $title, $dummySite );

		// Normalization in unit tests is actually using Title::getPrefixedText instead of a real API call
		// XXX: The Normalized title is passed by via reference to $title...
		$this->assertEquals( Title::newFromTextThrow( $title )->getPrefixedText(), $title );
	}

	public function notEnoughInputProvider() {
		return [
			[
				// Request with no sites
				[],
				[ 'barfoo' ],
				false,
			],
			[
				// Request with no titles
				[ 'enwiki' ],
				[],
				false,
			],
		];
	}

	/**
	 * @dataProvider notEnoughInputProvider
	 */
	public function testNotEnoughInput( array $sites, array $titles, $normalize ) {
		$this->expectException( ApiUsageException::class );

		$entityByTitleHelper = new EntityByTitleHelper(
			$this->createMock( ApiBase::class ),
			$this->getResultBuilderMock(),
			$this->getEntityByLinkedTitleLookupMock( new ItemId( 'Q123' ) ),
			$this->getSiteLookupMock(),
			new StringNormalizer()
		);

		$entityByTitleHelper->getEntityIds( $sites, $titles, $normalize );
	}

}
