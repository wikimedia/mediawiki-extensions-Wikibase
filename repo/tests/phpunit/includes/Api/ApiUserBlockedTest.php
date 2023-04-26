<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use ApiUsageException;
use MediaWiki\Block\DatabaseBlock;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;

/**
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group medium
 * @group Database
 * @coversNothing
 */
class ApiUserBlockedTest extends WikibaseApiTestCase {

	/** @var DatabaseBlock */
	private $block;

	protected function setUp(): void {
		parent::setUp();

		$testuser = self::getTestUser()->getUser();
		$this->block = new DatabaseBlock( [
			'address' => $testuser,
			'reason' => 'testing in ' . __CLASS__,
			'by' => $testuser,
		] );
		$this->block->insert();
		$this->initTestEntities( [ 'StringProp', 'Berlin', 'Oslo' ] );
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->block->delete();
	}

	public function blockCases() {
		yield [
			'wbcreateclaim',
			[
				'entity' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'snaktype' => 'value',
				'property' => [ [ EntityTestHelper::class, 'getId' ], 'StringProp' ],
				'value' => '"abc"',
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbcreateredirect',
			[
				'to' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'from' => 'Q123456',
			],
			[ 'permissiondenied' ],
		];

		yield [
			'wbeditentity',
			[
				'new' => 'item',
				'data' => json_encode( [] ),
			],
			[ 'blocked' ],
		];

		yield [
			'wbeditentity',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'data' => json_encode( [] ),
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wblinktitles',
			[
				'fromsite' => 'enwiki',
				'fromtitle' => 'Hydrogen',
				'tosite' => 'dewiki',
				'totitle' => 'Wasserstoff',
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbmergeitems',
			[
				'fromid' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'toid' => [ [ EntityTestHelper::class, 'getId' ], 'Oslo' ],
			],
			[ 'permissiondenied' ],
		];

		yield [
			'wbremoveclaims',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbremovequalifiers',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'qualifiers' => [ [ self::class, 'getEntityClaimQualifierHash' ], 'Berlin' ],
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbremovereferences',
			[
				'statement' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'references' => [ [ self::class, 'getEntityClaimReferenceHash' ], 'Berlin' ],
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbsetaliases',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'add' => 'foo',
				'language' => 'en',
			],
			[ 'blocked' ],
		];

		yield [
			'wbsetaliases',
			[
				'new' => 'item',
				'add' => 'en alias',
				'language' => 'en',
			],
			[ 'blocked' ],
		];

		yield [
			'wbsetclaim',
			[
				'claim' => [ [ self::class , 'buildTestClaimJSON' ], 'Oslo' ],
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbsetclaimvalue',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'value' => '"foobar"',
				'snaktype' => 'value',
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbsetdescription',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'language' => 'de',
				'value' => 'FizzBuzz',
			],
			[ 'blocked' ],
		];

		yield [
			'wbsetlabel',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'language' => 'de',
				'value' => 'Bärlin',
			],
			[ 'blocked' ],
		];

		yield [
			'wbsetqualifier',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'property' => [ [ EntityTestHelper::class, 'getId' ], 'StringProp' ],
				'snaktype' => 'value',
				'value' => '"baz"',
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbsetreference',
			[
				'statement' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'snaks' => [ [ self::class , 'buildTestReferenceSnakJSON' ] ],
			],
			[ 'failed-save', 'blocked' ],
		];

		yield [
			'wbsetsitelink',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'linksite' => 'enwiki',
				'linktitle' => 'Berlin',
			],
			[ 'blocked' ],
		];
	}

	public function testBlock() {
		$testuser = self::getTestUser()->getUser();

		$this->assertNotNull( $testuser->getBlock(), 'User is expected to be blocked' );

		foreach ( $this->blockCases() as $case ) {
			list( $apiKey, $otherApiData, $expectedErrors ) = $case;

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
				$this->fail( "$apiKey: Expected API error to be raised" );
			} catch ( ApiUsageException $exception ) {
				foreach ( $expectedErrors as $code ) {
					$this->assertTrue( ApiTestCase::apiExceptionHasCode( $exception, $code ),
						"$apiKey: Expected error code $code" );
				}
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
					'type' => 'string',
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
