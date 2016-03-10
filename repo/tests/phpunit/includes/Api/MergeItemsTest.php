<?php

namespace Wikibase\Test\Repo\Api;

use HashSiteStore;
use Language;
use RequestContext;
use Status;
use TestSites;
use Title;
use UsageException;
use User;
use Wikibase\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\MergeItems;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\EntityModificationTestHelper;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Repo\Api\MergeItems
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group MergeItemsTest
 * @group Database
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Lucie-Aimée Kaffee
 */
class MergeItemsTest extends \MediaWikiTestCase {

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

	protected function setUp() {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();
		$this->apiModuleTestHelper = new ApiModuleTestHelper();

		$this->mockRepository = $this->entityModificationTestHelper->getMockRepository();

		$this->entityModificationTestHelper->putEntities( array(
			'Q1' => array(),
			'Q2' => array(),
			'P1' => array( 'datatype' => 'string' ),
			'P2' => array( 'datatype' => 'string' ),
		) );

		$this->entityModificationTestHelper->putRedirects( array(
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		) );
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission ) {
				if ( $user->getName() === 'UserWithoutPermission' && $permission === 'edit' ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	/**
	 * @param EntityRedirect|null $redirect
	 *
	 * @return RedirectCreationInteractor
	 */
	public function getMockRedirectCreationInteractor( EntityRedirect $redirect = null ) {
		$mock = $this->getMockBuilder( RedirectCreationInteractor::class )
			->disableOriginalConstructor()
			->getMock();

		if ( $redirect ) {
			$mock->expects( $this->once() )
				->method( 'createRedirect' )
				->with( $redirect->getEntityId(), $redirect->getTargetId() )
				->will( $this->returnCallback( function() use ( $redirect ) {
					return $redirect;
				} ) );
		} else {
			$mock->expects( $this->never() )
				->method( 'createRedirect' );
		}

		return $mock;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return Title::newFromText( $entityId->getSerialization() );
			} ) );

		return $entityTitleLookup;
	}

	/**
	 * @param MergeItems $module
	 * @param EntityRedirect|null $expectedRedirect
	 */
	private function overrideServices( MergeItems $module, EntityRedirect $expectedRedirect = null ) {
		$idParser = new BasicEntityIdParser();

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$errorReporter = new ApiErrorReporter(
			$module,
			$wikibaseRepo->getExceptionLocalizer(),
			Language::factory( 'en' )
		);

		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( new RequestContext() );

		$resultBuilder = $apiHelperFactory->getResultBuilder( $module );

		$changeOpsFactoryProvider = new ChangeOpFactoryProvider(
			$this->getConstraintProvider(),
			new GuidGenerator(),
			$wikibaseRepo->getStatementGuidValidator(),
			$wikibaseRepo->getStatementGuidParser(),
			$this->getSnakValidator(),
			$this->getTermValidatorFactory(),
			new HashSiteStore( TestSites::getSites() )
		);

		$module->setServices(
			$idParser,
			$errorReporter,
			$resultBuilder,
			new ItemMergeInteractor(
				$changeOpsFactoryProvider->getMergeChangeOpFactory(),
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$wikibaseRepo->getSummaryFormatter(),
				$module->getUser(),
				$this->getMockRedirectCreationInteractor( $expectedRedirect ),
				$this->getEntityTitleLookup()
			)
		);
	}

	/**
	 * @return EntityConstraintProvider
	 */
	private function getConstraintProvider() {
		$constraintProvider = $this->getMockBuilder( EntityConstraintProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$constraintProvider->expects( $this->any() )
			->method( 'getUpdateValidators' )
			->will( $this->returnValue( array() ) );

		return $constraintProvider;
	}

	/**
	 * @return SnakValidator
	 */
	private function getSnakValidator() {
		$snakValidator = $this->getMockBuilder( SnakValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$snakValidator->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnValue( Status::newGood() ) );

		return $snakValidator;
	}

	/**
	 * @return TermValidatorFactory
	 */
	private function getTermValidatorFactory() {
		$dupeDetector = $this->getMockBuilder( LabelDescriptionDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		$dupeDetector->expects( $this->any() )
			->method( 'detectTermConflicts' )
			->will( $this->returnValue( Status::newGood() ) );

		return new TermValidatorFactory(
			100,
			array( 'en', 'de', 'fr' ),
			new BasicEntityIdParser(),
			$dupeDetector
		);
	}

	private function callApiModule( $params, EntityRedirect $expectedRedirect = null ) {
		$module = $this->apiModuleTestHelper->newApiModule( 'Wikibase\Repo\Api\MergeItems', 'wbmergeitems', $params );
		$this->overrideServices( $module, $expectedRedirect );

		$module->execute();

		$data = $module->getResult()->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
		return $data;
	}

	public function provideData() {
		$testCases = array();
		$testCases['labelMerge'] = array(
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			array(),
			array(),
			array( 'labels' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
			true,
		);
		$testCases['ignoreConflictSitelinksMerge'] = array(
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ) ) ),
			array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainFrom' ) ) ),
			array( 'sitelinks' => array(
				'dewiki' => array( 'site' => 'dewiki', 'title' => 'RemainTo' ),
				'enwiki' => array( 'site' => 'enwiki', 'title' => 'PlFrom' ),
			) ),
			false,
			'sitelink',
		);
		$testCases['statementMerge'] = array(
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal', 'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ) ) ) ),
			array(),
			array(),
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array( 'value' => 'imastring', 'type' => 'string' ) ),
				'type' => 'statement', 'rank' => 'normal' ) ) ) ),
			true,
		);
		$testCases['ignoreConflictStatementMerge'] = array(
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array(
					'value' => array( 'entity-type' => 'item', 'numeric-id' => 2 ), 'type' => 'wikibase-entityid' )
				),
				'type' => 'statement', 'rank' => 'normal', 'id' => 'deadbeefdeadbeefdeadbeefdeadbeef' ) ) ) ),
			array(),
			array(),
			array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
				'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array(
					'value' => array( 'entity-type' => 'item', 'numeric-id' => 2 ), 'type' => 'wikibase-entityid' )
				),
				'type' => 'statement', 'rank' => 'normal' ) ) )
			),
			true,
			'statement',
		);

		return $testCases;
	}

	/**
	 * @dataProvider provideData
	 */
	public function testMergeRequest( $pre1, $pre2, $expectedFrom, $expectedTo, $expectRedirect, $ignoreConflicts = null ) {
		// -- set up params ---------------------------------
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'summary' => 'CustomSummary!',
		);
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
	}

	private function assertResultCorrect( array $result ) {
		$this->apiModuleTestHelper->assertResultSuccess( $result );

		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'from', 'id' ), $result );
		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'to', 'id' ), $result );
		$this->assertEquals( 'Q1', $result['from']['id'] );
		$this->assertEquals( 'Q2', $result['to']['id'] );

		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'from', 'lastrevid' ), $result );
		$this->apiModuleTestHelper->assertResultHasKeyInPath( array( 'to', 'lastrevid' ), $result );
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
			$this->assertEquals( 1, $result['redirected'] );
		} else {
			$this->assertEquals( 0, $result['redirected'] );
		}
	}

	private function assertEditSummariesCorrect( array $result ) {
		$this->entityModificationTestHelper->assertRevisionSummary( array( 'wbmergeitems' ), $result['from']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( '/CustomSummary/', $result['from']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( array( 'wbmergeitems' ), $result['to']['lastrevid'] );
		$this->entityModificationTestHelper->assertRevisionSummary( '/CustomSummary/', $result['to']['lastrevid'] );
	}

	public function provideExceptionParamsData() {
		return array(
			array( //0 no ids given
				'p' => array(),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'param-missing'
				) )
			),
			array( //1 only from id
				'p' => array( 'fromid' => 'Q1' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'param-missing'
				) )
			),
			array( //2 only to id
				'p' => array( 'toid' => 'Q1' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'param-missing'
				) )
			),
			array( //3 toid bad
				'p' => array( 'fromid' => 'Q1', 'toid' => 'ABCDE' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'invalid-entity-id'
				) )
			),
			array( //4 fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'invalid-entity-id'
				) )
			),
			array( //5 both same id
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'invalid-entity-id',
					'message' => 'You must provide unique ids'
				) )
			),
			array( //6 from id is property
				'p' => array( 'fromid' => 'P1', 'toid' => 'Q1' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'not-item'
				) )
			),
			array( //7 to id is property
				'p' => array( 'fromid' => 'Q1', 'toid' => 'P1' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'not-item'
				) )
			),
			array( //8 bad ignoreconficts
				'p' => array( 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'foo' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'invalid-entity-id'
				) )
			),
			array( //9 bad ignoreconficts
				'p' => array( 'fromid' => 'Q2', 'toid' => 'Q2', 'ignoreconflicts' => 'label|foo' ),
				'e' => array( 'exception' => array(
					'type' => 'UsageException',
					'code' => 'invalid-entity-id'
				) )
			),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( $params, $expected ) {
		// -- set any defaults ------------------------------------
		$params['action'] = 'wbmergeitems';

		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( $expected, $ex );
		}
	}

	public function provideExceptionConflictsData() {
		return array(
			array(
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo' ) ) ),
				array( 'descriptions' => array( 'en' => array( 'language' => 'en', 'value' => 'foo2' ) ) ),
			),
			array(
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo' ) ) ),
				array( 'sitelinks' => array( 'dewiki' => array( 'site' => 'dewiki', 'title' => 'Foo2' ) ) ),
			),
			array(
				array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array(
						'value' => array( 'entity-type' => 'item', 'numeric-id' => 2 ), 'type' => 'wikibase-entityid' )
					),
					'type' => 'statement', 'rank' => 'normal' ) ) )
				),
				array(),
			),
			array(
				array(),
				array( 'claims' => array( 'P1' => array( array( 'mainsnak' => array(
					'snaktype' => 'value', 'property' => 'P1', 'datavalue' => array(
						'value' => array( 'entity-type' => 'item', 'numeric-id' => 1 ), 'type' => 'wikibase-entityid' )
					),
					'type' => 'statement', 'rank' => 'normal' ) ) )
				),
			)
		);
	}

	/**
	 * @dataProvider provideExceptionConflictsData
	 */
	public function testMergeItemsConflictsExceptions( $pre1, $pre2 ) {
		$expected = array( 'exception' => array( 'type' => 'UsageException', 'code' => 'failed-save' ) );

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q1',
			'toid' => 'Q2',
		);

		// -- do the request --------------------------------------------
		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( $expected, $ex );
		}
	}

	public function testMergeNonExistingItem() {
		$params = array(
			'action' => 'wbmergeitems',
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978'
		);

		try {
			$this->callApiModule( $params );
			$this->fail( 'Expected UsageException!' );
		} catch ( UsageException $ex ) {
			$this->apiModuleTestHelper->assertUsageException( 'no-such-entity', $ex );
		}
	}

}
