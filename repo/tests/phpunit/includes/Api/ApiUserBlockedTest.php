<?php

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use Block;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;

/**
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group medium
 */
class ApiUserBlockedTest extends WikibaseApiTestCase {

	/** @var Block */
	private $block;

	protected function setUp() {
		global $wgWBRepoSettings;

		parent::setUp();

		$testuser = self::getTestUser()->getUser();
		$this->block = new Block( [
			'address' => $testuser,
			'reason' => 'testing in ' . __CLASS__,
			'by' => $testuser->getId(),
		] );
		$this->block->insert();

		$customRepoSettings = $wgWBRepoSettings;
		$customRepoSettings['siteLinkGroups'] = [ 'wikipedia' ];
		$this->setMwGlobals( 'wgWBRepoSettings', $customRepoSettings );
		MediaWikiServices::getInstance()->resetServiceForTesting( 'SiteLookup' );

		$this->initTestEntities( [ 'StringProp', 'Berlin', 'Oslo' ] );
	}

	protected function tearDown() {
		parent::tearDown();
		$this->block->delete();
	}

	public function dataProvider() {
		yield [
			'wbcreateclaim',
			[
				'entity' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'snaktype' => 'value',
				'property' => [ [ EntityTestHelper::class, 'getId' ], 'StringProp' ],
				'value' => '"abc"',
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbcreateredirect',
			[
				'to' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'from' => 'Q123456',
			],
			[ 'wikibase-api-permissiondenied' ],
		];

		yield [
			'wbeditentity',
			[
				'new' => 'item',
				'data' => json_encode( [] ),
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbeditentity',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'data' => json_encode( [] ),
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wblinktitles',
			[
				'fromsite' => 'enwiki',
				'fromtitle' => 'Hydrogen',
				'tosite' => 'dewiki',
				'totitle' => 'Wasserstoff',
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbmergeitems',
			[
				'fromid' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'toid' => [ [ EntityTestHelper::class, 'getId' ], 'Oslo' ],
			],
			[ 'wikibase-itemmerge-permissiondenied' ],
		];

		yield [
			'wbremoveclaims',
			[
				'claim' => [ [ 'self', 'getEntityClaimGUID' ], 'Berlin' ],
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbremovequalifiers',
			[
				'claim' => [ [ 'self', 'getEntityClaimGUID' ], 'Berlin' ],
				'qualifiers' => [ [ 'self', 'getEntityClaimQualifierHash' ], 'Berlin' ],
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbremovereferences',
			[
				'statement' => [ [ 'self', 'getEntityClaimGUID' ], 'Berlin' ],
				'references' => [ [ 'self', 'getEntityClaimReferenceHash' ], 'Berlin' ],
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbsetaliases',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'add' => 'foo',
				'language' => 'en',
			],
			[ 'wikibase-api-permissiondenied', 'blockedtext' ],
		];

		yield [
			'wbsetaliases',
			[
				'new' => 'item',
				'add' => 'en alias',
				'language' => 'en'
			],
			[ 'wikibase-api-permissiondenied', 'blockedtext' ],
		];

		yield [
			'wbsetclaim',
			[
				'claim' => [ [ 'self' , 'buildTestClaimJSON' ], 'Oslo' ],
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbsetclaimvalue',
			[
				'claim' => [ [ 'self', 'getEntityClaimGUID' ], 'Berlin' ],
				'value' => '"foobar"',
				'snaktype' => 'value'
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbsetdescription',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'language' => 'de',
				'value' => 'FizzBuzz'
			],
			[ 'wikibase-api-permissiondenied', 'blockedtext' ],
		];

		yield [
			'wbsetlabel',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'language' => 'de',
				'value' => 'Bärlin',
			],
			[ 'wikibase-api-permissiondenied', 'blockedtext' ],
		];

		yield [
			'wbsetqualifier',
			[
				'claim' => [ [ 'self', 'getEntityClaimGUID' ], 'Berlin' ],
				'property' => [ [ EntityTestHelper::class, 'getId' ], 'StringProp' ],
				'snaktype' => 'value',
				'value' => '"baz"',
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbsetreference',
			[
				'statement' => [ [ 'self', 'getEntityClaimGUID' ], 'Berlin' ],
				'snaks' => [ [ 'self' , 'buildTestReferenceSnakJSON' ] ],
			],
			[ 'wikibase-api-failed-save', 'blockedtext', 'no-permission' ],
		];

		yield [
			'wbsetsitelink',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'linksite' => 'enwiki',
				'linktitle' => 'Berlin',
			],
			[ 'wikibase-api-permissiondenied', 'blockedtext' ],
		];
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @param $apiKey
	 * @param $otherApiData
	 */
	public function testBlock( $apiKey, $otherApiData, $expectedMessages ) {
		$testuser = self::getTestUser()->getUser();

		$this->assertTrue( $testuser->isBlocked(), 'User is expected to be blocked' );

		foreach ( $otherApiData as $key => &$value ) {
			if ( !is_array( $value ) ) {
				continue;
			}
			$value = call_user_func( ...$value );
		}
		unset( $value );

		try {
			$this->doApiRequestWithToken(
				array_merge(
					[ 'action' => $apiKey ],
					$otherApiData
				), null, $testuser );
			$this->fail( 'Expected api error to be raised' );
		} catch ( ApiUsageException $exception ) {
			foreach ( $expectedMessages as $message ) {
				$this->assertTrue( $exception->getStatusValue()->hasMessage( $message ),
					'Expected message ' . $message );
			}
		}
	}

	private function getEntityClaim( $handle ) {
		$testEntity = $this->loadEntity( EntityTestHelper::getId( $handle ) );
		if ( empty( $testEntity[ 'claims' ] ) ) {
			throw new \InvalidArgumentException( "Associated entity '$handle' has no claims!" );
		}
		return reset( $testEntity[ 'claims' ] )[ 0 ];
	}

	public function getEntityClaimGUID( $handle ) {
		return $this->getEntityClaim( $handle )[ 'id' ];
	}

	public function getEntityClaimQualifierHash( $handle ) {
		$testClaim = $this->getEntityClaim( $handle );
		if ( empty( $testClaim[ 'qualifiers' ] ) ) {
			throw new \InvalidArgumentException( "Associated entity '$handle' first claim has no qualifier!" );
		}
		return reset( $testClaim[ 'qualifiers' ] )[ 0 ][ 'hash' ];
	}

	public function getEntityClaimReferenceHash( $handle ) {
		$testClaim = $this->getEntityClaim( $handle );
		if ( empty( $testClaim[ 'references' ] ) ) {
			throw new \InvalidArgumentException( "Associated entity '$handle' first claim has no references!" );
		}
		return $testClaim[ 'references' ][ 0 ][ 'hash' ];
	}

	public function buildTestClaimJSON( $handle ) {
		$itemId = EntityTestHelper::getId( $handle );
		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( new ItemId( $itemId ) );

		$claim = [
			'id' => $guid,
			'type' => 'claim',
			'mainsnak' => [
				'snaktype' => 'value',
				'property' => EntityTestHelper::getId( 'StringProp' ),
				'datavalue' => [
					'value' => 'City',
					'type' => 'string'
				],
			],
		];
		return json_encode( $claim );
	}

	public function buildTestReferenceSnakJSON() {
		$stringProperty = EntityTestHelper::getId( 'StringProp' );
		$referneceSnak = [
			$stringProperty => [
				[
					'snaktype' => 'value',
					'property' => $stringProperty,
					'datavalue' => [
						'type' => 'string',
						'value' => 'foo',
					],
				],
			],
		];

		return json_encode( $referneceSnak );
	}

}
