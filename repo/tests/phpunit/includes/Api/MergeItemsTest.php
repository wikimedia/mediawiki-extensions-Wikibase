<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use ApiUsageException;
use ChangeTags;
use FauxRequest;
use HashSiteStore;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use SiteLookup;
use Status;
use TestSites;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Serializers\ItemSerializer;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\MergeItems;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\MergeItems
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Lucie-Aimée Kaffee
 */
class MergeItemsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	/**
	 * @var EntityModificationTestHelper|null
	 */
	private $entityModificationTestHelper = null;

	/**
	 * @var ApiModuleTestHelper|null
	 */
	private $apiModuleTestHelper = null;

	protected function setUp(): void {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();
		$this->apiModuleTestHelper = new ApiModuleTestHelper();

		$this->mockRepository = $this->entityModificationTestHelper->getMockRepository();

		$this->entityModificationTestHelper->putEntities( [
			'Q1' => [],
			'Q2' => [],
			'P1' => [ 'datatype' => 'string' ],
			'P2' => [ 'datatype' => 'string' ],
		] );

		$this->entityModificationTestHelper->putRedirects( [
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		] );
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getPermissionCheckers() {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturnCallback( function( User $user, $permission ) {
				if ( $user->getName() === 'UserWithoutPermission' && $permission === 'edit' ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} );

		return $permissionChecker;
	}

	/**
	 * @param EntityRedirect|null $redirect
	 *
	 * @return ItemRedirectCreationInteractor
	 */
	public function getMockRedirectCreationInteractor( EntityRedirect $redirect = null ) {
		$mock = $this->createMock( ItemRedirectCreationInteractor::class );

		if ( $redirect ) {
			$mock->expects( $this->once() )
				->method( 'createRedirect' )
				->with( $redirect->getEntityId(), $redirect->getTargetId() )
				->willReturnCallback( function() use ( $redirect ) {
					return $redirect;
				} );
		} else {
			$mock->expects( $this->never() )
				->method( 'createRedirect' );
		}

		return $mock;
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	private function getEntityTitleStoreLookup() {
		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $entityId ) {
				return Title::newFromTextThrow( $entityId->getSerialization() );
			} );

		return $entityTitleStoreLookup;
	}

	/**
	 * @param string[] $params
	 * @param EntityRedirect|null $expectedRedirect
	 *
	 * @return MergeItems
	 */
	private function newMergeItemsApiModule( array $params, EntityRedirect $expectedRedirect = null ) {
		if ( !isset( $params['token'] ) ) {
			$params['token'] = $this->getTestUser()->getUser()->getToken();
		}

		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );

		$changeOpsFactoryProvider = new ChangeOpFactoryProvider(
			$this->getConstraintProvider(),
			new GuidGenerator(),
			WikibaseRepo::getStatementGuidValidator(),
			WikibaseRepo::getStatementGuidParser(),
			$this->getSnakValidator(),
			$this->getTermValidatorFactory(),
			new HashSiteStore( TestSites::getSites() ),
			WikibaseRepo::getSnakNormalizer(),
			WikibaseRepo::getReferenceNormalizer(),
			WikibaseRepo::getStatementNormalizer(),
			[],
			true
		);

		$apiResultBuilder = new ResultBuilder(
			$main->getResult(),
			$this->getEntityTitleStoreLookup(),
			$this->createMock( SerializerFactory::class ),
			$this->createMock( ItemSerializer::class ),
			$this->createMock( SiteLookup::class ),
			new InMemoryDataTypeLookup(),
			WikibaseRepo::getEntityIdParser()
		);
		$errorReporter = new ApiErrorReporter(
			$main,
			WikibaseRepo::getExceptionLocalizer(),
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		);
		return new MergeItems(
			$main,
			'wbmergeitems',
			new ItemMergeInteractor(
				$changeOpsFactoryProvider->getMergeFactory(),
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				WikibaseRepo::getSummaryFormatter(),
				$this->getMockRedirectCreationInteractor( $expectedRedirect ),
				$this->getEntityTitleStoreLookup(),
				MediaWikiServices::getInstance()->getPermissionManager()
			),
			$errorReporter,
			function ( $module ) use ( $apiResultBuilder ) {
				return $apiResultBuilder;
			},
			[ 'mainItem' => 'Q100', 'auxItem' => 'Q200' ]
		);
	}

	/**
	 * @return EntityConstraintProvider
	 */
	private function getConstraintProvider() {
		$constraintProvider = $this->createMock( EntityConstraintProvider::class );

		$constraintProvider->method( 'getUpdateValidators' )
			->willReturn( [] );

		return $constraintProvider;
	}

	/**
	 * @return SnakValidator
	 */
	private function getSnakValidator() {
		$snakValidator = $this->createMock( SnakValidator::class );

		$snakValidator->method( 'validate' )
			->willReturn( Status::newGood() );

		return $snakValidator;
	}

	/**
	 * @return TermValidatorFactory
	 */
	private function getTermValidatorFactory() {
		return new TermValidatorFactory(
			100,
			[ 'en', 'de', 'fr' ],
			new ItemIdParser(),
			$this->createMock( TermsCollisionDetectorFactory::class ),
			$this->createMock( TermLookup::class ),
			$this->createMock( LanguageNameUtils::class )
		);
	}

	private function callApiModule( $params, EntityRedirect $expectedRedirect = null ) {
		$module = $this->newMergeItemsApiModule( $params, $expectedRedirect );

		$module->execute();

		$data = $module->getResult()->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
		return $data;
	}

	public function provideData() {
		$testCases = [];
		$testCases['labelMerge'] = [
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			[],
			[],
			[ 'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
			true,
		];
		$testCases['ignoreConflictSitelinksMerge'] = [
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainFrom' ],
				'enwiki' => [ 'site' => 'enwiki', 'title' => 'PlFrom' ],
			] ],
			[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainTo' ] ] ],
			[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainFrom' ] ] ],
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainTo' ],
				'enwiki' => [ 'site' => 'enwiki', 'title' => 'PlFrom' ],
			] ],
			false,
			'sitelink',
		];
		$testCases['statementMerge'] = [
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [ 'value' => 'imastring', 'type' => 'string' ] ],
				'type' => 'statement', 'rank' => 'normal', 'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ] ] ] ],
			[],
			[],
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [ 'value' => 'imastring', 'type' => 'string' ] ],
				'type' => 'statement', 'rank' => 'normal' ] ] ] ],
			true,
		];
		$testCases['ignoreConflictStatementMerge'] = [
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [
					'value' => [ 'entity-type' => 'item', 'numeric-id' => 2 ], 'type' => 'wikibase-entityid' ],
				],
				'type' => 'statement', 'rank' => 'normal', 'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ] ] ] ],
			[],
			[],
			[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [
					'value' => [ 'entity-type' => 'item', 'numeric-id' => 2 ], 'type' => 'wikibase-entityid' ],
				],
				'type' => 'statement', 'rank' => 'normal' ] ] ],
			],
			true,
			'statement',
		];

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testMergeRequest( $pre1, $pre2, $expectedFrom, $expectedTo, $expectRedirect, $ignoreConflicts = null ) {
		// -- set up params ---------------------------------
		$tag = __METHOD__ . '-tag';
		ChangeTags::defineTag( $tag );
		$params = [
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'summary' => 'CustomSummary!',
			'tags' => $tag,
		];
		if ( $ignoreConflicts !== null ) {
			$params['ignoreconflicts'] = $ignoreConflicts;
		}

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		// -- do the request --------------------------------------------
		$redirect = $expectRedirect
			? new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q2' ) )
			: null;
		$result = $this->callApiModule( $params, $redirect );

		// -- check the result --------------------------------------------
		$this->assertResultCorrect( $result );

		// -- check the items --------------------------------------------
		$this->assertItemsCorrect( $result, $expectedFrom, $expectedTo );

		// -- check redirect --------------------------------------------
		$this->assertRedirectCorrect( $result, $redirect );

		// -- check the edit summaries --------------------------------------------
		$this->assertEditSummariesCorrect( $result );

		$this->assertEditsAreTagged( $result, $tag );
	}

	private function assertResultCorrect( array $result ) {
		$this->apiModuleTestHelper->assertResultSuccess( $result );

		$this->apiModuleTestHelper->assertResultHasKeyInPath( [ 'from', 'id' ], $result );
		$this->apiModuleTestHelper->assertResultHasKeyInPath( [ 'to', 'id' ], $result );
		$this->assertEquals( 'Q1', $result['from']['id'] );
		$this->assertEquals( 'Q2', $result['to']['id'] );

		$this->apiModuleTestHelper->assertResultHasKeyInPath( [ 'from', 'lastrevid' ], $result );
		$this->apiModuleTestHelper->assertResultHasKeyInPath( [ 'to', 'lastrevid' ], $result );
		$this->assertGreaterThan( 0, $result['from']['lastrevid'] );
		$this->assertGreaterThan( 0, $result['to']['lastrevid'] );
	}

	private function assertItemsCorrect( array $result, array $expectedFrom, array $expectedTo ) {
		$actualFrom = $this->entityModificationTestHelper->getEntity( $result['from']['id'], true ); //resolve redirects
		$this->entityModificationTestHelper->assertEntityEquals( $expectedFrom, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( $result['to']['id'], true );
		$this->entityModificationTestHelper->assertEntityEquals( $expectedTo, $actualTo );
	}

	private function assertRedirectCorrect( array $result, EntityRedirect $redirect = null ) {
		$this->assertArrayHasKey( 'redirected', $result );

		if ( $redirect ) {
			$this->assertSame( 1, $result['redirected'] );
		} else {
			$this->assertSame( 0, $result['redirected'] );
		}
	}

	private function assertEditSummariesCorrect( array $result ) {
		$this->entityModificationTestHelper->assertRevisionSummary( [ 'wbmergeitems' ], $result['from']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( '/CustomSummary/', $result['from']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( [ 'wbmergeitems' ], $result['to']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( '/CustomSummary/', $result['to']['lastrevid'] );
	}

	private function assertEditsAreTagged( array $result, string $tag ) {
		$this->assertContains( $tag, $this->mockRepository->getLogEntry( $result['from']['lastrevid'] )['tags'] );
		$this->assertContains( $tag, $this->mockRepository->getLogEntry( $result['to']['lastrevid'] )['tags'] );
	}

	public function provideExceptionParamsData() {
		return [
			[ //0 no ids given
				'p' => [],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ],
			],
			[ //1 only from id
				'p' => [ 'fromid' => 'Q1' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ],
			],
			[ //2 only to id
				'p' => [ 'toid' => 'Q1' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ],
			],
			[ //3 toid bad
				'p' => [ 'fromid' => 'Q1', 'toid' => 'ABCDE' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ],
			],
			[ //4 fromid bad
				'p' => [ 'fromid' => 'ABCDE', 'toid' => 'Q1' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ],
			],
			[ //5 both same id
				'p' => [ 'fromid' => 'Q1', 'toid' => 'Q1' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
					'message' => 'You must provide unique ids',
				] ],
			],
			[ //6 from id is property
				'p' => [ 'fromid' => 'P1', 'toid' => 'Q1' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-item',
				] ],
			],
			[ //7 to id is property
				'p' => [ 'fromid' => 'Q1', 'toid' => 'P1' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-item',
				] ],
			],
			[ //8 bad ignoreconficts
				'p' => [ 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'foo' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ],
			],
			[ //9 bad ignoreconficts
				'p' => [ 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'label|foo' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ],
			],
		];
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';

		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected ApiUsageException!' );
		} catch ( ApiUsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( $expected, $ex );
		}
	}

	public function provideExceptionConflictsData() {
		return [
			[
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo2' ] ] ],
				[ 'Conflicting descriptions for language en' ],
			],
			[
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo' ] ] ],
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo2' ] ] ],
				[ 'Conflicting sitelinks for dewiki' ],
			],
			[
				[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
					'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [
						'value' => [ 'entity-type' => 'item', 'numeric-id' => 2 ], 'type' => 'wikibase-entityid' ],
					],
					'type' => 'statement', 'rank' => 'normal' ] ] ],
				],
				[],
				[ 'The two items cannot be merged because one of them links to the other using the properties: P1' ],
			],
			[
				[],
				[ 'claims' => [ 'P1' => [ [ 'mainsnak' => [
					'snaktype' => 'value', 'property' => 'P1', 'datavalue' => [
						'value' => [ 'entity-type' => 'item', 'numeric-id' => 1 ], 'type' => 'wikibase-entityid' ],
					],
					'type' => 'statement', 'rank' => 'normal' ] ] ],
				],
				[ 'The two items cannot be merged because one of them links to the other using the properties: P1' ],
			],
		];
	}

	/**
	 * @dataProvider provideExceptionConflictsData
	 */
	public function testMergeItemsConflictsExceptions( $pre1, $pre2, $extraData ) {
		$expected = [
			'exception' => [ 'type' => ApiUsageException::class, 'code' => 'failed-save' ],
			'extradata' => $extraData,
		];

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		$params = [
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];

		// -- do the request --------------------------------------------
		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected ApiUsageException!' );
		} catch ( ApiUsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( $expected, $ex );
		}
	}

	public function testMergeNonExistingItem() {
		$params = [
			'action' => 'wbmergeitems',
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978',
		];

		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected ApiUsageException!' );
		} catch ( ApiUsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( 'no-such-entity', $ex );
		}
	}

}
