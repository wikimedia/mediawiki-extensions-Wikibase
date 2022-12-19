<?php

namespace Wikibase\Repo\Tests\Api;

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
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
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
			[ 'wikibase-api-permissiondenied', 'apierror-blocked' ],
		];

		yield [
			'wbeditentity',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'data' => json_encode( [] ),
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wblinktitles',
			[
				'fromsite' => 'enwiki',
				'fromtitle' => 'Hydrogen',
				'tosite' => 'dewiki',
				'totitle' => 'Wasserstoff',
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
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
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbremovequalifiers',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'qualifiers' => [ [ self::class, 'getEntityClaimQualifierHash' ], 'Berlin' ],
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbremovereferences',
			[
				'statement' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'references' => [ [ self::class, 'getEntityClaimReferenceHash' ], 'Berlin' ],
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbsetaliases',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'add' => 'foo',
				'language' => 'en',
			],
			[ 'wikibase-api-permissiondenied', 'apierror-blocked' ],
		];

		yield [
			'wbsetaliases',
			[
				'new' => 'item',
				'add' => 'en alias',
				'language' => 'en',
			],
			[ 'wikibase-api-permissiondenied', 'apierror-blocked' ],
		];

		yield [
			'wbsetclaim',
			[
				'claim' => [ [ self::class , 'buildTestClaimJSON' ], 'Oslo' ],
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbsetclaimvalue',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'value' => '"foobar"',
				'snaktype' => 'value',
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbsetdescription',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'language' => 'de',
				'value' => 'FizzBuzz',
			],
			[ 'wikibase-api-permissiondenied', 'apierror-blocked' ],
		];

		yield [
			'wbsetlabel',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'language' => 'de',
				'value' => 'Bärlin',
			],
			[ 'wikibase-api-permissiondenied', 'apierror-blocked' ],
		];

		yield [
			'wbsetqualifier',
			[
				'claim' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'property' => [ [ EntityTestHelper::class, 'getId' ], 'StringProp' ],
				'snaktype' => 'value',
				'value' => '"baz"',
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbsetreference',
			[
				'statement' => [ [ self::class, 'getEntityClaimGUID' ], 'Berlin' ],
				'snaks' => [ [ self::class , 'buildTestReferenceSnakJSON' ] ],
			],
			[ 'wikibase-api-failed-save', 'apierror-blocked', 'permissionserrors' ],
		];

		yield [
			'wbsetsitelink',
			[
				'id' => [ [ EntityTestHelper::class, 'getId' ], 'Berlin' ],
				'linksite' => 'enwiki',
				'linktitle' => 'Berlin',
			],
			[ 'wikibase-api-permissiondenied', 'apierror-blocked' ],
		];
	}

	public function testBlock() {
		$testuser = self::getTestUser()->getUser();

		$this->assertNotNull( $testuser->getBlock(), 'User is expected to be blocked' );

		foreach ( $this->blockCases() as $case ) {
			list( $apiKey, $otherApiData, $expectedMessages ) = $case;

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
