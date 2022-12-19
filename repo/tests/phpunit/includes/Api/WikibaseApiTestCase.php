<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use ApiUsageException;
use ChangeTags;
use CommentStoreComment;
use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use OutOfBoundsException;
use PHPUnit\Framework\Constraint\Constraint;
use TestSites;
use TestUser;
use Title;
use User;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Normalization\DataValueNormalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @license GPL-2.0-or-later
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
abstract class WikibaseApiTestCase extends ApiTestCase {

	/** @var User */
	protected $user;

	protected function setUp(): void {
		parent::setUp();

		$this->setupUser();

		$this->setupSiteLinkGroups();

		$siteStore = new \HashSiteStore( TestSites::getSites() );
		$this->setService( 'SiteStore', $siteStore );
		$this->setService( 'SiteLookup', $siteStore );
	}

	protected function createTestUser() {
		return new TestUser(
			'Apitesteditor',
			'Api Test Editor',
			'api_test_editor@example.com',
			[ 'wbeditor' ]
		);
	}

	protected function getEntityStore() {
		return WikibaseRepo::getEntityStore();
	}

	private function setupUser() {
		self::$users['wbeditor'] = $this->createTestUser();

		$this->user = self::$users['wbeditor']->getUser();
		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [
			'property-create' => true,
			'createpage' => true,
			'bot' => true,
			'item-term' => true,
			'item-merge' => true,
			'item-redirect' => true,
			'property-term' => true,
			'read' => true,
			'edit' => true,
			'writeapi' => true,
		] ] );
	}

	private function setupSiteLinkGroups() {
		global $wgWBRepoSettings;

		$customRepoSettings = $wgWBRepoSettings;
		$customRepoSettings['siteLinkGroups'] = [ 'wikipedia' ];
		$this->setMwGlobals( 'wgWBRepoSettings', $customRepoSettings );
		MediaWikiServices::getInstance()->resetServiceForTesting( 'SiteLookup' );
	}

	/**
	 * @param string[] $handles
	 * @param string[] $idMap
	 */
	protected function initTestEntities( array $handles, array $idMap = [] ) {
		$activeHandles = EntityTestHelper::getActiveHandles();
		$user = $this->getTestSysop()->getUser();

		foreach ( $activeHandles as $handle => $id ) {
			$title = $this->getTestEntityTitle( $handle );

			$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
			$page->doDeleteArticleReal( 'Test reset', $user );
			EntityTestHelper::unRegisterEntity( $handle );
		}

		foreach ( $handles as $handle ) {
			$params = EntityTestHelper::getEntity( $handle );
			$params['action'] = 'wbeditentity';

			EntityTestHelper::injectIds( $params, $idMap );
			EntityTestHelper::injectIds( $params, EntityTestHelper::$defaultPlaceholderValues );

			list( $res, , ) = $this->doApiRequestWithToken( $params );
			EntityTestHelper::registerEntity( $handle, $res['entity']['id'], $res['entity'] );

			$idMap["%$handle%"] = $res['entity']['id'];
		}
	}

	/**
	 * @param string $handle
	 *
	 * @return null|Title
	 */
	protected function getTestEntityTitle( $handle ) {
		try {
			$idString = EntityTestHelper::getId( $handle );
			$id = WikibaseRepo::getEntityIdParser()->parse( $idString );
			$title = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $id );
		} catch ( OutOfBoundsException $ex ) {
			$title = null;
		}

		return $title;
	}

	/**
	 * Loads an entity from the database (via an API call).
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	protected function loadEntity( $id ) {
		list( $res, , ) = $this->doApiRequest(
			[
				'action' => 'wbgetentities',
				'format' => 'json', // make sure IDs are used as keys.
				'ids' => $id ]
		);

		return $res['entities'][$id];
	}

	/**
	 * Set up a datatype that normalizes strings to uppercase,
	 * create a property for it, and return its ID.
	 */
	protected function createUppercaseStringTestProperty(): PropertyId {
		$this->setTemporaryHook( 'WikibaseRepoDataTypes', function ( array &$dataTypes ) {
			$dataTypes['PT:string-uppercase'] = [
				'value-type' => 'string',
				'normalizer-factory-callback' => function (): DataValueNormalizer {
					$normalizer = $this->createMock( DataValueNormalizer::class );
					$normalizer->method( 'normalize' )
						->willReturnCallback( function ( StringValue $value ): StringValue {
							return new StringValue( strtoupper( $value->getValue() ) );
						} );
					return $normalizer;
				},
			];
		} );
		// note: when removing tmpNormalizeDataValues, call $this->resetServices() instead
		$settings = clone WikibaseRepo::getSettings( $this->getServiceContainer() );
		$settings->setSetting( 'tmpNormalizeDataValues', true );
		$this->setService( 'WikibaseRepo.Settings', $settings );

		return $this->getEntityStore()
			->saveEntity( Property::newFromType( 'string-uppercase' ), '', $this->user, EDIT_NEW )
			->getEntity()
			->getId();
	}

	/**
	 * Do the test for exceptions from Api queries.
	 *
	 * @param array $params Array of params for the API query.
	 * @param array $exception Details of the exception to expect (type, code, message, message-key).
	 * @param Authority|null $user
	 * @param bool $token Whether to include a CSRF token
	 */
	protected function doTestQueryExceptions(
		array $params,
		array $exception,
		Authority $user = null,
		$token = true
	) {
		try {
			if ( $token ) {
				$this->doApiRequestWithToken( $params, null, $user );
			} else {
				$this->doApiRequest( $params, null, false, $user );
			}

			$this->fail( 'Failed to throw ApiUsageException' );
		} catch ( ApiUsageException $e ) {
			if ( array_key_exists( 'type', $exception ) ) {
				$this->assertInstanceOf( $exception['type'], $e );
			}

			if ( array_key_exists( 'code', $exception ) ) {
				$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
				$this->assertThat(
					$msg->getApiCode(),
					$exception['code'] instanceof Constraint
						? $exception['code']
						: $this->equalTo( $exception['code'] )
				);
			}

			if ( array_key_exists( 'message', $exception ) ) {
				$this->assertStringContainsString( $exception['message'], $e->getMessage() );
			}

			if ( array_key_exists( 'message-key', $exception ) ) {
				$status = $e->getStatusValue();
				$this->assertTrue(
					$status->hasMessage( $exception['message-key'] ),
					'Status message key'
				);
			}
		}
	}

	/**
	 * Utility function for converting an array from "deep" (indexed) to "flat" (keyed) structure.
	 * Arrays that already use a flat structure are left unchanged.
	 *
	 * Arrays with a deep structure are expected to be list of entries that are associative arrays,
	 * where which entry has at least the fields given by $keyField and $valueField.
	 *
	 * Arrays with a flat structure are associative and assign values to meaningful keys.
	 *
	 * @param array $data the input array.
	 * @param string $keyField The name of the field in each entry that shall be used as the key in
	 *  the flat structure.
	 * @param string $valueField The name of the field in each entry that shall be used as the value
	 *  in the flat structure.
	 * @param bool $multiValue Whether the value in the flat structure shall be an indexed array of
	 *  values instead of a single value.
	 * @param array &$into optional aggregator.
	 *
	 * @return array The flat version of $data.
	 */
	protected function flattenArray( array $data, $keyField, $valueField, $multiValue = false, array &$into = [] ) {
		foreach ( $data as $index => $value ) {
			if ( is_array( $value ) ) {
				if ( isset( $value[$keyField] ) && isset( $value[$valueField] ) ) {
					// found "deep" entry in the array
					$k = $value[ $keyField ];
					$v = $value[ $valueField ];
				} elseif ( isset( $value[0] ) && !is_array( $value[0] ) && $multiValue ) {
					// found "flat" multi-value entry in the array
					$k = $index;
					$v = $value;
				} else {
					// found list, recurse
					$this->flattenArray( $value, $keyField, $valueField, $multiValue, $into );
					continue;
				}
			} else {
				// found "flat" entry in the array
				$k = $index;
				$v = $value;
			}

			if ( $multiValue ) {
				if ( is_array( $v ) ) {
					$into[$k] = empty( $into[$k] ) ? $v : array_merge( $into[$k], $v );
				} else {
					$into[$k][] = $v;
				}
			} else {
				$into[$k] = $v;
			}
		}

		return $into;
	}

	/**
	 * Compares two entity structures and asserts that they are equal. Only fields present in $expected are considered.
	 * $expected and $actual can both be either in "flat" or in "deep" form, they are converted as needed before comparison.
	 *
	 * @param array $expected
	 * @param array $actual
	 * @param bool $expectEmptyArrays Should we expect empty arrays or just ignore them?
	 */
	// phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
	protected function assertEntityEquals( array $expected, array $actual, $expectEmptyArrays = true ) {
		if ( isset( $expected['id'] ) && !empty( $expected['id'] ) ) {
			$this->assertEquals( $expected['id'], $actual['id'], 'id' );
		}
		if ( isset( $expected['lastrevid'] ) ) {
			$this->assertEquals( $expected['lastrevid'], $actual['lastrevid'], 'lastrevid' );
		}
		if ( isset( $expected['type'] ) ) {
			$this->assertEquals( $expected['type'], $actual['type'], 'type' );
		}
		if ( isset( $expected['datatype'] ) ) {
			$this->assertSame( $expected['datatype'], $actual['datatype'], 'datatype' );
		}

		if ( isset( $expected['labels'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['labels'] === [] ) ) {
				$data = $this->flattenArray( $actual['labels'], 'language', 'value' );
				$exp = $this->flattenArray( $expected['labels'], 'language', 'value' );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['descriptions'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['descriptions'] === [] ) ) {
				$data = $this->flattenArray( $actual['descriptions'], 'language', 'value' );
				$exp = $this->flattenArray( $expected['descriptions'], 'language', 'value' );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['sitelinks'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['sitelinks'] === [] ) ) {
				$data = $this->flattenArray( $actual['sitelinks'] ?? [], 'site', 'title' );
				$exp = $this->flattenArray( $expected['sitelinks'], 'site', 'title' );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['aliases'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['aliases'] === [] ) ) {
				$data = $this->flattenArray( $actual['aliases'], 'language', 'value', true );
				$exp = $this->flattenArray( $expected['aliases'], 'language', 'value', true );

				// keys are significant in flat form
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}

		if ( isset( $expected['claims'] ) ) {
			if ( !( $expectEmptyArrays === false && $expected['claims'] === [] ) ) {
				$data = $this->flattenArray( $actual['claims'], 'mainsnak', 'value', true );
				$exp = $this->flattenArray( $expected['claims'], 'language', 'value', true );
				$count = count( $expected['claims'] );

				for ( $i = 0; $i < $count; $i++ ) {
					$this->assertArrayHasKey( $i, $data['id'] );
					$this->assertGreaterThanOrEqual( 39, strlen( $data['id'][$i] ) );
				}
				//unset stuff we dont actually want to compare
				if ( isset( $exp['id'] ) ) {
					$this->assertArrayHasKey( 'id', $data );
				}
				unset( $exp['id'] );
				unset( $exp['datatype'] );
				unset( $exp['hash'] );
				unset( $exp['qualifiers-order'] );
				unset( $data['datatype'] );
				unset( $data['id'] );
				unset( $data['hash'] );
				unset( $data['qualifiers-order'] );
				$this->assertArrayEquals( $exp, $data, false, true );
			}
		}
	}

	/**
	 * Asserts that the given API response represents a successful call.
	 *
	 * @param array $response
	 */
	protected function assertResultSuccess( array $response ) {
		$this->assertArrayHasKey( 'success', $response, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $response );
	}

	/**
	 * Asserts that the given API response has a valid entity type if the result contains an entity
	 *
	 * @param array $response
	 */
	protected function assertResultHasEntityType( array $response ) {
		if ( isset( $response['entity'] ) ) {
			if ( isset( $response['entity']['type'] ) ) {
				$this->assertContains(
					$response['entity']['type'],
					WikibaseRepo::getEnabledEntityTypes(),
					"Missing valid 'type' in response."
				);
			}
		} elseif ( isset( $response['entities'] ) ) {
			foreach ( $response['entities'] as $entity ) {
				if ( isset( $entity['type'] ) ) {
					$this->assertContains(
						$entity['type'],
						WikibaseRepo::getEnabledEntityTypes(),
						"Missing valid 'type' in response."
					);
				}
			}
		}
	}

	/**
	 * Asserts that the revision with the given ID has a summary matching $regex
	 *
	 * @param string|string[] $regex The regex to match, or an array to build a regex from.
	 * @param int $revid
	 */
	protected function assertRevisionSummary( $regex, $revid ) {
		if ( is_array( $regex ) ) {
			$r = '';

			foreach ( $regex as $s ) {
				if ( $r !== '' ) {
					$r .= '.*';
				}

				$r .= preg_quote( $s, '!' );
			}

			$regex = "!$r!";
		}

		$revRecord = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( $revid );
		$this->assertNotNull( $revRecord, "revision not found: $revid" );

		$comment = $revRecord->getComment();
		$this->assertInstanceOf( CommentStoreComment::class, $comment );
		$this->assertMatchesRegularExpression( $regex, $comment->text );
	}

	protected function assertCanTagSuccessfulRequest(
		array $params,
		array $session = null,
		Authority $user = null,
		$tokenType = 'csrf'
	) {
		$dummyTag = __METHOD__ . '-dummy-tag';
		ChangeTags::defineTag( $dummyTag );

		$params[ 'tags' ] = $dummyTag;

		list( $result, , ) = $this->doApiRequestWithToken( $params, $session, $user, $tokenType );

		$this->assertArrayNotHasKey( 'warnings', $result, json_encode( $result ) );
		$this->assertArrayHasKey( 'success', $result );
		$lastRevid = $this->getLastRevIdFromResult( $result );
		if ( $lastRevid === null ) {
			$this->fail(
				'API result does not have lastrevid. Actual result: '
				. json_encode( $result, JSON_PRETTY_PRINT )
			);
		}

		$this->assertContains( $dummyTag,
			ChangeTags::getTags( $this->db, null, $lastRevid ) );
	}

	private function getLastRevIdFromResult( array $result ) {
		if ( isset( $result['entity']['lastrevid'] ) ) {
			return $result['entity']['lastrevid'];
		}
		if ( isset( $result['pageinfo']['lastrevid'] ) ) {
			return $result['pageinfo']['lastrevid'];
		}
		if ( isset( $result['lastrevid'] ) ) {
			return $result['lastrevid'];
		}

		return null;
	}

}
