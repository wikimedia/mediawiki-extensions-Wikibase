<?php

namespace Wikibase\Test\Repo\Api;

use ApiResult;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use HashSiteStore;
use InvalidArgumentException;
use Revision;
use Status;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
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
use Wikibase\EntityRevision;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\ResultBuilder;

/**
 * @covers Wikibase\Repo\Api\ResultBuilder
 * @todo mock and inject serializers to avoid massive expected output?
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Addshore
 * @author Thiemo Mättig
 */
class ResultBuilderTest extends \PHPUnit_Framework_TestCase {

	private function getDefaultResult() {
		return new ApiResult( false );
	}

	private function getResultBuilder( ApiResult $result, $addMetaData = false ) {
		$mockTitle = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$mockTitle->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 123 ) );
		$mockTitle->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 456 ) );
		$mockTitle->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'MockPrefixedText' ) );

		$mockEntityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$mockEntityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $mockTitle ) );

		$mockPropertyDataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );
		$mockPropertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return 'DtIdFor_' . $id->getSerialization();
			} ) );

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$builder = new ResultBuilder(
			$result,
			$mockEntityTitleLookup,
			$serializerFactory,
			$serializerFactory->newEntitySerializer(),
			new HashSiteStore(),
			$mockPropertyDataTypeLookup,
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
			if ( substr( $key, 0, 1 ) === '_' ) {
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
			array(
				'success' => $expected,
				'_type' => 'assoc',
			),
			$data
		);
	}

	public function provideMarkResultSuccess() {
		return array(
			array( true, 1 ),
			array( 1, 1 ),
			array( false, 0 ),
			array( 0, 0 ),
			array( null, 0 ),
		);
	}

	/**
	 * @dataProvider provideMarkResultSuccessExceptions
	 */
	public function testMarkResultSuccessExceptions( $param ) {
		$this->setExpectedException( InvalidArgumentException::class );
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
	}

	public function provideMarkResultSuccessExceptions() {
		return array( array( 3 ), array( -1 ) );
	}

	public function provideTestAddEntityRevision() {
		$expected = array(
			'entities' => array(
				'Q1230000' => array(
					'pageid' => 123, //mocked
					'ns' => 456, //mocked
					'title' => 'MockPrefixedText', //mocked
					'id' => 'Q123098',
					'type' => 'item',
					'lastrevid' => 33,
					'modified' => '2013-11-26T20:29:23Z',
					'redirects' => array(
						'from' => 'Q1230000',
						'to' => 'Q123098',
					),
					'aliases' => array(
						'en' => array(
							array(
								'language' => 'en',
								'value' => 'bar',
							),
							array(
								'language' => 'en',
								'value' => 'baz',
							),
							'_element' => 'alias',
						),
						'zh' => array(
							array(
								'language' => 'zh',
								'value' => '????????',
							),
							'_element' => 'alias',
						),
						'_element' => 'language',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					),
					'descriptions' => array(
						'pt' => array(
							'language' => 'pt',
							'value' => 'ptDesc'
						),
						'pl' => array(
							'language' => 'pl',
							'value' => 'Longer Description For An Item'
						),
						'_element' => 'description',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,

					),
					'labels' => array(
						'de' => array(
							'language' => 'de',
							'value' => 'foo'
						),
						'zh_classical' => array(
							'language' => 'zh_classical',
							'value' => 'Longer Label'
						),
						'_element' => 'label',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					),
					'claims' => array(
						'P65' => array(
							array(
								'id' => 'imaguid',
								'mainsnak' => array(
									'snaktype' => 'value',
									'property' => 'P65',
									'datavalue' => array(
										'value' => 'snakStringValue',
										'type' => 'string',
									),
									'datatype' => 'DtIdFor_P65',
								),
								'type' => 'statement',
								'qualifiers' => array(
									'P65' => array(
										array(
											'hash' => 'e95e866e7fa1c18bd06dae9b712cb99545107eb8',
											'snaktype' => 'value',
											'property' => 'P65',
											'datavalue' => array(
												'value' => 'string!',
												'type' => 'string',
											),
											'datatype' => 'DtIdFor_P65',
										),
										array(
											'hash' => '210b00274bf03247a89de918f15b12142ebf9e56',
											'snaktype' => 'somevalue',
											'property' => 'P65',
											'datatype' => 'DtIdFor_P65',
										),
										'_element' => 'qualifiers',
									),
									'_element' => 'property',
									'_type' => 'kvp',
									'_kvpkeyname' => 'id',
								),
								'rank' => 'normal',
								'qualifiers-order' => array(
									'P65',
									'_element' => 'property',
								),
								'references' => array(
									array(
										'hash' => 'bdc5f7185904d6d3219e13b7443571dda8c4bee8',
										'snaks' => array(
											'P65' => array(
												array(
													'snaktype' => 'somevalue',
													'property' => 'P65',
													'datatype' => 'DtIdFor_P65',
												),
												'_element' => 'snak',
											),
											'P68' => array(
												array(
													'snaktype' => 'somevalue',
													'property' => 'P68',
													'datatype' => 'DtIdFor_P68',
												),
												'_element' => 'snak',
											),
											'_element' => 'property',
											'_type' => 'kvp',
											'_kvpkeyname' => 'id',
										),
										'snaks-order' => array(
											'P65',
											'P68',
											'_element' => 'property',
										)
									),
									'_element' => 'reference',
								),
							),
							'_element' => 'claim',
						),
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					),
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => array(
								'Q333',
								'_element' => 'badge',
							)
						),
						'zh_classicalwiki' => array(
							'site' => 'zh_classicalwiki',
							'title' => 'User:Addshore',
							'badges' => array(
								'_element' => 'badge',
							)
						),
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					),
				),
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			),
			'_type' => 'assoc',
		);

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return array(
			array( false, $expectedNoMetaData ),
			array( true, $expected ),
		);
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
		$item->setAliases( 'en', array( 'bar', 'baz' ) );
		$item->setAliases( 'zh', array( '????????' ) );
		$item->setDescription( 'pt', 'ptDesc' );
		$item->setDescription( 'pl', 'Longer Description For An Item' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q333' ) ) );
		$item->getSiteLinkList()->addNewSiteLink( 'zh_classicalwiki', 'User:Addshore', array() );

		$snak = new PropertyValueSnak( new PropertyId( 'P65' ), new StringValue( 'snakStringValue' ) );

		$qualifiers = new SnakList();
		$qualifiers->addSnak( new PropertyValueSnak( new PropertyId( 'P65' ), new StringValue( 'string!' ) ) );
		$qualifiers->addSnak( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );

		$references = new ReferenceList();
		$referenceSnaks = new SnakList();
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new PropertyId( 'P68' ) ) );
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

		$props = array();
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
		$expected = array(
			'entities' => array(
				'Q123101' => array(
					'id' => 'Q123101',
					'type' => 'item',
					'labels' => array(
						'de-formal' => array(
							'language' => 'de',
							'value' => 'Oslo-de',
							'for-language' => 'de-formal'
						),
						'es' => array(
							'language' => 'en',
							'value' => 'Oslo-en',
							'for-language' => 'es',
						),
						'qug' => array(
							'language' => 'en',
							'value' => 'Oslo-en',
							'for-language' => 'qug'
						),
						'zh-my' => array(
							'language' => 'en',
							'value' => 'Oslo-en',
							'for-language' => 'zh-my'
						),
						'_element' => 'label',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					),
					'descriptions' => array(
						'es' => array(
							'language' => 'es',
							'value' => 'desc-es',
						),
						'qug' => array(
							'language' => 'es',
							'value' => 'desc-es',
							'for-language' => 'qug'
						),
						'zh-my' => array(
							'language' => 'zh-my',
							'value' => 'desc-zh-sg',
							'source-language' => 'zh-sg',
						),
						'_element' => 'description',
						'_type' => 'kvp',
						'_kvpkeyname' => 'language',
						'_kvpmerge' => true,
					),
				),
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			),
			'_type' => 'assoc',
		);

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return array(
			array( false, $expectedNoMetaData ),
			array( true, $expected ),
		);
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
		$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL;
		$fallbackChains = array(
			'de-formal' => $fallbackChainFactory->newFromLanguageCode( 'de-formal', $fallbackMode ),
			'es' => $fallbackChainFactory->newFromLanguageCode( 'es', $fallbackMode ),
			'qug' => $fallbackChainFactory->newFromLanguageCode( 'qug', $fallbackMode ),
			'zh-my' => $fallbackChainFactory->newFromLanguageCode( 'zh-my', $fallbackMode ),
		);
		$filterLangCodes = array_keys( $fallbackChains );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addEntityRevision(
			null,
			$entityRevision,
			array( 'labels', 'descriptions' ),
			array(),
			$filterLangCodes,
			$fallbackChains
		);

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionWithLanguagesFilter() {
		$item = new Item( new ItemId( 'Q123099' ) );
		$item->getFingerprint()->setLabel( 'en', 'text' );
		$item->getFingerprint()->setLabel( 'de', 'text' );
		$item->getFingerprint()->setDescription( 'en', 'text' );
		$item->getFingerprint()->setDescription( 'de', 'text' );
		$item->getFingerprint()->setAliasGroup( 'en', array( 'text' ) );
		$item->getFingerprint()->setAliasGroup( 'de', array( 'text' ) );
		$entityRevision = new EntityRevision( $item );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision(
			null,
			$entityRevision,
			array( 'labels', 'descriptions', 'aliases' ),
			array(),
			array( 'de' )
		);

		$expected = array(
			'entities' => array(
				'Q123099' => array(
					'id' => 'Q123099',
					'type' => 'item',
					'labels' => array(
						'de' => array(
							'language' => 'de',
							'value' => 'text',
						),
					),
					'descriptions' => array(
						'de' => array(
							'language' => 'de',
							'value' => 'text',
						),
					),
					'aliases' => array(
						'de' => array(
							array(
								'language' => 'de',
								'value' => 'text',
							),
						),
					),
				),
			),
			// This meta data element is always present in ApiResult
			'_type' => 'assoc',
		);

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionWithSiteLinksFilter() {
		$item = new Item( new ItemId( 'Q123099' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$entityRevision = new EntityRevision( $item );

		$props = array( 'sitelinks' );
		$siteIds = array( 'enwiki' );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision( null, $entityRevision, $props, $siteIds );

		$expected = array(
			'entities' => array(
				'Q123099' => array(
					'id' => 'Q123099',
					'type' => 'item',
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => array(),
						),
					),
				),
			),
			// This meta data element is always present in ApiResult
			'_type' => 'assoc',
		);

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

		$props = array( 'sitelinks' );
		$siteIds = array( 'enwiki' );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, true );
		$resultBuilder->addEntityRevision( null, $entityRevision, $props, $siteIds );

		$expected = array(
			'entities' => array(
				'Q123100' => array(
					'id' => 'Q123100',
					'type' => 'item',
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => array(
								'_element' => 'badge',
							),
						),
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					),
				),
				'_element' => 'entity',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
				'_kvpmerge' => true,
			),
			'_type' => 'assoc',
		);

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddBasicEntityInformation() {
		$result = $this->getDefaultResult();
		$entityId = new ItemId( 'Q67' );
		$expected = array(
			'entity' => array(
				'id' => 'Q67',
				'type' => 'item',
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addBasicEntityInformation( $entityId, 'entity' );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddLabels() {
		$result = $this->getDefaultResult();
		$labels = new TermList( array(
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		) );
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'labels' => array(
						'en' => array(
							'language' => 'en',
							'value' => 'foo',
						),
						'de' => array(
							'language' => 'de',
							'value' => 'bar',
						),
					),
				),
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addLabels( $labels, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedLabel() {
		$result = $this->getDefaultResult();
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'labels' => array(
						'en' => array(
							'language' => 'en',
							'removed' => '',
						),
					),
				),
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedLabel( 'en', $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddDescriptions() {
		$result = $this->getDefaultResult();
		$descriptions = new TermList( array(
			new Term( 'en', 'foo' ),
			new Term( 'de', 'bar' ),
		) );
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'descriptions' => array(
						'en' => array(
							'language' => 'en',
							'value' => 'foo',
						),
						'de' => array(
							'language' => 'de',
							'value' => 'bar',
						),
					),
				),
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addDescriptions( $descriptions, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedDescription() {
		$result = $this->getDefaultResult();
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'descriptions' => array(
						'en' => array(
							'language' => 'en',
							'removed' => '',
						),
					),
				),
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedDescription( 'en', $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideAddAliasGroupList() {
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'aliases' => array(
						'en' => array(
							array(
								'language' => 'en',
								'value' => 'boo',
							),
							array(
								'language' => 'en',
								'value' => 'hoo',
							),
							'_element' => 'alias',
						),
						'de' => array(
							array(
								'language' => 'de',
								'value' => 'ham',
							),
							array(
								'language' => 'de',
								'value' => 'cheese',
							),
							'_element' => 'alias',
						),
						'_element' => 'language',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					),
				),
			),
			'_type' => 'assoc',
		);

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return array(
			array( false, $expectedNoMetaData ),
			array( true, $expected ),
		);
	}

	/**
	 * @dataProvider provideAddAliasGroupList
	 */
	public function testAddAliasGroupList( $metaData, array $expected ) {
		$result = $this->getDefaultResult();
		$aliasGroupList = new AliasGroupList(
			array(
				new AliasGroup( 'en', array( 'boo', 'hoo' ) ),
				new AliasGroup( 'de', array( 'ham', 'cheese' ) ),
			)
		);
		$path = array( 'entities', 'Q1' );

		$resultBuilder = $this->getResultBuilder( $result, $metaData );
		$resultBuilder->addAliasGroupList( $aliasGroupList, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideAddSiteLinkList() {
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'User:Addshore',
							'badges' => array( '_element' => 'badge' ),
						),
						'dewikivoyage' => array(
							'site' => 'dewikivoyage',
							'title' => 'Berlin',
							'badges' => array( '_element' => 'badge' ),
						),
						'_element' => 'sitelink',
						'_type' => 'kvp',
						'_kvpkeyname' => 'site',
						'_kvpmerge' => true,
					),
				),
			),
			'_type' => 'assoc',
		);

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return array(
			array( false, $expectedNoMetaData ),
			array( true, $expected ),
		);
	}

	/**
	 * @dataProvider provideAddSiteLinkList
	 */
	public function testAddSiteLinkList( $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$siteLinkList = new SiteLinkList(
			array(
				new SiteLink( 'enwiki', 'User:Addshore' ),
				new SiteLink( 'dewikivoyage', 'Berlin' ),
			)
		);
		$path = array( 'entities', 'Q1' );

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addSiteLinkList( $siteLinkList, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedSiteLinks() {
		//TODO test with metadata....
		$result = $this->getDefaultResult();
		$siteLinkList = new SiteLinkList( array(
			new SiteLink( 'enwiki', 'User:Addshore' ),
			new SiteLink( 'dewikivoyage', 'Berlin' ),
		) );
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'User:Addshore',
							'removed' => '',
							'badges' => array(),
						),
						'dewikivoyage' => array(
							'site' => 'dewikivoyage',
							'title' => 'Berlin',
							'removed' => '',
							'badges' => array(),
						),
					),
				),
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedSiteLinks( $siteLinkList, $path );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddAndRemoveSiteLinks() {
		$result = $this->getDefaultResult();
		$siteLinkListAdd = new SiteLinkList(
			array(
				new SiteLink( 'enwiki', 'User:Addshore' ),
				new SiteLink( 'dewikivoyage', 'Berlin' ),
			)
		);
		$siteLinkListRemove = new SiteLinkList( array(
			new SiteLink( 'ptwiki', 'Port' ),
			new SiteLink( 'dewiki', 'Gin' ),
		) );
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'User:Addshore',
							'badges' => array(),
						),
						'dewikivoyage' => array(
							'site' => 'dewikivoyage',
							'title' => 'Berlin',
							'badges' => array(),
						),
						'ptwiki' => array(
							'site' => 'ptwiki',
							'title' => 'Port',
							'removed' => '',
							'badges' => array(),
						),
						'dewiki' => array(
							'site' => 'dewiki',
							'title' => 'Gin',
							'removed' => '',
							'badges' => array(),
						),
					),
				),
			),
			'_type' => 'assoc',
		);

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
		$path = array( 'entities', 'Q1' );

		$expected = array(
			'entities' => array(
				'Q1' => array(
					'claims' => array(
						'P12' => array(
							$statementSerialization,
							'_element' => 'claim',
						),
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					),
				),
			),
			'_type' => 'assoc',
		);

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
		$path = array( 'entities', 'Q1' );

		$statement = new Statement(
			new PropertySomeValueSnak( new PropertyId( 'P12' ) ),
			null,
			new Referencelist( array(
				new Reference( array(
					new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
				) ),
			) ),
			'fooguidbar'
		);

		$expected = array(
			'entities' => array(
				'Q1' => array(
					'claims' => array(
						'P12' => array(
							array(
								'id' => 'fooguidbar',
								'mainsnak' => array(
									'snaktype' => 'somevalue',
									'property' => 'P12',
									'datatype' => 'DtIdFor_P12',
								),
								'type' => 'statement',
								'rank' => 'normal',
							),
						),
					),
				),
			),
			'_type' => 'assoc',
		);

		$props = array();

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
		$expected = array(
			'claim' => $statementSerialization,
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result, $addMetaData );
		$resultBuilder->addStatement( $statement );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function statementSerializationProvider() {
		$statement = new Statement(
			new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) ),
			new SnakList( array(
				new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'qualiferVal' ) ),
			) ),
			new Referencelist( array(
				new Reference( array(
					new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'refSnakVal' ) ),
				) ),
			) ),
			'fooguidbar'
		);

		$expected = array(
			'id' => 'fooguidbar',
			'mainsnak' => array(
				'snaktype' => 'value',
				'property' => 'P12',
				'datavalue' => array(
					'value' => 'stringVal',
					'type' => 'string',
				),
				'datatype' => 'DtIdFor_P12',
			),
			'type' => 'statement',
			'rank' => 'normal',
			'qualifiers-order' => array(
				'P12',
				'_element' => 'property',
			),
			'references' => array(
				array(
					'hash' => '2f543336756784850a310cbc52a9307e467c7c42',
					'snaks' => array(
						'P12' => array(
							array(
								'snaktype' => 'value',
								'property' => 'P12',
								'datatype' => 'DtIdFor_P12',
								'datavalue' => array(
									'value' => 'refSnakVal',
									'type' => 'string',
								),
							),
							'_element' => 'snak',
						),
						'_element' => 'property',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
					),
					'snaks-order' => array(
						'P12',
						'_element' => 'property',
					),
				),
				'_element' => 'reference',
			),
			'qualifiers' => array(
				'P12' => array(
					array(
						'snaktype' => 'value',
						'property' => 'P12',
						'datatype' => 'DtIdFor_P12',
						'datavalue' => array(
							'value' => 'qualiferVal',
							'type' => 'string',
						),
						'hash' => '67423e8a140238decaa9156be1e3ba23513b3b19',
					),
					'_element' => 'qualifiers',
				),
				'_element' => 'property',
				'_type' => 'kvp',
				'_kvpkeyname' => 'id',
			),
		);

		$expectedNoMetaData = $this->removeMetaData( $expected );

		return array(
			array( $statement, false, $expectedNoMetaData ),
			array( $statement, true, $expected ),
		);
	}

	public function provideAddReference() {
		$expected = array(
			'reference' => array(
				'hash' => 'de52176deca6dd967b2a4122c96766089c1f793a',
				'snaks' => array(
					'P12' => array(
						array(
							'snaktype' => 'value',
							'property' => 'P12',
							'datavalue' => array(
								'value' => 'stringVal',
								'type' => 'string',
							),
							'datatype' => 'DtIdFor_P12',
						),
						'_element' => 'snak',
					),
					'_element' => 'property',
					'_type' => 'kvp',
					'_kvpkeyname' => 'id',
				),
				'snaks-order' => array(
					'P12',
					'_element' => 'property',
				),
			),
			'_type' => 'assoc',
		);

		$expectedNoMetaData = $this->removeMetaData( $expected );
		// The api always starts with this
		$expectedNoMetaData['_type'] = 'assoc';

		return array(
			array( false, $expectedNoMetaData ),
			array( true, $expected ),
		);
	}

	/**
	 * @dataProvider provideAddReference
	 */
	public function testAddReference( $addMetaData, array $expected ) {
		$result = $this->getDefaultResult();
		$reference = new Reference(
			new SnakList( array(
				new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) )
			) )
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
		return array(
			array(
				array(
					array( 'site' => 'enwiki', 'title' => 'Berlin' ),
				),
				array(
					'entities' => array(
						'-1' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'missing' => '',
						),
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					),
					'_type' => 'assoc',
				)
			),
			array(
				array(
					array( 'id' => 'Q77' ),
				),
				array(
					'entities' => array(
						'Q77' => array(
							'id' => 'Q77',
							'missing' => '',
						),
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					),
					'_type' => 'assoc',
				)
			),
			array(
				array(
					'Q77' => array( 'foo' => 'bar' ),
				),
				array(
					'entities' => array(
						'Q77' => array(
							'foo' => 'bar',
							'missing' => '',
						),
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					),
					'_type' => 'assoc',
				)
			),
			array(
				array(
					array( 'site' => 'enwiki', 'title' => 'Berlin' ),
					array( 'site' => 'dewiki', 'title' => 'Foo' ),
				),
				array(
					'entities' => array(
						'-1' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'missing' => '',
						),
						'-2' => array(
							'site' => 'dewiki',
							'title' => 'Foo',
							'missing' => '',
						),
						'_element' => 'entity',
						'_type' => 'kvp',
						'_kvpkeyname' => 'id',
						'_kvpmerge' => true,
					),
					'_type' => 'assoc',
				)
			),
		);
	}

	public function testAddNormalizedTitle() {
		$result = $this->getDefaultResult();
		$from = 'berlin';
		$to = 'Berlin';
		$expected = array(
			'normalized' => array(
				'n' => array(
					'from' => 'berlin',
					'to' => 'Berlin'
				),
			),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addNormalizedTitle( $from, $to );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function testAddRevisionIdFromStatusToResult() {
		$result = $this->getDefaultResult();
		$mockRevision = $this->getMockBuilder( Revision::class )
		->disableOriginalConstructor()
		->getMock();
		$mockRevision->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( 123 ) );
		$mockStatus = $this->getMock( Status::class );
		$mockStatus->expects( $this->once() )
			->method( 'getValue' )
			->will( $this->returnValue( array( 'revision' => $mockRevision ) ) );
		$expected = array(
			'entity' => array( 'lastrevid' => '123' ),
			'_type' => 'assoc',
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRevisionIdFromStatusToResult( $mockStatus, 'entity' );

		$data = $result->getResultData();

		$this->assertEquals( $expected, $data );
	}

	public function provideSetList() {
		return array(
			'null path' => array( null, 'foo', array(), 'letter', false,
				array( 'foo' => array(), '_type' => 'assoc' )
			),
			'empty path' => array( array(), 'foo', array( 'x', 'y' ), 'letter', false,
				array(
					'foo' => array( 'x', 'y' ), '_type' => 'assoc'
				)
			),
			'string path' => array( 'ROOT', 'foo', array( 'x', 'y' ), 'letter', false,
				array(
					'ROOT' => array(
						'foo' => array( 'x', 'y' )
					),
					'_type' => 'assoc',
				)
			),
			'actual path' => array( array( 'one', 'two' ), 'foo', array( 'X' => 'x', 'Y' => 'y' ), 'letter', false,
				array(
					'one' => array(
						'two' => array(
							'foo' => array( 'X' => 'x', 'Y' => 'y' )
						)
					),
					'_type' => 'assoc',
				)
			),
			'indexed' => array( 'ROOT', 'foo', array( 'X' => 'x', 'Y' => 'y' ), 'letter', true,
				array(
					'ROOT' => array(
						'foo' => array( 'X' => 'x', 'Y' => 'y', '_element' => 'letter', '_type' => 'array' ),
					),
					'_type' => 'assoc',
				),
			),
			'pre-set element name' => array( 'ROOT', 'foo', array( 'x', 'y', '_element' => '_thingy' ), 'letter', true,
				array(
					'ROOT' => array(
						'foo' => array( 'x', 'y', '_element' => '_thingy', '_type' => 'array' )
					),
					'_type' => 'assoc',
				)
			),
		);
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
		return array(
			'null name' => array( 'ROOT', null, array( 10, 20 ), 'Q' ),
			'int name' => array( 'ROOT', 6, array( 10, 20 ), 'Q' ),
			'array name' => array( 'ROOT', array( 'x' ), array( 10, 20 ), 'Q' ),

			'null tag' => array( 'ROOT', 'foo', array( 10, 20 ), null ),
			'int tag' => array( 'ROOT', 'foo', array( 10, 20 ), 6 ),
			'array tag' => array( 'ROOT', 'foo', array( 10, 20 ), array( 'x' ) ),
		);
	}

	/**
	 * @dataProvider provideSetList_InvalidArgument
	 */
	public function testSetList_InvalidArgument( $path, $name, array $values, $tag ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result );

		$this->setExpectedException( InvalidArgumentException::class );
		$builder->setList( $path, $name, $values, $tag );
	}

	public function provideSetValue() {
		return array(
			'null path' => array( null, 'foo', 'value', false, array( 'foo' => 'value', '_type' => 'assoc' ) ),
			'empty path' => array( array(), 'foo', 'value', false,
				array(
					'foo' => 'value',
					'_type' => 'assoc',
				)
			),
			'string path' => array( 'ROOT', 'foo', 'value', false,
				array(
					'ROOT' => array( 'foo' => 'value' ),
					'_type' => 'assoc'
				)
			),
			'actual path' => array( array( 'one', 'two' ), 'foo', array( 'X' => 'x', 'Y' => 'y' ), true,
				array(
					'one' => array(
						'two' => array(
							'foo' => array( 'X' => 'x', 'Y' => 'y' )
						)
					),
					'_type' => 'assoc'
				)
			),
			'indexed' => array( 'ROOT', 'foo', 'value', true,
				array(
					'ROOT' => array( 'foo' => 'value' ),
					'_type' => 'assoc'
				)
			),
		);
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
		return array(
			'null name' => array( 'ROOT', null, 'X' ),
			'int name' => array( 'ROOT', 6, 'X' ),
			'array name' => array( 'ROOT', array( 'x' ), 'X' ),

			'list value' => array( 'ROOT', 'foo', array( 10, 20 ) ),
		);
	}

	/**
	 * @dataProvider provideSetValue_InvalidArgument
	 */
	public function testSetValue_InvalidArgument( $path, $name, $value ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result );

		$this->setExpectedException( InvalidArgumentException::class );
		$builder->setValue( $path, $name, $value );
	}

	public function provideAppendValue() {
		return array(
			'null path' => array( null, null, 'value', 'letter', false,
				array( 'value', '_type' => 'assoc' ),
			),
			'empty path' => array( array(), null, 'value', 'letter', false,
				array( 'value', '_type' => 'assoc' )
			),
			'string path' => array( 'ROOT', null, 'value', 'letter', false,
				array(
					'ROOT' => array( 'value' ),
					'_type' => 'assoc'
				)
			),
			'actual path' => array( array( 'one', 'two' ), null, array( 'X' => 'x', 'Y' => 'y' ), 'letter', false,
				array(
					'one' => array(
						'two' => array( array( 'X' => 'x', 'Y' => 'y' ) ),
					),
					'_type' => 'assoc'
				)
			),
			'int key' => array( 'ROOT', -2, 'value', 'letter', false,
				array(
					'ROOT' => array( -2 => 'value' ),
					'_type' => 'assoc',
				)
			),
			'string key' => array( 'ROOT', 'Q7', 'value', 'letter', false,
				array(
					'ROOT' => array( 'Q7' => 'value' ),
					'_type' => 'assoc',
				)
			),
			'null key indexed' => array( 'ROOT', null, 'value', 'letter', true,
				array(
					'ROOT' => array( 'value', '_element' => 'letter' ),
					'_type' => 'assoc',
				)
			),
			'int key indexed' => array( 'ROOT', -2, 'value', 'letter', true,
				array(
					'ROOT' => array( -2 => 'value', '_element' => 'letter' ),
					'_type' => 'assoc',
				)
			),
			'string key indexed' => array( 'ROOT', 'Q7', 'value', 'letter', true,
				array(
					'ROOT' => array( 'Q7' => 'value', '_element' => 'letter' ),
					'_type' => 'assoc',
				)
			),
		);
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
		return array(
			'list value' => array( 'ROOT', null, array( 1, 2, 3 ), 'Q' ),
			'array key' => array( 'ROOT', array( 'x' ), 'value', 'Q' ),

			'null tag' => array( 'ROOT', 'foo', 'value', null ),
			'int tag' => array( 'ROOT', 'foo', 'value', 6 ),
			'array tag' => array( 'ROOT', 'foo', 'value', array( 'x' ) ),
		);
	}

	/**
	 * @dataProvider provideAppendValue_InvalidArgument
	 */
	public function testAppendValue_InvalidArgument( $path, $key, $value, $tag ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result );

		$this->setExpectedException( InvalidArgumentException::class );
		$builder->appendValue( $path, $key, $value, $tag );
	}

}
