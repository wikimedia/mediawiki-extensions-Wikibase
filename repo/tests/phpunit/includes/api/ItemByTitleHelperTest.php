<?php
namespace Wikibase\Test;

use Wikibase\Api\ItemByTitleHelper;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Settings;
use Wikibase\StringNormalizer;

/**
 * Tests for the ItemByTitleHelper api helper class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.4
 *
 * @ingroup Test
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
	 * Gets a mock ApiBase object which excepts a certain number
	 * of calls to certain (sub)methods
	 *
	 * @param integer|null $expectedAddValueCount How many times
	 * 		do we expect values to be added to the result (ApiResult::addValue)
	 * @param bool $expectDieUsage Whether we expect ApiBase::dieUsage
	 * 		to be called
	 */
	public function getApiBaseMock( $expectedAddValueCount = null, $expectDieUsage = false ) {
		$apiBaseMock = $this->getMockBuilder( '\ApiBase' )
			->disableOriginalConstructor()
			->getMock();

		$apiBaseMock
			->expects( $expectDieUsage ? $this->once() : $this->never() )
			->method( 'dieUsage' )
			->will( $this->throwException( new \UsageException( 'MockUsageExceptionMessage', 'MockUsageExceptionCode' ) ) );

		$apiResultMock = $this->getMockBuilder( '\ApiResult' )
			->disableOriginalConstructor()
			->getMock();

		if ( !is_null( $expectedAddValueCount ) ) {
			$apiResultMock->expects( $this->exactly( $expectedAddValueCount ) )
				->method( 'addValue' );
		}

		$apiBaseMock->expects( $this->any() )
			->method( 'getResult' )
			->will( $this->returnValue( $apiResultMock ) );

		return $apiBaseMock;
	}

	/**
	 * @param integer|null $entityId
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

		$expectedEntityId = new EntityId( Item::ENTITY_TYPE, 123 );
		$expectedEntityId = $entityIdFormatter->format( $expectedEntityId );

		$itemByTitleHelper = new ItemByTitleHelper(
			$this->getApiBaseMock( 0 ),
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
			$this->getApiBaseMock( 2 ),
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
			$this->getApiBaseMock( 2 ),
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
			$this->getApiBaseMock( 0, true ),
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
			$this->getApiBaseMock( $expectedAddValueCount ),
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
			$this->getApiBaseMock( null, true ),
			$this->getSiteLinkCacheMock( 123 ),
			$this->getSiteStoreMock(),
			new StringNormalizer()
		);

		$entityId = $itemByTitleHelper->getEntityIds( array( ), array( 'barfoo' ), false );
	}

}
