<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Permissions\Authority;
use ReadOnlyError;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\EditEntity
 * @covers \Wikibase\Repo\Api\ModifyEntity
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Michal Lazowik
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group BreakingTheSlownessBarrier
 * @group Database
 * @group medium
 */
class EditEntityTest extends WikibaseApiTestCase {

	/**
	 * @var string[]
	 */
	private static $idMap;

	/**
	 * @var bool
	 */
	private static $hasSetup;

	protected function setUp(): void {
		parent::setUp();

		// XXX: This test doesn't mark tablesUsed so things created here will remain through all tests in the class.
		if ( !isset( self::$hasSetup ) ) {
			$store = $this->getEntityStore();

			$prop = Property::newFromType( 'string' );
			$store->saveEntity( $prop, 'EditEntityTestP56', $this->user, EDIT_NEW );
			self::$idMap['%P56%'] = $prop->getId()->getSerialization();
			self::$idMap['%StringProp%'] = $prop->getId()->getSerialization();

			$prop = Property::newFromType( 'string' );
			$store->saveEntity( $prop, 'EditEntityTestP72', $this->user, EDIT_NEW );
			self::$idMap['%P72%'] = $prop->getId()->getSerialization();

			$this->initTestEntities( [ 'Berlin' ], self::$idMap );
			self::$idMap['%Berlin%'] = EntityTestHelper::getId( 'Berlin' );

			$p56 = self::$idMap['%P56%'];
			$berlinData = EntityTestHelper::getEntityOutput( 'Berlin' );
			self::$idMap['%BerlinP56%'] = $berlinData['claims'][$p56][0]['id'];

			$badge = new Item();
			$store->saveEntity( $badge, 'EditEntityTestQ42', $this->user, EDIT_NEW );
			self::$idMap['%Q42%'] = $badge->getId()->getSerialization();

			$badge = new Item();
			$store->saveEntity( $badge, 'EditEntityTestQ149', $this->user, EDIT_NEW );
			self::$idMap['%Q149%'] = $badge->getId()->getSerialization();

			$badge = new Item();
			$store->saveEntity( $badge, 'EditEntityTestQ32', $this->user, EDIT_NEW );
			self::$idMap['%Q32%'] = $badge->getId()->getSerialization();

			// self::$idMap['%UppercaseStringProp%'] is added in testEditEntity
		}

		WikibaseRepo::getSettings()->setSetting( 'badgeItems', [
			self::$idMap['%Q42%'] => '',
			self::$idMap['%Q149%'] => '',
			'Q99999' => '', // Just in case we have a wrong config
		] );

		self::$hasSetup = true;
	}

	/**
	 * Provide data for a sequence of requests that will work when run in order
	 */
	public function provideData() {
		return [
			'new item' => [
				'p' => [ 'new' => 'item', 'data' => '{}' ],
				'e' => [ 'type' => 'item' ] ],
			'new property' => [ // make sure if we pass in a valid type it is accepted
				'p' => [ 'new' => 'property', 'data' => '{"datatype":"string"}' ],
				'e' => [ 'type' => 'property', 'datatype' => 'string' ] ],
			'new property with data' => [ // this is our current example in the api doc
				'p' => [
					'new' => 'property',
					'data' => '{"labels":{"en-gb":{"language":"en-gb","value":"Propertylabel"}},'
						. '"descriptions":{"en-gb":{"language":"en-gb","value":"Propertydescription"}},'
						. '"datatype":"string"}',
				],
				'e' => [
					'type' => 'property',
					'datatype' => 'string',
					'labels' => [ 'en-gb' => 'Propertylabel' ],
					'descriptions' => [ 'en-gb' => 'Propertydescription' ],
				] ],
			'add a sitelink..' => [ // make sure if we pass in a valid id it is accepted
				'p' => [
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki",'
						. '"title":"TestPage!","badges":["%Q42%","%Q149%"]}}}',
				],
				'e' => [
					'sitelinks' => [
						[
							'site' => 'dewiki',
							'title' => 'TestPage!',
							'badges' => [ '%Q42%', '%Q149%' ],
						],
					],
				],
			],
			'add a label, (making sure some data fields are ignored)' => [
				'p' => [
					'data' => [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'A Label' ] ],
						'length' => 'ignoreme!',
						'count' => 'ignoreme!',
						'touched' => 'ignoreme!',
						'modified' => 'ignoreme!',
					],
				],
				'e' => [
					'sitelinks' => [
						[
							'site' => 'dewiki',
							'title' => 'TestPage!',
							'badges' => [ '%Q42%', '%Q149%' ],
						],
					],
					'labels' => [ 'en' => 'A Label' ],
				],
			],
			'add a description..' => [
				'p' => [ 'data' => '{"descriptions":{"en":{"language":"en","value":"DESC"}}}' ],
				'e' => [
					'sitelinks' => [
						[
							'site' => 'dewiki',
							'title' => 'TestPage!',
							'badges' => [ '%Q42%', '%Q149%' ],
						],
					],
					'labels' => [ 'en' => 'A Label' ],
					'descriptions' => [ 'en' => 'DESC' ],
				],
			],
			'remove a sitelink..' => [
				'p' => [ 'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":""}}}' ],
				'e' => [
					'labels' => [ 'en' => 'A Label' ],
					'descriptions' => [ 'en' => 'DESC' ] ],
			],
			'remove a label..' => [
				'p' => [ 'data' => '{"labels":{"en":{"language":"en","value":""}}}' ],
				'e' => [ 'descriptions' => [ 'en' => 'DESC' ] ] ],
			'remove a description..' => [
				'p' => [ 'data' => '{"descriptions":{"en":{"language":"en","value":""}}}' ],
				'e' => [ 'type' => 'item' ] ],
			'clear an item with some new value' => [
				'p' => [
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"page"}}}',
					'clear' => '',
				],
				'e' => [
					'type' => 'item',
					'sitelinks' => [
						[
							'site' => 'dewiki',
							'title' => 'Page',
							'badges' => [],
						],
					],
				],
			],
			'clear an item with no value' => [
				'p' => [ 'data' => '{}', 'clear' => '' ],
				'e' => [ 'type' => 'item' ] ],
			'add 2 labels' => [
				'p' => [ 'data' => '{"labels":{"en":{"language":"en","value":"A Label"},'
					. '"sv":{"language":"sv","value":"SVLabel"}}}' ],
				'e' => [ 'labels' => [ 'en' => 'A Label', 'sv' => 'SVLabel' ] ] ],
			'remove a label with remove' => [
				'p' => [ 'data' => '{"labels":{"en":{"language":"en","remove":true}}}' ],
				'e' => [ 'labels' => [ 'sv' => 'SVLabel' ] ] ],
			'override and add 2 descriptions' => [
				'p' => [ 'clear' => '', 'data' => '{"descriptions":{'
					. '"en":{"language":"en","value":"DESC1"},'
					. '"de":{"language":"de","value":"DESC2"}}}' ],
				'e' => [ 'descriptions' => [ 'en' => 'DESC1', 'de' => 'DESC2' ] ] ],
			'remove a description with remove' => [
				'p' => [ 'data' => '{"descriptions":{"en":{"language":"en","remove":true}}}' ],
				'e' => [ 'descriptions' => [ 'de' => 'DESC2' ] ] ],
			'override and add 2 sitelinks..' => [
				'p' => [ 'data' => '{"sitelinks":{'
					. '"dewiki":{"site":"dewiki","title":"BAA"},'
					. '"svwiki":{"site":"svwiki","title":"FOO"}}}' ],
				'e' => [
					'type' => 'item',
					'sitelinks' => [
						[
							'site' => 'dewiki',
							'title' => 'BAA',
							'badges' => [],
						],
						[
							'site' => 'svwiki',
							'title' => 'FOO',
							'badges' => [],
						],
					],
				],
			],
			'unset a sitelink using the other sitelink' => [
				'p' => [
					'site' => 'svwiki',
					'title' => 'FOO',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":""}}}',
				],
				'e' => [
					'type' => 'item',
					'sitelinks' => [
						[
							'site' => 'svwiki',
							'title' => 'FOO',
							'badges' => [],
						],
					],
				],
			],
			'set badges for a existing sitelink, title intact' => [
				'p' => [
					'data' => '{"sitelinks":{"svwiki":{"site":"svwiki","badges":["%Q149%","%Q42%"]}}}',
				],
				'e' => [
					'type' => 'item',
					'sitelinks' => [
						[
							'site' => 'svwiki',
							'title' => 'FOO',
							'badges' => [ "%Q149%", "%Q42%" ],
						],
					],
				],
			],
			'set title for a existing sitelink, badges intact' => [
				'p' => [ 'data' => '{"sitelinks":{"svwiki":{"site":"svwiki","title":"FOO2"}}}' ],
				'e' => [
					'type' => 'item',
					'sitelinks' => [
						[
							'site' => 'svwiki',
							'title' => 'FOO2',
							'badges' => [ "%Q149%", "%Q42%" ],
						],
					],
				],
			],
			'delete sitelink by providing neither title nor badges' => [
				'p' => [ 'data' => '{"sitelinks":{"svwiki":{"site":"svwiki"}}}' ],
				'e' => [
					'type' => 'item',
				],
			],
			'add a claim' => [
				'p' => [ 'data' => '{"claims":[{"mainsnak":{"snaktype":"value",'
					. '"property":"%P56%","datavalue":{"value":"imastring","type":"string"}},'
					. '"type":"statement","rank":"normal"}]}' ],
				'e' => [ 'claims' => [
					'%P56%' => [
						'mainsnak' => [
							'snaktype' => 'value',
							'property' => '%P56%',
							'datavalue' => [ 'value' => 'imastring', 'type' => 'string' ],
						],
						'type' => 'statement',
						'rank' => 'normal',
					],
				] ],
			],
			'change the claim' => [
				'p' => [ 'data' => [
					'claims' => [
						[
							'id' => '%lastClaimId%',
							'mainsnak' => [
								'snaktype' => 'value',
								'property' => '%P56%',
								'datavalue' => [
									'value' => 'diffstring',
									'type' => 'string',
								],
							],
							'type' => 'statement',
							'rank' => 'normal',
						],
					],
				] ],
				'e' => [ 'claims' => [
					'%P56%' => [
						'mainsnak' => [ 'snaktype' => 'value', 'property' => '%P56%',
							'datavalue' => [
								'value' => 'diffstring',
								'type' => 'string' ] ],
						'type' => 'statement',
						'rank' => 'normal',
					],
				] ],
			],
			'remove the claim' => [
				'p' => [ 'data' => '{"claims":[{"id":"%lastClaimId%","remove":""}]}' ],
				'e' => [ 'claims' => [] ],
			],
			'add multiple claims' => [
				'p' => [ 'data' => '{"claims":['
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring1","type":"string"}},"type":"statement","rank":"normal"},'
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring2","type":"string"}},"type":"statement","rank":"normal"}'
					. ']}' ],
				'e' => [ 'claims' => [
					[
						'mainsnak' => [
							'snaktype' => 'value', 'property' => '%P56%',
							'datavalue' => [
								'value' => 'imastring1',
								'type' => 'string' ] ],
						'type' => 'statement',
						'rank' => 'normal' ],
					[
						'mainsnak' => [
							'snaktype' => 'value', 'property' => '%P56%',
							'datavalue' => [
								'value' => 'imastring2',
								'type' => 'string' ] ],
						'type' => 'statement',
						'rank' => 'normal' ],
				] ],
			],
			'remove all stuff' => [
				'p' => [ 'clear' => '', 'data' => '{}' ],
				'e' => [
					'labels' => [],
					'descriptions' => [],
					'aliases' => [],
					'sitelinks' => [],
					'claims' => [],
				],
			],
			'add lots of data again' => [
				'p' => [ 'data' => '{"claims":['
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring1","type":"string"}},"type":"statement","rank":"normal"},'
					. '{"mainsnak":{"snaktype":"value","property":"%P56%","datavalue":'
					. '{"value":"imastring2","type":"string"}},"type":"statement","rank":"normal"}'
					. '],'
					. '"sitelinks":{"dewiki":{"site":"dewiki","title":"page"}},'
					. '"labels":{"en":{"language":"en","value":"A Label"}},'
					. '"descriptions":{"en":{"language":"en","value":"A description"}}}' ],
				'e' => [ 'type' => 'item' ],
			],
			'make a null edit' => [
				'p' => [ 'data' => '{}' ],
				'e' => [ 'nochange' => '' ],
			],
			'remove all stuff in another way' => [
				'p' => [ 'clear' => true, 'data' => '{}' ],
				'e' => [
					'labels' => [],
					'descriptions' => [],
					'aliases' => [],
					'sitelinks' => [],
					'claims' => [],
				],
			],
			'return normalized data' => [
				'p' => [ 'data' => '{"claims":['
					. '{"mainsnak":{"snaktype":"value","property":"%UppercaseStringProp%",'
					. '"datavalue":{"value":"a string","type":"string"}},'
					. '"type":"statement","rank":"normal"}]}' ],
				'e' => [
					'claims' => [ [
						'mainsnak' => [
							'snaktype' => 'value',
							'property' => '%UppercaseStringProp%',
							'datavalue' => [ 'value' => 'A STRING', 'type' => 'string' ],
						],
						'type' => 'statement',
						'rank' => 'normal',
					] ],
				],
			],
		];
	}

	/**
	 * Applies self::$idMap to all data in the given data structure, recursively.
	 *
	 * @param mixed &$data
	 */
	protected function injectIds( &$data ) {
		EntityTestHelper::injectIds( $data, self::$idMap );
	}

	/**
	 * Skips a test of the given entity type is not enabled.
	 *
	 * @param string|null $requiredEntityType
	 */
	private function skipIfEntityTypeNotKnown( $requiredEntityType ) {
		if ( $requiredEntityType === null ) {
			return;
		}

		$enabledTypes = WikibaseRepo::getLocalEntityTypes();
		if ( !in_array( $requiredEntityType, $enabledTypes ) ) {
			$this->markTestSkipped( 'Entity type not enabled: ' . $requiredEntityType );
		}
	}

	public function testUserCanEditWhenTheyHaveSufficientPermission() {
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'all-permission' => [ 'read' => true, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			'*' => [ 'read' => true, 'edit' => false, 'writeapi' => true ],
		] );

		$newItem = $this->createItemUsing( $userWithAllPermissions );
		$this->assertArrayHasKey( 'id', $newItem );
	}

	public function testUserCannotEditWhenTheyLackPermission() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'read' => true, 'edit' => false ],
			'all-permission' => [ 'read' => true, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			'*' => [ 'read' => true, 'edit' => false, 'writeapi' => true ],
		] );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );

		// And an existing item
		$newItem = $this->createItemUsing( $userWithAllPermissions );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithAllPermissions
		);
		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithInsufficientPermissions
		);

		$this->doTestQueryExceptions(
			$this->addSiteLink( $newItem['id'] ),
			$expected,
			$userWithInsufficientPermissions
		);
	}

	public function testEditingLabelRequiresEntityTermEditPermissions() {
		$userWithInsufficientPermissions = $this->createUserWithGroup( 'no-permission' );
		$userWithAllPermissions = $this->createUserWithGroup( 'all-permission' );

		$this->setMwGlobals( 'wgGroupPermissions', [
			'no-permission' => [ 'read' => true, 'edit' => true, 'item-term' => false ],
			'all-permission' => [ 'read' => true, 'edit' => true, 'item-term' => true, 'createpage' => true ],
			'*' => [ 'read' => true, 'edit' => false, 'writeapi' => true ],
		] );

		MediaWikiServices::getInstance()->resetServiceForTesting( 'PermissionManager' );

		// And an existing item
		$newItem = $this->createItemUsing( $userWithAllPermissions );

		// Then the request is denied
		$expected = [
			'type' => ApiUsageException::class,
			'code' => 'permissiondenied',
		];

		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithAllPermissions
		);
		MediaWikiServices::getInstance()->getPermissionManager()->invalidateUsersRightsCache(
			$userWithInsufficientPermissions
		);

		$this->doTestQueryExceptions(
			$this->removeLabel( $newItem['id'] ),
			$expected,
			$userWithInsufficientPermissions );
	}

	private function createItemUsing( Authority $user ) {
		$createItemParams = [ 'action' => 'wbeditentity',
			'new' => 'item',
			'data' =>
				'{"labels":{"en":{"language":"en","value":"something"}}}' ];
		list( $result, ) = $this->doApiRequestWithToken( $createItemParams, null, $user );
		return $result['entity'];
	}

	/**
	 * @param string $groupName
	 *
	 * @return Authority
	 */
	private function createUserWithGroup( $groupName ) {
		return $this->getTestUser( [ 'wbeditor', $groupName ] )->getUser();
	}

	private function addSiteLink( $id ) {
		return [
			'action' => 'wbeditentity',
			'id' => $id,
			'data' => '{"sitelinks":{"enwiki":{"site":"enwiki","title":"Hello World"}}}',
		];
	}

	private function removeLabel( $id ) {
		return [
			'action' => 'wbeditentity',
			'id' => $id,
			'data' => '{"labels":{"en":{"language":"en","value":""}}}',
		];
	}

	/**
	 * @dataProvider provideData
	 */
	public function testEditEntity( $params, $expected, $needed = null ) {
		$this->skipIfEntityTypeNotKnown( $needed );

		// this registers a new datatype, can’t be done in setUp
		self::$idMap['%UppercaseStringProp%'] = $this
			->createUppercaseStringTestProperty()->getSerialization();

		$this->injectIds( $params );
		$this->injectIds( $expected );

		$p56 = '%P56%';
		$this->injectIds( $p56 );

		if ( isset( $params['data'] ) && is_array( $params['data'] ) ) {
			$params['data'] = json_encode( $params['data'] );
		}

		// -- set any defaults ------------------------------------
		$params['action'] = 'wbeditentity';
		if ( !array_key_exists( 'id', $params )
			&& !array_key_exists( 'new', $params )
			&& !array_key_exists( 'site', $params )
			&& !array_key_exists( 'title', $params )
		) {
			$params['id'] = self::$idMap['!lastEntityId!'];
		}

		// -- do the request --------------------------------------------------
		list( $result, , ) = $this->doApiRequestWithToken( $params );

		// -- steal ids for later tests -------------------------------------
		if ( array_key_exists( 'new', $params ) && stristr( $params['new'], 'item' ) ) {
			self::$idMap['!lastEntityId!'] = $result['entity']['id'];
		}
		if ( array_key_exists( 'claims', $result['entity'] )
			&& array_key_exists( $p56, $result['entity']['claims'] )
		) {
			foreach ( $result['entity']['claims'][$p56] as $claim ) {
				if ( array_key_exists( 'id', $claim ) ) {
					self::$idMap['%lastClaimId%'] = $claim['id'];
				}
			}
		}

		// -- check the result ------------------------------------------------
		$this->assertArrayHasKey( 'success', $result, "Missing 'success' marker in response." );
		$this->assertResultHasEntityType( $result );
		$this->assertArrayHasKey( 'entity', $result, "Missing 'entity' section in response." );

		$this->assertArrayHasKey(
			'id',
			$result['entity'],
			"Missing 'id' section in entity in response."
		);

		$this->assertEntityEquals( $expected, $result['entity'] );

		// -- check null edits ---------------------------------------------
		if ( isset( $expected['nochange'] ) ) {
			$this->assertArrayHasKey( 'nochange', $result['entity'] );
		}

		// -- check the item in the database -------------------------------
		$dbEntity = $this->loadEntity( $result['entity']['id'] );
		$this->assertEntityEquals( $expected, $dbEntity, false );

		// -- check the edit summary --------------------------------------------
		if ( !array_key_exists( 'warning', $expected )
			|| $expected['warning'] != 'edit-no-change'
		) {
			$this->assertRevisionSummary(
				[ 'wbeditentity' ],
				$result['entity']['lastrevid']
			);

			if ( array_key_exists( 'summary', $params ) ) {
				$this->assertRevisionSummary(
					'/' . $params['summary'] . '/',
					$result['entity']['lastrevid']
				);
			}
		}
	}

	public function provideItemIdParamsAndExpectedSummaryPatternForEditEntity() {
		return [
			'no languages changed' => [
				[
					'action' => 'wbeditentity',
					'data' => json_encode( [
						'labels' => [],
						'descriptions' => [],
						'aliases' => [],
						'sitelinks' => [
							[
								'site' => 'dewiki',
								'title' => 'Page',
								'badges' => [],
							],
						],
					] ),
				],
				preg_quote( '/* wbeditentity-update:0| */' ),
			],
			'only one language changed, no other parts changed' => [
				[
					'action' => 'wbeditentity',
					'data' => json_encode( [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'Foo' ] ],
						'descriptions' => [],
						'aliases' => [],
					] ),
				],
				preg_quote( '/* wbeditentity-update-languages-short:0||en */' ),
			],
			'multiple languages changed, no other parts changed' => [
				[
					'action' => 'wbeditentity',
					'data' => json_encode( [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'Foo' ] ],
						'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'Bar' ] ],
						'aliases' => [ 'es' => [ [ 'language' => 'es', 'value' => 'ooF' ], [ 'language' => 'es', 'value' => 'raB' ] ] ],
					] ),
				],
				preg_quote( '/* wbeditentity-update-languages-short:0||en, de, es */' ),
			],
			'some languages changed and other parts changed' => [
				[
					'action' => 'wbeditentity',
					'data' => json_encode( [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'Foo' ] ],
						'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'Bar' ] ],
						'aliases' => [ 'es' => [ [ 'language' => 'es', 'value' => 'ooF' ], [ 'language' => 'es', 'value' => 'raB' ] ] ],
						'sitelinks' => [
							[
								'site' => 'dewiki',
								'title' => 'Some Page',
								'badges' => [],
							],
						],
					] ),
				],
				preg_quote( '/* wbeditentity-update-languages-and-other-short:0||en, de, es */' ),
			],
			'more than 50 languages changed' => [
				[
					'action' => 'wbeditentity',
					'data' => json_encode( [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'Foo' ] ],
						'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'Bar' ] ],
						'aliases' => $this->generateLanguageValuePairs( 50 ),
					] ),
				],
				preg_quote( '/* wbeditentity-update-languages:0||52 */' ),
			],
			'more than 50 languages changed and other parts changed' => [
				[
					'action' => 'wbeditentity',
					'data' => json_encode( [
						'labels' => [ 'en' => [ 'language' => 'en', 'value' => 'Foo' ] ],
						'descriptions' => [ 'de' => [ 'language' => 'de', 'value' => 'Bar' ] ],
						'aliases' => $this->generateLanguageValuePairs( 50 ),
						'sitelinks' => [
							[
								'site' => 'dewiki',
								'title' => 'Some other Page',
								'badges' => [],
							],
						],
					] ),
				],
				preg_quote( '/* wbeditentity-update-languages-and-other:0||52 */' ),
			],
		];
	}

	/**
	 * @dataProvider provideItemIdParamsAndExpectedSummaryPatternForEditEntity
	 */
	public function testEditEntity_producesCorrectSummary( $params, $expectedSummaryPattern ) {
		// Saving entity couldn't be done in the provider because there the
		// test database setup has not been done yet
		$item = new Item();
		$this->saveEntity( $item );
		$params['id'] = $item->getId()->getSerialization();

		list( $result ) = $this->doApiRequestWithToken( $params );

		$this->assertRevisionSummary(
			$expectedSummaryPattern,
			$result['entity']['lastrevid']
		);
	}

	private function generateLanguageValuePairs( $langCount ) {
		$result = [];
		$langCodes = WikibaseRepo::getTermsLanguages()->getLanguages();

		for ( $langCount = min( $langCount, ( count( $langCodes ) ) ); $langCount > 0; $langCount-- ) {
			$result[ $langCodes[ $langCount ] ] = [ 'language' => $langCodes[ $langCount ], 'value' => "Foo{$langCount}" ];
		}
		return $result;
	}

	protected function saveEntity( EntityDocument $entity ) {
		$this->getEntityStore()->saveEntity(
			$entity,
			static::class,
			$this->getTestUser()->getUser(),
			EDIT_NEW
		);
	}

	/**
	 * Provide data for requests that will fail with a set exception, code and message
	 */
	public function provideExceptionData() {
		return [
			'no entity id given' => [
				'p' => [ 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-illegal',
				] ] ],
			'empty entity id given' => [
				'p' => [ 'id' => '', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ] ],
			'invalid id' => [
				'p' => [ 'id' => 'abcde', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ] ],
			'unknown id' => [
				'p' => [ 'id' => 'Q1234567', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-entity',
				] ] ],
			'invalid explicit id' => [
				'p' => [ 'id' => '1234', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ] ],
			'non existent sitelink' => [
				'p' => [ 'site' => 'dewiki','title' => 'NonExistent', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-entity-link',
				] ] ],
			'missing site (also bad title)' => [
				'p' => [ 'title' => 'abcde', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ] ],
			'missing site but id given' => [
				'p' => [ 'title' => 'abcde', 'id' => 'Q12', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-missing',
				] ] ],
			'cant have id and new' => [
				'p' => [ 'id' => 'q666', 'new' => 'item', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-illegal',
					'message' => 'Either provide the item "id" or pairs of "site" and "title" or a "new" type for an entity',
				] ] ],
			'when clearing must also have data!' => [
				'p' => [ 'site' => 'enwiki', 'title' => 'Berlin', 'clear' => '' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'nodata' ),
						$this->equalTo( 'missingparam' )
					),
				] ] ],
			'bad site' => [
				'p' => [ 'site' => 'abcde', 'data' => '{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'unknown_site' ),
						$this->equalTo( 'badvalue' )
					),
				] ] ],
			'no data provided' => [
				'p' => [ 'site' => 'enwiki', 'title' => 'Berlin' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'nodata' ), // see 'no$1' in ApiBase::$messageMap
						$this->equalTo( 'missingparam' )
					),
				] ],
			],
			'malformed json' => [
				'p' => [ 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '{{{}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-json',
				] ] ],
			'must be a json object (json_decode s this an an int)' => [
				'p' => [ 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '1234' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-recognized-array',
				] ] ],
			'must be a json object (json_decode s this an an indexed array)' => [
				'p' => [ 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '[ "xyz" ]' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-recognized-string',
				] ] ],
			'must be a json object (json_decode s this an a string)' => [
				'p' => [ 'site' => 'enwiki', 'title' => 'Berlin', 'data' => '"string"' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-recognized-array',
				] ] ],
			'inconsistent site in json' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"ptwiki":{"site":"svwiki","title":"TestPage!"}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'inconsistent-site',
				] ] ],
			'inconsistent lang in json' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"de":{"language":"pt","value":"TestPage!"}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'inconsistent-language',
				] ] ],
			'inconsistent unknown site in json' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"BLUB":{"site":"BLUB","title":"TestPage!"}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-recognized-site',
				] ] ],
			'inconsistent unknown languages' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"BLUB":{"language":"BLUB","value":"ImaLabel"}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-recognized-language',
				] ] ],
			// @todo the error codes in the overly long string tests make no sense
			// and should be corrected...
			'overly long label' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"en":{"language":"en","value":"'
						. TermTestHelper::makeOverlyLongString() . '"}}}',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'overly long description' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"descriptions":{"en":{"language":"en","value":"'
						. TermTestHelper::makeOverlyLongString() . '"}}}',
				],
				'e' => [ 'exception' => [ 'type' => ApiUsageException::class ] ] ],
			'missing language in labels (T54731)' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"labels":{"de":{"site":"pt","title":"TestString"}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'missing-language',
					'message' => '\'language\' was not found in term serialization for de',
				] ],
			],
			'removing invalid claim fails' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"claims":[{"remove":""}]}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-claim',
					'message' => 'Cannot remove a claim with no GUID',
				] ],
			],
			'invalid entity ID in data value' => [
				'p' => [
					'id' => '%Berlin%',
					'data' => '{ "claims": [ {
						"mainsnak": { "snaktype": "novalue", "property": "P0" },
						"type": "statement"
					} ] }',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-claim',
					'message' => '\'P0\' is not a valid',
				] ],
			],
			'invalid statement GUID' => [
				'p' => [
					'id' => '%Berlin%',
					'data' => '{ "claims": [ {
						"id": "Q0$GUID",
						"mainsnak": { "snaktype": "novalue", "property": "%P56%" },
						"type": "statement"
					} ] }',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'modification-failed',
					'message' => 'Statement GUID can not be parsed',
				] ],
			],
			'removing valid claim with no guid fails' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{
						"claims": [ {
							"remove": "",
							"mainsnak": {
								"snaktype": "value",
								"property": "%P56%",
								"datavalue": { "value": "imastring", "type": "string" }
							},
							"type": "statement",
							"rank": "normal"
						} ]
					}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-claim',
				] ],
			],
			'bad badge id' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["abc","%Q149%"]}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ],
			],
			'badge id is not an item id' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["P2","%Q149%"]}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'invalid-entity-id',
				] ],
			],
			'badge id is not specified' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["%Q149%","%Q32%"]}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-badge',
				] ],
			],
			'badge item does not exist' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!",'
						. '"badges":["Q99999","%Q149%"]}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-entity',
				] ],
			],
			'no sitelink - cannot change badges' => [
				'p' => [
					'site' => 'enwiki',
					'title' => 'Berlin',
					'data' => '{"sitelinks":{"svwiki":{"site":"svwiki",'
						. '"badges":["%Q42%","%Q149%"]}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'no-such-sitelink',
					'message' => wfMessage( 'wikibase-validator-no-such-sitelink', 'svwiki' )->inLanguage( 'en' )->text(),
				] ],
			],
			'bad id in serialization' => [
				'p' => [ 'id' => '%Berlin%', 'data' => '{"id":"Q13244"}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-invalid',
					'message' => 'Invalid field used in call: "id", must match id parameter',
				] ],
			],
			'bad type in serialization' => [
				'p' => [ 'id' => '%Berlin%', 'data' => '{"id":"%Berlin%","type":"foobar"}' ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-invalid',
					'message' => 'Invalid field used in call: "type", '
						. 'must match type associated with id',
				] ],
			],
			'bad main snak replacement' => [
				'p' => [ 'id' => '%Berlin%', 'data' => json_encode( [
					'claims' => [
						[
							'id' => '%BerlinP56%',
							'mainsnak' => [
								'snaktype' => 'value',
								'property' => '%P72%',
								'datavalue' => [
									'value' => 'anotherstring',
									'type' => 'string',
								],
							],
							'type' => 'statement',
							'rank' => 'normal' ],
					],
				] ) ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'modification-failed',
					'message' => 'uses property %P56%, can\'t change to %P72%' ] ] ],
			'invalid main snak' => [
				'p' => [ 'id' => '%Berlin%', 'data' => json_encode( [
					'claims' => [
						[
							'id' => '%BerlinP56%',
							'mainsnak' => [
								'snaktype' => 'value',
								'property' => '%P56%',
								'datavalue' => [ 'value' => '   ', 'type' => 'string' ],
							],
							'type' => 'statement',
							'rank' => 'normal' ],
					],
				] ) ],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'modification-failed' ] ] ],
			'properties cannot have sitelinks' => [
				'p' => [
					'id' => '%P56%',
					'data' => '{"sitelinks":{"dewiki":{"site":"dewiki","title":"TestPage!"}}}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-supported',
					'message' => 'The requested feature is not supported by the given entity',
				] ] ],
			'property with invalid datatype' => [
				'p' => [
					'new' => 'property',
					'data' => '{"datatype":"invalid"}',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'param-illegal',
				] ] ],
			'remove key misplaced in data' => [
				'p' => [
					'id' => '%Berlin%',
					'data' => json_encode( [
						'remove' => '',
						'claims' => [ [
							'type' => 'statement',
							'mainsnak' => [
								'snaktype' => 'novalue',
								'property' => '%P56%',
							],
							'id' => '%BerlinP56%',
						] ],
					] ),
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => 'not-recognized',
					'message-key' => 'wikibase-api-illegal-entity-remove',
				] ],
			],
			'invalid tag (one)' => [
				'p' => [
					'new' => 'item',
					'data' => '{}',
					'tags' => 'test tag that definitely does not exist',
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'tags-apply-not-allowed-one' ),
						$this->equalTo( 'badtags' )
					),
				] ],
			],
			'invalid tag (multi)' => [
				'p' => [
					'new' => 'item',
					'data' => '{}',
					'tags' => implode( '|', [
						'test tag that definitely does not exist',
						'second test that that does not exist either',
					] ),
				],
				'e' => [ 'exception' => [
					'type' => ApiUsageException::class,
					'code' => $this->logicalOr(
						$this->equalTo( 'tags-apply-not-allowed-multi' ),
						$this->equalTo( 'badtags' )
					),
				] ],
			],
		];
	}

	/**
	 * @dataProvider provideExceptionData
	 */
	public function testEditEntityExceptions( $params, $expected, $needed = null ) {
		$this->skipIfEntityTypeNotKnown( $needed );

		$this->injectIds( $params );
		$this->injectIds( $expected );

		// -- set any defaults ------------------------------------
		$params['action'] = 'wbeditentity';
		$this->doTestQueryExceptions( $params, $expected['exception'] );
	}

	public function testItemCreationWithTag() {
		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => '{}',
		] );
	}

	public function testItemLabelEqualsDescriptionConflict() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => '{
				"labels": { "de": { "language": "de", "value": "label should not = description" } },
				"descriptions": { "de": { "language": "de", "value": "label should not = description" } }
			}',
		];

		$expectedException = [
			'type' => ApiUsageException::class,
			'code' => 'modification-failed',
		];
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testItemLabelConflictAvoidSelfConflictOnClear() {
		$params = [
			'action' => 'wbeditentity',
			'new' => 'item',
			'data' => '{
				"labels": { "de": { "language": "de", "value": "Very German label" } },
				"descriptions": { "de": { "language": "de", "value": "Very German description" } }
			}',
		];
		list( $result, ) = $this->doApiRequestWithToken( $params );

		$params = [
			'action' => 'wbeditentity',
			'id' => $result['entity']['id'],
			'clear' => 1,
			'data' => '{
				"labels": {
					"de": { "language": "de", "value": "Very German label" },
					"fa": { "language": "fa", "value": "Very non-German label" }
				},
				"descriptions": { "de": { "language": "de", "value": "Very German description" } }
			}',
		];
		list( $result, ) = $this->doApiRequestWithToken( $params );
		$this->assertSame( 1, $result['success'] );
	}

	public function testClearFromBadRevId() {
		$params = [
			'action' => 'wbeditentity',
			'id' => '%Berlin%',
			'data' => '{}',
			// 'baserevid' => '', // baserevid is set below
			'clear' => '' ];
		$this->injectIds( $params );

		$setupParams = [
			'action' => 'wbeditentity',
			'id' => $params['id'],
			'clear' => '',
			'data' => '{"descriptions":{"en":{"language":"en","value":"ClearFromBadRevidDesc1"}}}',
		];

		list( $result, , ) = $this->doApiRequestWithToken( $setupParams );
		$params['baserevid'] = $result['entity']['lastrevid'];
		$setupParams['data'] = '{"descriptions":{"en":{"language":"en","value":"ClearFromBadRevidDesc2"}}}';
		$this->doApiRequestWithToken( $setupParams );

		$expectedException = [ 'type' => ApiUsageException::class, 'code' => 'editconflict' ];
		$this->doTestQueryExceptions( $params, $expectedException );
	}

	public function testGivenReadOnlyType_errorIsShownAndNoEditHappened() {
		$oldSetting = WikibaseRepo::getSettings()->getSetting(
			'readOnlyEntityTypes'
		);

		WikibaseRepo::getSettings()->setSetting(
			'readOnlyEntityTypes',
			[ 'item' ]
		);

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'labels' => [ 'en' => [ 'value' => 'fooooo', 'language' => 'en' ] ],
			] ),
			'new' => 'item',
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Read only error did not happen but should' );
		} catch ( ReadOnlyError $e ) {
			$message = $e->getMessageObject();
			$this->assertEquals( 'readonlytext', $message->getKey() );
			$this->assertEquals(
				[ 'Editing of entity type: item is currently disabled. It will be enabled soon.' ],
				$message->getParams()
			);
		}

		WikibaseRepo::getSettings()->setSetting(
			'readOnlyEntityTypes',
			$oldSetting
		);
	}

	public function testIdGeneratorRateLimit() {
		$this->mergeMwGlobalArrayValue( 'wgRateLimits', [ 'wikibase-idgenerator' => [
			'anon' => [ 1, 60 ],
			'user' => [ 1, 60 ],
		] ] );
		$this->setMwGlobals( 'wgMainCacheType', 'hash' );

		$params = [
			'action' => 'wbeditentity',
			'data' => json_encode( [
				'labels' => [ 'en' => [ 'value' => 'rate limit test item', 'language' => 'en' ] ],
			] ),
			'new' => 'item',
		];

		[ $result ] = $this->doApiRequestWithToken( $params );
		$firstItemId = $result['entity']['id'];
		$firstId = ( new ItemId( $firstItemId ) )->getNumericId();

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Expected second request to hit ID generation rate limit' );
		} catch ( ApiUsageException $e ) {
			$expected = wfMessage( 'actionthrottledtext' )->parse();
			$expected = preg_replace( '/\s+/', ' ', $expected );
			$this->assertStringContainsString( $expected, $e->getMessage() );
		}

		$this->mergeMwGlobalArrayValue( 'wgRateLimits', [ 'wikibase-idgenerator' => [
			'anon' => [ 60, 60 ],
			'user' => [ 60, 60 ],
		] ] );

		[ $result ] = $this->doApiRequestWithToken( $params );
		$secondItemId = $result['entity']['id'];
		$secondId = ( new ItemId( $secondItemId ) )->getNumericId();

		$this->assertSame( $firstId + 1, $secondId,
			'Failed request should not have consumed item ID' );
	}

}
