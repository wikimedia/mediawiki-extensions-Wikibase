<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiResult;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use HashSiteStore;
use InvalidArgumentException;
use Status;
use Title;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * @covers \Wikibase\Repo\Api\ResultBuilder
 * @todo mock and inject serializers to avoid massive expected output?
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Thiemo Kreuz
 */
class ResultBuilderTest extends \PHPUnit\Framework\TestCase {

	private function getDefaultResult() {
		return new ApiResult( false );
	}

	private function getResultBuilder( ApiResult $result, $addMetaData = false ) {
		$mockTitle = $this->createMock( Title::class );
		$mockTitle->method( 'getArticleID' )
			->willReturn( 123 );
		$mockTitle->method( 'getNamespace' )
			->willReturn( 456 );
		$mockTitle->method( 'getPrefixedText' )
			->willReturn( 'MockPrefixedText' );

		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturn( $mockTitle );

		$mockPropertyDataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$mockPropertyDataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturnCallback( function( NumericPropertyId $id ) {
				return 'DtIdFor_' . $id->getSerialization();
			} );

		$propertyIdParser = $this->createStub( EntityIdParser::class );
		$propertyIdParser->method( 'parse' )
			->willReturnCallback( static function ( string $id ) {
				return new NumericPropertyId( $id );
			} );

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$builder = new ResultBuilder(
			$result,
			$entityTitleStoreLookup,
			$serializerFactory,
			$serializerFactory->newItemSerializer(),
			new HashSiteStore(),
			$mockPropertyDataTypeLookup,
			$propertyIdParser,
			$addMetaData
		);

		return $builder;
	}

	/**
	 * Removes all metadata keys as recognised by the MW Api.
	 * These all start with a '_' character.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private function removeMetaData( array $array ) {
		foreach ( $array as $key => &$value ) {
			if ( substr( (string)$key, 0, 1 ) === '_' ) {
				unset( $array[$key] );
			} else {
				if ( is_array( $value ) ) {
					$value = $this->removeMetaData( $value );
				}
			}
		}
		return $array;
	}

	public function testCanConstruct() {
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$this->assertInstanceOf( ResultBuilder::class, $resultBuilder );
	}

	/**
	 * @dataProvider provideMarkResultSuccess
	 */
	public function testMarkResultSuccess( $param, $expected ) {
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
		$data = $result->getResultData();

		$this->assertEquals(
			[
				'success' => $expected,
				'_type' => 'assoc',
			],
			$data
		);
	}

	public function provideMarkResultSuccess() {
		return [
			[ true, 1 ],
			[ 1, 1 ],
			[ false, 0 ],
			[ 0, 0 ],
			[ null, 0 ],
		];
	}

	/**
	 * @dataProvider provideMarkResultSuccessExceptions
	 */
	public function testMarkResultSuccessExceptions( $param ) {
		$this->expectException( InvalidArgumentException::class );
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
	}

	public function provideMarkResultSuccessExceptions() {
		return [ [ 3 ], [ -1 ] ];
	}

	public function provideTestAddEntityRevision() {
		$expected = [
			'entities' => [
				'Q1230000' => [
					'pageid' => 123, //mocked
					'ns' => 456, //mocked
					'title' => 'MockPrefixedText', //mocked
					'id' => 'Q123098',
					'type' => 'item',
					'lastrevid' => 33,
					'modified' => '2013-11-26T20:29:23Z',
					'redirects' => [
						'from' => 'Q1230000',
						'to' => 'Q123098',
					],
					'aliases' => [
						'en' => [
							[
								'language' => 'en',
								'value' => 'bar',
							],
							[
								'language' => 'en',
								'value' => 'baz',
							],
							'_element' => 'alias',
						],
						'zh' => [
							[
								'language' => 'zh',
								'value' => '????????',
							],
							'_element' => 'alias',
						],
						'_element' => 'language',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
					'descriptions' => [
						'pt' => [
							'language' => 'pt',
							'value' => 'ptDesc',
						],
						'pl' => [
							'language' => 'pl',
							'value' => 'Longer Description For An Item',
						],
						'_element' => 'description',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,

					],
					'labels' => [
						'de' => [
							'language' => 'de',
							'value' => 'foo',
						],
						'zh_classical' => [
							'language' => 'zh_classical',
							'value' => 'Longer Label',
						],
						'_element' => 'label',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					],
					'claims' => [
						'P65' => [
							[
								'id' => 'imaguid',
								'mainsnak' => [
									'snaktype' => 'value',
									'property' => 'P65',
									'datavalue' => [
										'value' => 'snakStringValue',
										'type' => 'string',
									],
									'datatype' => 'DtIdFor_P65',
								],
								'type' => 'statement',
								'qualifiers' => [
									'P65' => [
										[
											'hash' => '3ea0f5404dd4e631780b3386d17a15a583e499a6',
											'snaktype' => 'value',
											'property' => 'P65',
											'datavalue' => [
												'value' => 'string!',
												'type' => 'string',
											],
											'datatype' => 'DtIdFor_P65',
										],
										[
											'hash' => 'aa9a5f05e20d7fa5cda7d98371e44c0bdd5de35e',
											'snaktype' => 'somevalue',
											'property' => 'P65',
											'datatype' => 'DtIdFor_P65',
										],
										'_element' => 'qualifiers',
									],
									'_element' => 'property',
									'_type' => 'kvp',
									'_kvpkeyname' => 'id',
								],
								'rank' => 'normal',
								'qualifiers-order' => [
									'P65',
									'_element' => 'property',
								],
								'references' => [
									[
										'hash' => '8445204eb74e636cb53687e2f947c268d5186075',
										'snaks' => [
											'P65' => [
												[
													'snaktype' => 'somevalue',
													'property' => 'P65',
													'datatype' => 'DtIdFor_P65',
												],
												'_element' => 'snak',
											],
											'P68' => [
												[
													'snaktype' => 'somevalue',
													'property' => 'P68',
													'datatype' => 'DtIdFor_P68',
												],
												'_element' => 'snak',
											],
											'_element' => 'property',
											'_type' => 'kvp',
											'_kvpkeyname' => 'id',
										],
										'snaks-order' => [
											'P65',
											'P68',
											'_element' => 'property',
										],
									],
									'_element' => 'reference',
								],
							],
							'_element' => 'claim',
						],
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
					'sitelinks' => [
						'enwiki' => [
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => [
								'Q333',
								'_element' => 'badge',
							],
						],
						'zh_classicalwiki' => [
							'site' => 'zh_classicalwiki',
							'title' => 'User:Addshore',
							'badges' => [
								'_element' => 'badge',
							],
						],
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					],
				],
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	/**
	 * @dataProvider provideTestAddEntityRevision
	 */
	public function testAddEntityRevision( $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$item = new Item( new ItemId( 'Q123098' ) );

		//Basic
		$item->setLabel( 'de', 'foo' );
		$item->setLabel( 'zh_classical', 'Longer Label' );
		$item->setAliases( 'en', [ 'bar', 'baz' ] );
		$item->setAliases( 'zh', [ '????????' ] );
		$item->setDescription( 'pt', 'ptDesc' );
		$item->setDescription( 'pl', 'Longer Description For An Item' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin', [ new ItemId( 'Q333' ) ] );
		$item->getSiteLinkList()->addNewSiteLink( 'zh_classicalwiki', 'User:Addshore', [] );

		$snak = new PropertyValueSnak( new NumericPropertyId( 'P65' ), new StringValue( 'snakStringValue' ) );

		$qualifiers = new SnakList();
		$qualifiers->addSnak( new PropertyValueSnak( new NumericPropertyId( 'P65' ), new StringValue( 'string!' ) ) );
		$qualifiers->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P65' ) ) );

		$references = new ReferenceList();
		$referenceSnaks = new SnakList();
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P65' ) ) );
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new NumericPropertyId( 'P68' ) ) );
		$references->addReference( new Reference( $referenceSnaks ) );

		$guid = 'imaguid';
		$item->getStatements()->addNewStatement( $snak, $qualifiers, $references, $guid );

		$entityRevision = new EntityRevision( $item, 33, '20131126202923' );

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addEntityRevision( 'Q1230000', $entityRevision );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionKey() {
		$item = new Item( new ItemId( 'Q11' ) );

		$entityRevision = new EntityRevision( $item, 33, '20131126202923' );

		$props = [];
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );

		// automatic key
		$resultBuilder->addEntityRevision( null, $entityRevision, $props );

		$data = $result->getResultData();
		$this->assertArrayHasKey( 'Q11', $data['entities'] );

		// explicit key
		$resultBuilder->addEntityRevision( 'FOO', $entityRevision, $props );

		$data = $result->getResultData();
		$this->assertArrayHasKey( 'FOO', $data['entities'] );
	}

	public function provideTestAddEntityRevisionFallback() {
		$expected = [
			'entities' => [
				'Q123101' => [
					'id' => 'Q123101',
					'type' => 'item',
					'labels' => [
						'de-formal' => [
							'language' => 'de',
							'value' => 'Oslo-de',
							'for-language' => 'de-formal',
						],
						'es' => [
							'language' => 'en',
							'value' => 'Oslo-en',
							'for-language' => 'es',
						],
						'qug' => [
							'language' => 'en',
							'value' => 'Oslo-en',
							'for-language' => 'qug',
						],
						'zh-my' => [
							'language' => 'en',
							'value' => 'Oslo-en',
							'for-language' => 'zh-my',
						],
						'_element' => 'label',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					],
					'descriptions' => [
						'es' => [
							'language' => 'es',
							'value' => 'desc-es',
						],
						'qug' => [
							'language' => 'es',
							'value' => 'desc-es',
							'for-language' => 'qug',
						],
						'zh-my' => [
							'language' => 'zh-my',
							'value' => 'desc-zh-sg',
							'source-language' => 'zh-sg',
						],
						'_element' => 'description',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					],
				],
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	/**
	 * @dataProvider provideTestAddEntityRevisionFallback
	 */
	public function testAddEntityRevisionFallback( $addMetaData, array $expected ) {
		$item = new Item( new ItemId( 'Q123101' ) );
		$item->getFingerprint()->setLabel( 'de', 'Oslo-de' );
		$item->getFingerprint()->setLabel( 'en', 'Oslo-en' );
		$item->getFingerprint()->setDescription( 'es', 'desc-es' );
		$item->getFingerprint()->setDescription( 'zh-sg', 'desc-zh-sg' );
		$entityRevision = new EntityRevision( $item );

		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$fallbackChains = [
			'de-formal' => $fallbackChainFactory->newFromLanguageCode( 'de-formal' ),
			'es' => $fallbackChainFactory->newFromLanguageCode( 'es' ),
			'qug' => $fallbackChainFactory->newFromLanguageCode( 'qug' ),
			'zh-my' => $fallbackChainFactory->newFromLanguageCode( 'zh-my' ),
		];
		$filterLangCodes = array_keys( $fallbackChains );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addEntityRevision(
			null,
			$entityRevision,
			[ 'labels', 'descriptions' ],
			[],
			$filterLangCodes,
			$fallbackChains
		);

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionWithLanguagesFilter() {
		$item = new Item( new ItemId( 'Q123099' ) );
		$item->setLabel( 'en', 'text' );
		$item->setLabel( 'de', 'text' );
		$item->setDescription( 'en', 'text' );
		$item->setDescription( 'de', 'text' );
		$item->setAliases( 'en', [ 'text' ] );
		$item->setAliases( 'de', [ 'text' ] );
		$entityRevision = new EntityRevision( $item );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision(
			null,
			$entityRevision,
			[ 'labels', 'descriptions', 'aliases' ],
			[],
			[ 'de' ]
		);

		$expected = [
			'entities' => [
				'Q123099' => [
					'id' => 'Q123099',
					'type' => 'item',
					'labels' => [
						'de' => [
							'language' => 'de',
							'value' => 'text',
						],
					],
					'descriptions' => [
						'de' => [
							'language' => 'de',
							'value' => 'text',
						],
					],
					'aliases' => [
						'de' => [
							[
								'language' => 'de',
								'value' => 'text',
							],
						],
					],
				],
			],
			// This meta data element is always present in ApiResult
			'_type' => 'assoc',
		];

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionWithSiteLinksFilter() {
		$item = new Item( new ItemId( 'Q123099' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$entityRevision = new EntityRevision( $item );

		$props = [ 'sitelinks' ];
		$siteIds = [ 'enwiki' ];

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision( null, $entityRevision, $props, $siteIds );

		$expected = [
			'entities' => [
				'Q123099' => [
					'id' => 'Q123099',
					'type' => 'item',
					'sitelinks' => [
						'enwiki' => [
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => [],
						],
					],
				],
			],
			// This meta data element is always present in ApiResult
			'_type' => 'assoc',
		];

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	/**
	 * @see https://phabricator.wikimedia.org/T68181
	 */
	public function testAddEntityRevisionInIndexedModeWithSiteLinksFilter() {
		$item = new Item( new ItemId( 'Q123100' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$entityRevision = new EntityRevision( $item );

		$props = [ 'sitelinks' ];
		$siteIds = [ 'enwiki' ];

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, true );
		$resultBuilder->addEntityRevision( null, $entityRevision, $props, $siteIds );

		$expected = [
			'entities' => [
				'Q123100' => [
					'id' => 'Q123100',
					'type' => 'item',
					'sitelinks' => [
						'enwiki' => [
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => [
								'_element' => 'badge',
							],
						],
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					],
				],
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			],
			'_type' => 'assoc',
		];

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddBasicEntityInformation() {
		$result = $this->getDefaultResult();
		$entityId = new ItemId( 'Q67' );
		$expected = [
			'entity' => [
				'id' => 'Q67',
				'type' => 'item',
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addBasicEntityInformation( $entityId, 'entity' );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddLabels() {
		$result = $this->getDefaultResult();
		$labels = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );
		$path = [ 'entities', 'Q1' ];
		$expected = [
			'entities' => [
				'Q1' => [
					'labels' => [
						'en' => [
							'language' => 'en',
							'value' => 'foo',
						],
						'de' => [
							'language' => 'de',
							'value' => 'bar',
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addLabels( $labels, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedLabel() {
		$result = $this->getDefaultResult();
		$path = [ 'entities', 'Q1' ];
		$expected = [
			'entities' => [
				'Q1' => [
					'labels' => [
						'en' => [
							'language' => 'en',
							'removed' => '',
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedLabel( 'en', $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddDescriptions() {
		$result = $this->getDefaultResult();
		$descriptions = new TermList( [
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		] );
		$path = [ 'entities', 'Q1' ];
		$expected = [
			'entities' => [
				'Q1' => [
					'descriptions' => [
						'en' => [
							'language' => 'en',
							'value' => 'foo',
						],
						'de' => [
							'language' => 'de',
							'value' => 'bar',
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addDescriptions( $descriptions, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedDescription() {
		$result = $this->getDefaultResult();
		$path = [ 'entities', 'Q1' ];
		$expected = [
			'entities' => [
				'Q1' => [
					'descriptions' => [
						'en' => [
							'language' => 'en',
							'removed' => '',
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedDescription( 'en', $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideAddAliasGroupList() {
		$expected = [
			'entities' => [
				'Q1' => [
					'aliases' => [
						'en' => [
							[
								'language' => 'en',
								'value' => 'boo',
							],
							[
								'language' => 'en',
								'value' => 'hoo',
							],
							'_element' => 'alias',
						],
						'de' => [
							[
								'language' => 'de',
								'value' => 'ham',
							],
							[
								'language' => 'de',
								'value' => 'cheese',
							],
							'_element' => 'alias',
						],
						'_element' => 'language',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
				],
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	/**
	 * @dataProvider provideAddAliasGroupList
	 */
	public function testAddAliasGroupList( $metaData, array $expected ) {
		$result = $this->getDefaultResult();
		$aliasGroupList = new AliasGroupList(
			[
				new AliasGroup( 'en', [ 'boo', 'hoo' ] ),
				new AliasGroup( 'de', [ 'ham', 'cheese' ] ),
			]
		);
		$path = [ 'entities', 'Q1' ];

		$resultBuilder = $this->getResultBuilder( $result, $metaData );
		$resultBuilder->addAliasGroupList( $aliasGroupList, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideAddSiteLinkList() {
		$expected = [
			'entities' => [
				'Q1' => [
					'sitelinks' => [
						'enwiki' => [
							'site' => 'enwiki',
							'title' => 'User:Addshore',
							'badges' => [ '_element' => 'badge' ],
						],
						'dewikivoyage' => [
							'site' => 'dewikivoyage',
							'title' => 'Berlin',
							'badges' => [ '_element' => 'badge' ],
						],
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					],
				],
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	/**
	 * @dataProvider provideAddSiteLinkList
	 */
	public function testAddSiteLinkList( $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$siteLinkList = new SiteLinkList(
			[
				new SiteLink( 'enwiki', 'User:Addshore' ),
				new SiteLink( 'dewikivoyage', 'Berlin' ),
			]
		);
		$path = [ 'entities', 'Q1' ];

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addSiteLinkList( $siteLinkList, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedSiteLinks() {
		//TODO test with metadata....
		$result = $this->getDefaultResult();
		$siteLinkList = new SiteLinkList( [
			new SiteLink( 'enwiki', 'User:Addshore' ),
			new SiteLink( 'dewikivoyage', 'Berlin' ),
		] );
		$path = [ 'entities', 'Q1' ];
		$expected = [
			'entities' => [
				'Q1' => [
					'sitelinks' => [
						'enwiki' => [
							'site' => 'enwiki',
							'title' => 'User:Addshore',
							'removed' => '',
							'badges' => [],
						],
						'dewikivoyage' => [
							'site' => 'dewikivoyage',
							'title' => 'Berlin',
							'removed' => '',
							'badges' => [],
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedSiteLinks( $siteLinkList, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddAndRemoveSiteLinks() {
		$result = $this->getDefaultResult();
		$siteLinkListAdd = new SiteLinkList(
			[
				new SiteLink( 'enwiki', 'User:Addshore' ),
				new SiteLink( 'dewikivoyage', 'Berlin' ),
			]
		);
		$siteLinkListRemove = new SiteLinkList( [
			new SiteLink( 'ptwiki', 'Port' ),
			new SiteLink( 'dewiki', 'Gin' ),
		] );
		$path = [ 'entities', 'Q1' ];
		$expected = [
			'entities' => [
				'Q1' => [
					'sitelinks' => [
						'enwiki' => [
							'site' => 'enwiki',
							'title' => 'User:Addshore',
							'badges' => [],
						],
						'dewikivoyage' => [
							'site' => 'dewikivoyage',
							'title' => 'Berlin',
							'badges' => [],
						],
						'ptwiki' => [
							'site' => 'ptwiki',
							'title' => 'Port',
							'removed' => '',
							'badges' => [],
						],
						'dewiki' => [
							'site' => 'dewiki',
							'title' => 'Gin',
							'removed' => '',
							'badges' => [],
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addSiteLinkList( $siteLinkListAdd, $path );
		$resultBuilder->addRemovedSiteLinks( $siteLinkListRemove, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	/**
	 * @dataProvider statementSerializationProvider
	 */
	public function testAddStatements( Statement $statement, $addMetaData, array $statementSerialization ) {
		$result = $this->getDefaultResult();
		$path = [ 'entities', 'Q1' ];

		$expected = [
			'entities' => [
				'Q1' => [
					'claims' => [
						'P12' => [
							$statementSerialization,
							'_element' => 'claim',
						],
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
				],
			],
			'_type' => 'assoc',
		];

		if ( !$addMetaData ) {
			$expected = $this->removeMetaData( $expected );
			$expected['_type'] = 'assoc';
		}

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addStatements( new StatementList( $statement ), $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddStatementsNoProps() {
		$result = $this->getDefaultResult();
		$path = [ 'entities', 'Q1' ];

		$statement = new Statement(
			new PropertySomeValueSnak( new NumericPropertyId( 'P12' ) ),
			null,
			new Referencelist( [
				new Reference( [
					new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
				] ),
			] ),
			'fooguidbar'
		);

		$expected = [
			'entities' => [
				'Q1' => [
					'claims' => [
						'P12' => [
							[
								'id' => 'fooguidbar',
								'mainsnak' => [
									'snaktype' => 'somevalue',
									'property' => 'P12',
									'datatype' => 'DtIdFor_P12',
								],
								'type' => 'statement',
								'rank' => 'normal',
							],
						],
					],
				],
			],
			'_type' => 'assoc',
		];

		$props = [];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addStatements( new StatementList( $statement ), $path, $props );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	/**
	 * @dataProvider statementSerializationProvider
	 */
	public function testAddStatement( Statement $statement, $addMetaData, array $statementSerialization ) {
		$result = $this->getDefaultResult();
		$expected = [
			'claim' => $statementSerialization,
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addStatement( $statement );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function statementSerializationProvider() {
		$statement = new Statement(
			new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'stringVal' ) ),
			new SnakList( [
				new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'qualiferVal' ) ),
			] ),
			new Referencelist( [
				new Reference( [
					new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
				] ),
			] ),
			'fooguidbar'
		);

		$expected = [
			'id' => 'fooguidbar',
			'mainsnak' => [
				'snaktype' => 'value',
				'property' => 'P12',
				'datavalue' => [
					'value' => 'stringVal',
					'type' => 'string',
				],
				'datatype' => 'DtIdFor_P12',
			],
			'type' => 'statement',
			'rank' => 'normal',
			'qualifiers-order' => [
				'P12',
				'_element' => 'property',
			],
			'references' => [
				[
					'hash' => '4db26028db87a994581ef9cd832e60635321bca9',
					'snaks' => [
						'P12' => [
							[
								'snaktype' => 'value',
								'property' => 'P12',
								'datatype' => 'DtIdFor_P12',
								'datavalue' => [
									'value' => 'refSnakVal',
									'type' => 'string',
								],
							],
							'_element' => 'snak',
						],
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
					'snaks-order' => [
						'P12',
						'_element' => 'property',
					],
				],
				'_element' => 'reference',
			],
			'qualifiers' => [
				'P12' => [
					[
						'snaktype' => 'value',
						'property' => 'P12',
						'datatype' => 'DtIdFor_P12',
						'datavalue' => [
							'value' => 'qualiferVal',
							'type' => 'string',
						],
						'hash' => '16c37f4d851c37f7495a31ebc539e52227918a5e',
					],
					'_element' => 'qualifiers',
				],
				'_element' => 'property',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
			],
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );

		return [
			[ $statement, false, $expectedNoMetaData ],
			[ $statement, true, $expected ],
		];
	}

	public function provideAddReference() {
		$expected = [
			'reference' => [
				'hash' => '27ff8ea8cc011639f959481465c175fe7f07ecbd',
				'snaks' => [
					'P12' => [
						[
							'snaktype' => 'value',
							'property' => 'P12',
							'datavalue' => [
								'value' => 'stringVal',
								'type' => 'string',
							],
							'datatype' => 'DtIdFor_P12',
						],
						'_element' => 'snak',
					],
					'_element' => 'property',
					'_type' => 'kvp',
					'_kvpkeyname' => 'id',
				],
				'snaks-order' => [
					'P12',
					'_element' => 'property',
				],
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	/**
	 * @dataProvider provideAddReference
	 */
	public function testAddReference( $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$reference = new Reference(
			new SnakList( [
				new PropertyValueSnak( new NumericPropertyId( 'P12' ), new StringValue( 'stringVal' ) ),
			] )
		);

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addReference( $reference );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	/**
	 * @dataProvider provideMissingEntity
	 */
	public function testAddMissingEntityWithMetaData( array $missingEntities, array $expected ) {
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, true );

		foreach ( $missingEntities as $key => $missingDetails ) {
			if ( is_int( $key ) ) {
				// string keys are kept for use in the result structure, integer keys aren't
				$key = null;
			}

			$resultBuilder->addMissingEntity( $key, $missingDetails );
		}

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideMissingEntity() {
		return [
			[
				[
					[ 'site' => 'enwiki', 'title' => 'Berlin' ],
				],
				[
					'entities' => [
						'-1' => [
							'site' => 'enwiki',
							'title' => 'Berlin',
							'missing' => '',
						],
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					],
					'_type' => 'assoc',
				],
			],
			[
				[
					[ 'id' => 'Q77' ],
				],
				[
					'entities' => [
						'Q77' => [
							'id' => 'Q77',
							'missing' => '',
						],
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					],
					'_type' => 'assoc',
				],
			],
			[
				[
					'Q77' => [ 'foo' => 'bar' ],
				],
				[
					'entities' => [
						'Q77' => [
							'foo' => 'bar',
							'missing' => '',
						],
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					],
					'_type' => 'assoc',
				],
			],
			[
				[
					[ 'site' => 'enwiki', 'title' => 'Berlin' ],
					[ 'site' => 'dewiki', 'title' => 'Foo' ],
				],
				[
					'entities' => [
						'-1' => [
							'site' => 'enwiki',
							'title' => 'Berlin',
							'missing' => '',
						],
						'-2' => [
							'site' => 'dewiki',
							'title' => 'Foo',
							'missing' => '',
						],
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					],
					'_type' => 'assoc',
				],
			],
		];
	}

	public function testAddNormalizedTitle() {
		$result = $this->getDefaultResult();
		$from = 'berlin';
		$to = 'Berlin';
		$expected = [
			'normalized' => [
				'n' => [
					'from' => 'berlin',
					'to' => 'Berlin',
				],
			],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addNormalizedTitle( $from, $to );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRevisionIdFromStatusToResult() {
		$result = $this->getDefaultResult();
		$mockRevision = $this->createMock( EntityRevision::class );
		$mockRevision->expects( $this->once() )
			->method( 'getRevisionId' )
			->willReturn( 123 );
		$mockStatus = $this->createMock( Status::class );
		$mockStatus->expects( $this->once() )
			->method( 'getValue' )
			->willReturn( [ 'revision' => $mockRevision ] );
		$expected = [
			'entity' => [ 'lastrevid' => '123' ],
			'_type' => 'assoc',
		];

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRevisionIdFromStatusToResult( $mockStatus, 'entity' );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideSetList() {
		return [
			'null path' => [ null, 'foo', [], 'letter', false,
				[ 'foo' => [], '_type' => 'assoc' ],
			],
			'empty path' => [ [], 'foo', [ 'x', 'y' ], 'letter', false,
				[
					'foo' => [ 'x', 'y' ], '_type' => 'assoc',
				],
			],
			'string path' => [ 'ROOT', 'foo', [ 'x', 'y' ], 'letter', false,
				[
					'ROOT' => [
						'foo' => [ 'x', 'y' ],
					],
					'_type' => 'assoc',
				],
			],
			'actual path' => [ [ 'one', 'two' ], 'foo', [ 'X' => 'x', 'Y' => 'y' ], 'letter', false,
				[
					'one' => [
						'two' => [
							'foo' => [ 'X' => 'x', 'Y' => 'y' ],
						],
					],
					'_type' => 'assoc',
				],
			],
			'indexed' => [ 'ROOT', 'foo', [ 'X' => 'x', 'Y' => 'y' ], 'letter', true,
				[
					'ROOT' => [
						'foo' => [ 'X' => 'x', 'Y' => 'y', '_element' => 'letter', '_type' => 'array' ],
					],
					'_type' => 'assoc',
				],
			],
			'pre-set element name' => [ 'ROOT', 'foo', [ 'x', 'y', '_element' => '_thingy' ], 'letter', true,
				[
					'ROOT' => [
						'foo' => [ 'x', 'y', '_element' => '_thingy', '_type' => 'array' ],
					],
					'_type' => 'assoc',
				],
			],
		];
	}

	/**
	 * @dataProvider provideSetList
	 */
	public function testSetList( $path, $name, array $values, $tag, $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result, $addMetaData );

		$builder->setList( $path, $name, $values, $tag );
		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideSetList_InvalidArgument() {
		return [
			'null name' => [ 'ROOT', null, [ 10, 20 ], 'Q' ],
			'int name' => [ 'ROOT', 6, [ 10, 20 ], 'Q' ],
			'array name' => [ 'ROOT', [ 'x' ], [ 10, 20 ], 'Q' ],

			'null tag' => [ 'ROOT', 'foo', [ 10, 20 ], null ],
			'int tag' => [ 'ROOT', 'foo', [ 10, 20 ], 6 ],
			'array tag' => [ 'ROOT', 'foo', [ 10, 20 ], [ 'x' ] ],
		];
	}

	/**
	 * @dataProvider provideSetList_InvalidArgument
	 */
	public function testSetList_InvalidArgument( $path, $name, array $values, $tag ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result );

		$this->expectException( InvalidArgumentException::class );
		$builder->setList( $path, $name, $values, $tag );
	}

	public function provideSetValue() {
		return [
			'null path' => [ null, 'foo', 'value', false, [ 'foo' => 'value', '_type' => 'assoc' ] ],
			'empty path' => [ [], 'foo', 'value', false,
				[
					'foo' => 'value',
					'_type' => 'assoc',
				],
			],
			'string path' => [ 'ROOT', 'foo', 'value', false,
				[
					'ROOT' => [ 'foo' => 'value' ],
					'_type' => 'assoc',
				],
			],
			'actual path' => [ [ 'one', 'two' ], 'foo', [ 'X' => 'x', 'Y' => 'y' ], true,
				[
					'one' => [
						'two' => [
							'foo' => [ 'X' => 'x', 'Y' => 'y' ],
						],
					],
					'_type' => 'assoc',
				],
			],
			'indexed' => [ 'ROOT', 'foo', 'value', true,
				[
					'ROOT' => [ 'foo' => 'value' ],
					'_type' => 'assoc',
				],
			],
		];
	}

	/**
	 * @dataProvider provideSetValue
	 */
	public function testSetValue( $path, $name, $value, $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result, $addMetaData );

		$builder->setValue( $path, $name, $value );
		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideSetValue_InvalidArgument() {
		return [
			'null name' => [ 'ROOT', null, 'X' ],
			'int name' => [ 'ROOT', 6, 'X' ],
			'array name' => [ 'ROOT', [ 'x' ], 'X' ],

			'list value' => [ 'ROOT', 'foo', [ 10, 20 ] ],
		];
	}

	/**
	 * @dataProvider provideSetValue_InvalidArgument
	 */
	public function testSetValue_InvalidArgument( $path, $name, $value ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result );

		$this->expectException( InvalidArgumentException::class );
		$builder->setValue( $path, $name, $value );
	}

	public function provideAppendValue() {
		return [
			'null path' => [ null, null, 'value', 'letter', false,
				[ 'value', '_type' => 'assoc' ],
			],
			'empty path' => [ [], null, 'value', 'letter', false,
				[ 'value', '_type' => 'assoc' ],
			],
			'string path' => [ 'ROOT', null, 'value', 'letter', false,
				[
					'ROOT' => [ 'value' ],
					'_type' => 'assoc',
				],
			],
			'actual path' => [ [ 'one', 'two' ], null, [ 'X' => 'x', 'Y' => 'y' ], 'letter', false,
				[
					'one' => [
						'two' => [ [ 'X' => 'x', 'Y' => 'y' ] ],
					],
					'_type' => 'assoc',
				],
			],
			'int key' => [ 'ROOT', -2, 'value', 'letter', false,
				[
					'ROOT' => [ -2 => 'value' ],
					'_type' => 'assoc',
				],
			],
			'string key' => [ 'ROOT', 'Q7', 'value', 'letter', false,
				[
					'ROOT' => [ 'Q7' => 'value' ],
					'_type' => 'assoc',
				],
			],
			'null key indexed' => [ 'ROOT', null, 'value', 'letter', true,
				[
					'ROOT' => [ 'value', '_element' => 'letter' ],
					'_type' => 'assoc',
				],
			],
			'int key indexed' => [ 'ROOT', -2, 'value', 'letter', true,
				[
					'ROOT' => [ -2 => 'value', '_element' => 'letter' ],
					'_type' => 'assoc',
				],
			],
			'string key indexed' => [ 'ROOT', 'Q7', 'value', 'letter', true,
				[
					'ROOT' => [ 'Q7' => 'value', '_element' => 'letter' ],
					'_type' => 'assoc',
				],
			],
		];
	}

	/**
	 * @dataProvider provideAppendValue
	 */
	public function testAppendValue( $path, $key, $value, $tag, $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result, $addMetaData );

		$builder->appendValue( $path, $key, $value, $tag );
		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideAppendValue_InvalidArgument() {
		return [
			'list value' => [ 'ROOT', null, [ 1, 2, 3 ], 'Q' ],
			'array key' => [ 'ROOT', [ 'x' ], 'value', 'Q' ],

			'null tag' => [ 'ROOT', 'foo', 'value', null ],
			'int tag' => [ 'ROOT', 'foo', 'value', 6 ],
			'array tag' => [ 'ROOT', 'foo', 'value', [ 'x' ] ],
		];
	}

	/**
	 * @dataProvider provideAppendValue_InvalidArgument
	 */
	public function testAppendValue_InvalidArgument( $path, $key, $value, $tag ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result );

		$this->expectException( InvalidArgumentException::class );
		$builder->appendValue( $path, $key, $value, $tag );
	}

	public function provideTestEmptyListsMetaData() {
		$expected = [
			'entities' => [
				'Q123000' => [
					'pageid' => 123, //mocked
					'ns' => 456, //mocked
					'title' => 'MockPrefixedText', //mocked
					'lastrevid' => 33,
					'modified' => '2013-11-26T20:29:23Z',
					'type' => 'item',
					'id' => 'Q123000',
					'aliases' => [
						'_element' => 'language',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
					'descriptions' => [
						'_element' => 'description',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					],
					'labels' => [
						'_element' => 'label',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					],
					'claims' => [
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					],
					'sitelinks' => [
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					],
				],
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			],
			'_type' => 'assoc',
		];

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return [
			[ false, $expectedNoMetaData ],
			[ true, $expected ],
		];
	}

	/**
	 * @dataProvider provideTestEmptyListsMetaData
	 */
	public function testEmptyLists( $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$item = new Item( new ItemId( 'Q123000' ) );

		$entityRevision = new EntityRevision( $item, 33, '20131126202923' );

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addEntityRevision( 'Q123000', $entityRevision );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}
}
