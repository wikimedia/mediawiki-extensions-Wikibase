<?php

namespace Wikibase\Test\Repo\Api;

use ApiResult;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Serializers\EntitySerializer;
use Wikibase\Repo\Api\ResultBuilder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\LibSerializerFactory;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Repo\Api\ResultBuilder
 * @todo mock and inject serializers to avoid massive expected output?
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author Thiemo Mättig
 */
class ResultBuilderTest extends \PHPUnit_Framework_TestCase {

	protected function getDefaultResult() {
		return new ApiResult( false );
	}

	protected function getResultBuilder( $result, $options = null, $isRawMode = false ) {
		$mockTitle = $this->getMockBuilder( '\Title' )
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

		$mockEntityTitleLookup = $this->getMock( '\Wikibase\Lib\Store\EntityTitleLookup' );
		$mockEntityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $mockTitle ) );

		$mockPropertyDataTypeLookup = $this->getMock( '\Wikibase\DataModel\Entity\PropertyDataTypeLookup' );
		$mockPropertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( function( PropertyId $propertyId ) {
				return 'DtIdFor_' . $propertyId->getSerialization();
			} ) );

		// @todo inject EntityFactory and SiteStore
		$libSerializerFactory = new LibSerializerFactory(
			null, //no serialization options
			$mockPropertyDataTypeLookup
		);
		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH
		);

		$builder = new ResultBuilder(
			$result,
			$mockEntityTitleLookup,
			$libSerializerFactory,
			$serializerFactory,
			new MockSiteStore(),
			$mockPropertyDataTypeLookup,
			$isRawMode
		);

		if ( is_array( $options ) ) {
			$builder->getOptions()->setOptions( $options );
		} elseif ( $options instanceof SerializationOptions ) {
			$builder->getOptions()->merge( $options );
		}

		return $builder;
	}

	public function testCanConstruct() {
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$this->assertInstanceOf( '\Wikibase\Repo\Api\ResultBuilder', $resultBuilder );
	}

	/**
	 * @dataProvider provideBadConstructionData
	 */
	public function testBadConstruction( $result ) {
		$this->setExpectedException( 'InvalidArgumentException' );
		$this->getResultBuilder( $result );
	}

	public function provideBadConstructionData() {
		return array(
			array( null ),
			array( 1234 ),
			array( "imastring" ),
			array( array() ),
		);
	}

	/**
	 * @dataProvider provideMarkResultSuccess
	 */
	public function testMarkResultSuccess( $param, $expected ) {
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
		$data = $result->getResultData();

		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( array( 'success' => $expected ), $data );
	}

	public function provideMarkResultSuccess() {
		return array( array( true, 1 ), array( 1, 1 ), array( false, 0 ), array( 0, 0 ), array( null, 0 ) );
	}

	/**
	 * @dataProvider provideMarkResultSuccessExceptions
	 */
	public function testMarkResultSuccessExceptions( $param ) {
		$this->setExpectedException( 'InvalidArgumentException' );
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
								'value' => 'bar'
							),
							array(
								'language' => 'en',
								'value' => 'baz'
							)
						),
						'zh' => array(
							array(
								'language' => 'zh',
								'value' => '????????',
							),
						),
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
					),
					'claims' => array(
						'P65' => array(
							array(
								'id' => 'imaguid',
								'mainsnak' => array(
									'snaktype' => 'somevalue',
									'property' => 'P65'
								),
								'type' => 'statement',
								'qualifiers' => array(
									'P65' => array(
										array(
											'hash' => '210b00274bf03247a89de918f15b12142ebf9e56',
											'snaktype' => 'somevalue',
											'property' => 'P65',
										),
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
									),
								),
								'rank' => 'normal',
								'qualifiers-order' => array(
									'P65'
								),
								'references' => array(
									array(
										'hash' => 'bdc5f7185904d6d3219e13b7443571dda8c4bee8',
										'snaks' => array(
											'P65' => array(
												array(
													'snaktype' => 'somevalue',
													'property' => 'P65'
												)
											),
											'P68' => array(
												array(
													'snaktype' => 'somevalue',
													'property' => 'P68'
												)
											),
										),
										'snaks-order' => array(
											'P65',
											'P68'
										)
									),
								),
							)
						),
					),
					'sitelinks' => array(
						'enwiki' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => array( 'Q333' )
						),
						'zh_classicalwiki' => array(
							'site' => 'zh_classicalwiki',
							'title' => 'User:Addshore',
							'badges' => array()
						),
					)
				),
				'_element' => 'entity',
			),
		);

		$expectedRaw = array(
			'entities' => array(
				array(
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
						array(
							'language' => 'en',
							'value' => 'bar'
						),
						array(
							'language' => 'en',
							'value' => 'baz'
						),
						array(
							'language' => 'zh',
							'value' => '????????',
						),
						'_element' => 'alias',
					),
					'descriptions' => array(
						array(
							'language' => 'pt',
							'value' => 'ptDesc'
						),
						array(
							'language' => 'pl',
							'value' => 'Longer Description For An Item'
						),
						'_element' => 'description',
					),
					'labels' => array(
						array(
							'language' => 'de',
							'value' => 'foo'
						),
						array(
							'language' => 'zh_classical',
							'value' => 'Longer Label'
						),
						'_element' => 'label',
					),
					'claims' => array(
						array(
							'id' => 'P65',
							array(
								'id' => 'imaguid',
								'mainsnak' => array(
									'snaktype' => 'somevalue',
									'property' => 'P65'
								),
								'type' => 'statement',
								'qualifiers' => array(
									array(
										'id' => 'P65',
										array(
											'hash' => '210b00274bf03247a89de918f15b12142ebf9e56',
											'snaktype' => 'somevalue',
											'property' => 'P65',
										),
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
										'_element' => 'qualifiers',
									),
									'_element' => 'property',
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
											array(
												'id' => 'P65',
												array(
													'snaktype' => 'somevalue',
													'property' => 'P65'
												),
												'_element' => 'snak',
											),
											array(
												'id' => 'P68',
												array(
													'snaktype' => 'somevalue',
													'property' => 'P68'
												),
												'_element' => 'snak',
											),
											'_element' => 'property',
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
					),
					'sitelinks' => array(
						array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'badges' => array(
								'Q333',
								'_element' => 'badge',
							)
						),
						array(
							'site' => 'zh_classicalwiki',
							'title' => 'User:Addshore',
							'badges' => array(
								'_element' => 'badge',
							)
						),
						'_element' => 'sitelink',
					)
				),
				'_element' => 'entity',
			),
		);

		return array(
			array( false, $expected ),
			array( true, $expectedRaw ),
		);
	}

	/**
	 * @dataProvider provideTestAddEntityRevision
	 */
	public function testAddEntityRevision( $isRawMode, $expected ) {
		$result = $this->getDefaultResult();
		$props = array( 'info' );
		$item = new Item( new ItemId( 'Q123098' ) );

		//Basic
		$item->setLabel( 'de', 'foo' );
		$item->setLabel( 'zh_classical', 'Longer Label' );
		$item->addAliases( 'en', array( 'bar', 'baz' ) );
		$item->addAliases( 'zh', array( '????????' ) );
		$item->setDescription( 'pt', 'ptDesc' );
		$item->setDescription( 'pl', 'Longer Description For An Item' );
		$item->addSiteLink( new SiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q333' ) ) ) );
		$item->addSiteLink( new SiteLink( 'zh_classicalwiki', 'User:Addshore', array() ) );

		$snak = new PropertySomeValueSnak( new PropertyId( 'P65' ) );

		$qualifiers = new SnakList();
		$qualifiers->addSnak( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );
		$qualifiers->addSnak( new PropertyValueSnak( new PropertyId( 'P65' ), new StringValue( 'string!' ) ) );

		$references = new ReferenceList();
		$referenceSnaks = new SnakList();
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new PropertyId( 'P68' ) ) );
		$references->addReference( new Reference( $referenceSnaks ) );

		$guid = 'imaguid';
		$item->getStatements()->addNewStatement( $snak, $qualifiers, $references, $guid );

		$entityRevision = new EntityRevision( $item, 33, '20131126202923' );

		$serializationOptions = new SerializationOptions();
		$serializationOptions->setIndexTags( $isRawMode );

		$resultBuilder = $this->getResultBuilder( $result, null, $isRawMode );
		$resultBuilder->addEntityRevision( 'Q1230000', $entityRevision, $serializationOptions, $props );

		$data = $result->getResultData();

		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionKey() {
		$item = new Item( new ItemId( 'Q11' ) );

		$entityRevision = new EntityRevision( $item, 33, '20131126202923' );

		$props = array();
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );

		// automatic key
		$resultBuilder->addEntityRevision( null, $entityRevision, new SerializationOptions(), $props );

		$data = $result->getResultData();
		$this->assertArrayHasKey( 'Q11', $data['entities'] );

		// explicit key
		$resultBuilder->addEntityRevision( 'FOO', $entityRevision, new SerializationOptions(), $props );

		$data = $result->getResultData();
		$this->assertArrayHasKey( 'FOO', $data['entities'] );
	}

	public function provideTestAddEntityRevisionFallback() {
		return array(
			array(
				false,
				array(
					'entities' => array(
						'Q123101' => array(
							'id' => 'Q123101',
							'type' => 'item',
							'labels' => array(
								'de-formal' => array(
									'language' => 'de',
									'value' => 'Oslo-de',
								),
								'es' => array(
									'language' => 'en',
									'value' => 'Oslo-en',
								),
								'qug' => array(
									'language' => 'en',
									'value' => 'Oslo-en',
								),
								'zh-my' => array(
									'language' => 'en',
									'value' => 'Oslo-en',
								),
							),
							'descriptions' => array(
								'es' => array(
									'language' => 'es',
									'value' => 'desc-es',
								),
								'qug' => array(
									'language' => 'es',
									'value' => 'desc-es',
								),
								'zh-my' => array(
									'language' => 'zh-my',
									'value' => 'desc-zh-sg',
									'source-language' => 'zh-sg',
								),
							),
						),
						'_element' => 'entity',
					)
				),
			),
			array(
				true,
				array(
					'entities' => array(
						array(
							'id' => 'Q123101',
							'type' => 'item',
							'labels' => array(
								array(
									'language' => 'de',
									'value' => 'Oslo-de',
									'for-language' => 'de-formal'
								),
								array(
									'language' => 'en',
									'value' => 'Oslo-en',
									'for-language' => 'es',
								),
								array(
									'language' => 'en',
									'value' => 'Oslo-en',
									'for-language' => 'qug'
								),
								array(
									'language' => 'en',
									'value' => 'Oslo-en',
									'for-language' => 'zh-my'
								),
								'_element' => 'label',
							),
							'descriptions' => array(
								array(
									'language' => 'es',
									'value' => 'desc-es',
								),
								array(
									'language' => 'es',
									'value' => 'desc-es',
									'for-language' => 'qug'
								),
								array(
									'language' => 'zh-my',
									'value' => 'desc-zh-sg',
									'source-language' => 'zh-sg',
								),
								'_element' => 'description',
							),
						),
						'_element' => 'entity',
					)
				),
			),
		);
	}

	/**
	 * @dataProvider provideTestAddEntityRevisionFallback
	 */
	public function testAddEntityRevisionFallback( $indexedMode, $expected  ) {
		$item = new Item( new ItemId( 'Q123101' ) );
		$item->getFingerprint()->setLabel( 'de', 'Oslo-de' );
		$item->getFingerprint()->setLabel( 'en', 'Oslo-en' );
		$item->getFingerprint()->setDescription( 'es', 'desc-es' );
		$item->getFingerprint()->setDescription( 'zh-sg', 'desc-zh-sg' );
		$entityRevision = new EntityRevision( $item );

		$fallbackChainFactory = new LanguageFallbackChainFactory();
		$languages = array(
			'de-formal' => $fallbackChainFactory->newFromLanguageCode( 'de-formal' ),
			'es' => $fallbackChainFactory->newFromLanguageCode( 'es' ),
			'qug' => $fallbackChainFactory->newFromLanguageCode( 'qug' ),
			'zh-my' => $fallbackChainFactory->newFromLanguageCode( 'zh-my' ),
		);

		$props = array( 'labels', 'descriptions' );

		$options = new SerializationOptions();
		$options->setIndexTags( $indexedMode );
		$options->setLanguages( $languages );
		$options->setOption( EntitySerializer::OPT_PARTS, $props );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, $options, $indexedMode );
		$resultBuilder->addEntityRevision( null, $entityRevision, $options, $props );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );

		$this->assertEquals( $expected, $data );
	}

	public function testAddEntityRevisionWithSiteLinksFilter() {
		$item = new Item( new ItemId( 'Q123099' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$entityRevision = new EntityRevision( $item );

		$options = new SerializationOptions();
		$props = array( 'sitelinks' );
		$siteIds = array( 'enwiki' );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision( null, $entityRevision, $options, $props, $siteIds );

		$expected = array( 'entities' => array(
			'Q123099' => array(
				'id' => 'Q123099',
				'type' => 'item',
				'sitelinks' => array(
					'enwiki' => array(
						'site' => 'enwiki',
						'title' => 'Berlin',
						'badges' => array()
					),
				),
			),
			'_element' => 'entity',
		) );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	/**
	 * @see https://phabricator.wikimedia.org/T68181
	 */
	public function testAddEntityRevisionInIndexedModeWithSiteLinksFilter() {
		$indexedMode = true;

		$item = new Item( new ItemId( 'Q123100' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$entityRevision = new EntityRevision( $item );

		$options = new SerializationOptions();
		$options->setIndexTags( $indexedMode );
		$props = array( 'sitelinks' );
		$siteIds = array( 'enwiki' );

		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result, null, $indexedMode );
		$resultBuilder->addEntityRevision( null, $entityRevision, $options, $props, $siteIds );

		$expected = array( 'entities' => array(
			array(
				'id' => 'Q123100',
				'type' => 'item',
				'sitelinks' => array(
					array(
						'site' => 'enwiki',
						'title' => 'Berlin',
						'badges' => array(
							'_element' => 'badge'
						)
					),
					'_element' => 'sitelink'
				),
				'aliases' => array(
					'_element' => 'alias'
				),
				'descriptions' => array(
					'_element' => 'description'
				),
				'labels' => array(
					'_element' => 'label'
				),
				'claims' => array(
					'_element' => 'property'
				),
			),
			'_element' => 'entity'
		) );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function testAddBasicEntityInformation() {
		$result = $this->getDefaultResult();
		$entityId = new ItemId( 'Q67' );
		$expected = array( 'entity' => array(
			'id' => 'Q67',
			'type' => 'item',
		) );

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addBasicEntityInformation( $entityId, 'entity' );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addLabels( $labels, $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedLabel( 'en', $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addDescriptions( $descriptions, $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedDescription( 'en', $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function provideAddAliasGroupList() {
		return array(
			array(
				false,
				array(
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
								),
							),
						),
					),
				),
			),
			array(
				true,
				array(
					'entities' => array(
						'Q1' => array(
							'aliases' => array(
								array(
									'language' => 'en',
									'value' => 'boo',
								),
								array(
									'language' => 'en',
									'value' => 'hoo',
								),
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
						),
					),
				),
			),
		);
	}

	/**
	 * @dataProvider provideAddAliasGroupList
	 */
	public function testAddAliasGroupList( $rawMode, $expected ) {
		$result = $this->getDefaultResult();
		$aliasGroupList = new AliasGroupList(
			array(
				new AliasGroup( 'en', array( 'boo', 'hoo' ) ),
				new AliasGroup( 'de', array( 'ham', 'cheese' ) ),
			)
		);
		$path = array( 'entities', 'Q1' );

		$resultBuilder = $this->getResultBuilder( $result, null, $rawMode );
		$resultBuilder->addAliasGroupList( $aliasGroupList, $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function provideAddSiteLinkList() {
		return array(
			array(
				false,
				array(
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
							),
						),
					),
				),
			),
			array(
				true,
				array(
					'entities' => array(
						'Q1' => array(
							'sitelinks' => array(
								array(
									'site' => 'enwiki',
									'title' => 'User:Addshore',
									'badges' => array( '_element' => 'badge' ),
								),
								array(
									'site' => 'dewikivoyage',
									'title' => 'Berlin',
									'badges' => array( '_element' => 'badge' ),
								),
								'_element' => 'sitelink',
							),
						),
					),
				),
			),
		);
	}

	/**
	 * @dataProvider provideAddSiteLinkList
	 */
	public function testAddSiteLinkList( $isRawMode, $expected ) {
		$result = $this->getDefaultResult();
		$siteLinkList = new SiteLinkList(
			array(
				new SiteLink( 'enwiki', 'User:Addshore' ),
				new SiteLink( 'dewikivoyage', 'Berlin' ),
			)
		);
		$path = array( 'entities', 'Q1' );

		$resultBuilder = $this->getResultBuilder( $result, null, $isRawMode );
		$resultBuilder->addSiteLinkList( $siteLinkList, $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function testAddRemovedSiteLinks() {
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRemovedSiteLinks( $siteLinkList, $path );

		$data = $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addSiteLinkList( $siteLinkListAdd, $path );
		$resultBuilder->addRemovedSiteLinks( $siteLinkListRemove, $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	/**
	 * @dataProvider statementSerializationProvider
	 */
	public function testAddClaims( Statement $statement, $isRawMode, $statementSerialization ) {
		$result = $this->getDefaultResult();
		$path = array( 'entities', 'Q1' );

		if ( $isRawMode ) {
			$expected = array(
				'entities' => array(
					'Q1' => array(
						'claims' => array(
							array(
								'id' => 'P12',
								$statementSerialization,
								'_element' => 'claim',
							),
							'_element' => 'property',
						),
					),
				),
			);
		} else {
			$expected = array(
				'entities' => array(
					'Q1' => array(
						'claims' => array(
							'P12' => array(
								$statementSerialization
							),
						),
					),
				),
			);
		}

		$resultBuilder = $this->getResultBuilder( $result, null, $isRawMode );
		$resultBuilder->addClaims( array( $statement ), $path );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	/**
	 * @dataProvider statementSerializationProvider
	 */
	public function testAddClaim( Statement $statement, $isRawMode, $statementSerialization ) {
		$result = $this->getDefaultResult();
		$expected = array( 'claim' => $statementSerialization );

		$resultBuilder = $this->getResultBuilder( $result, null, $isRawMode );
		$resultBuilder->addClaim( $statement );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
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

		$expectedSerialization = array(
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
			'qualifiers-order' => array( 'P12' ),
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
						),
					),
					'snaks-order' => array( 'P12' ),
				),
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
				),
			),
		);

		$expectedRawModeSerialization = array(
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
						array(
							'id' => 'P12',
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
					),
					'snaks-order' => array(
						'P12',
						'_element' => 'property',
					),
				),
				'_element' => 'reference',
			),
			'qualifiers' => array(
				array(
					'id' => 'P12',
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
			),
		);

		return array(
			array( $statement, false, $expectedSerialization ),
			array( $statement, true, $expectedRawModeSerialization ),
		);
	}

	public function provideAddReference() {
		return array(
			array(
				false,
				array(
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
								)
							),
						),
						'snaks-order' => array( 'P12' ),
					),
				)
			),
			array(
				true,
				array(
					'reference' => array(
						'hash' => 'de52176deca6dd967b2a4122c96766089c1f793a',
						'snaks' => array(
							array(
								'id' => 'P12',
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
						),
						'snaks-order' => array(
							'P12',
							'_element' => 'property',
						),
					),
				)
			),
		);
	}

	/**
	 * @dataProvider provideAddReference
	 */
	public function testAddReference( $isRawMode, $expected ) {
		$result = $this->getDefaultResult();
		$reference = new Reference(
			new SnakList( array(
				new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) )
			) )
		);

		$resultBuilder = $this->getResultBuilder( $result, null, $isRawMode );
		$resultBuilder->addReference( $reference );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	/**
	 * @dataProvider provideMissingEntity
	 */
	public function testAddMissingEntity( $missingEntities, $expected ) {
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );

		foreach ( $missingEntities as $key => $missingDetails ) {
			if ( is_int( $key ) ) {
				// string keys are kept for use in the result structure, integer keys aren't
				$key = null;
			}

			$resultBuilder->addMissingEntity( $key, $missingDetails );
		}

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function provideMissingEntity() {
		return array(
			array(
				array(
					array( 'site' => 'enwiki', 'title' => 'Berlin'),
				),
				array(
					'entities' => array(
						'-1' => array(
							'site' => 'enwiki',
							'title' => 'Berlin',
							'missing' => '',
						),
						'_element' => 'entity',
					),
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
					),
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
					),
				)
			),
			array(
				array(
					array( 'site' => 'enwiki', 'title' => 'Berlin'),
					array( 'site' => 'dewiki', 'title' => 'Foo'),
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
					),
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
				//todo this is JUST SILLY
				'n' => array(
					'from' => 'berlin',
					'to' => 'Berlin'
				),
			),
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addNormalizedTitle( $from, $to );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function testAddRevisionIdFromStatusToResult() {
		$result = $this->getDefaultResult();
		$mockRevision = $this->getMockBuilder( 'Revision' )
		->disableOriginalConstructor()
		->getMock();
		$mockRevision->expects( $this->once() )
			->method( 'getId' )
			->will( $this->returnValue( 123 ) );
		$mockStatus = $this->getMock( 'Status' );
		$mockStatus->expects( $this->once() )
			->method( 'getValue' )
			->will( $this->returnValue( array( 'revision' => $mockRevision ) ) );
		$expected = array( 'entity' => array( 'lastrevid' => '123' ) );

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addRevisionIdFromStatusToResult( $mockStatus, 'entity' );

		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertEquals( $expected, $data );
	}

	public function provideSetList() {
		return array(
			'null path' => array( null, 'foo', array(), 'letter', false, array( 'foo' => array() ) ),

			'empty path' => array( array(), 'foo', array( 'x', 'y' ), 'letter', false,
				array(
					'foo' => array( 'x', 'y' )
			) ),

			'string path' => array( 'ROOT', 'foo', array( 'x', 'y' ), 'letter', false,
				array(
					'ROOT' => array(
						'foo' => array( 'x', 'y' ) )
				) ),

			'actual path' => array( array( 'one', 'two' ), 'foo', array( 'X' => 'x', 'Y' => 'y' ), 'letter', false,
				array(
					'one' => array(
						'two' => array(
							'foo' => array( 'X' => 'x', 'Y' => 'y' ) ) )
				) ),

			'indexed' => array( 'ROOT', 'foo', array( 'X' => 'x', 'Y' => 'y' ), 'letter', true,
				array(
					'ROOT' => array(
						'foo' => array( 'x', 'y', '_element' => 'letter' ) )
				) ),

			'pre-set element name' => array( 'ROOT', 'foo', array( 'x', 'y', '_element' => '_thingy' ), 'letter', true,
				array(
					'ROOT' => array(
						'foo' => array( 'x', 'y', '_element' => 'letter' ) )
				) ),

		);
	}

	/**
	 * @dataProvider provideSetList
	 */
	public function testSetList( $path, $name, array $values, $tag, $indexed, $expected ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result, null, $indexed );

		$builder->setList( $path, $name, $values, $tag );
		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertResultStructure( $expected, $data );
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

		$this->setExpectedException( 'InvalidArgumentException' );
		$builder->setList( $path, $name, $values, $tag );
	}

	public function provideSetValue() {
		return array(
			'null path' => array( null, 'foo', 'value', false, array( 'foo' => 'value' ) ),

			'empty path' => array( array(), 'foo', 'value', false,
				array(
					'foo' => 'value'
				) ),

			'string path' => array( 'ROOT', 'foo', 'value', false,
				array(
					'ROOT' => array( 'foo' => 'value' )
				) ),

			'actual path' => array( array( 'one', 'two' ), 'foo', array( 'X' => 'x', 'Y' => 'y' ), true,
				array(
					'one' => array(
						'two' => array(
							'foo' => array( 'X' => 'x', 'Y' => 'y' ) ) )
				) ),

			'indexed' => array( 'ROOT', 'foo', 'value', true,
				array(
					'ROOT' => array( 'foo' => 'value' )
				) ),
		);
	}

	/**
	 * @dataProvider provideSetValue
	 */
	public function testSetValue( $path, $name, $value, $indexed, $expected ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result, null, $indexed );

		$builder->setValue( $path, $name, $value );
		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertResultStructure( $expected, $data );
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

		$this->setExpectedException( 'InvalidArgumentException' );
		$builder->setValue( $path, $name, $value );
	}

	public function provideAppendValue() {
		return array(
			'null path' => array( null, null, 'value', 'letter', false,
				array( 'value', '_element' => 'letter' ),
			),

			'empty path' => array( array(), null, 'value', 'letter', false,
				array( 'value', '_element' => 'letter' )
			),

			'string path' => array( 'ROOT', null, 'value', 'letter', false,
				array(
					'ROOT' => array( 'value', '_element' => 'letter' )
				) ),

			'actual path' => array( array( 'one', 'two' ), null, array( 'X' => 'x', 'Y' => 'y' ), 'letter', false,
				array(
					'one' => array(
						'two' => array( array( 'X' => 'x', 'Y' => 'y' ), '_element' => 'letter' ),
					)
				) ),

			'int key' => array( 'ROOT', -2, 'value', 'letter', false,
				array(
					'ROOT' => array( -2 => 'value', '_element' => 'letter' ),
				) ),

			'string key' => array( 'ROOT', 'Q7', 'value', 'letter', false,
				array(
					'ROOT' => array( 'Q7' => 'value', '_element' => 'letter' ),
				) ),

			'null key indexed' => array( 'ROOT', null, 'value', 'letter', true,
				array(
					'ROOT' => array( 'value', '_element' => 'letter' )
				) ),

			'int key indexed' => array( 'ROOT', -2, 'value', 'letter', true,
				array(
					'ROOT' => array( 'value', '_element' => 'letter' )
				) ),

			'string key indexed' => array( 'ROOT', 'Q7', 'value', 'letter', true,
				array(
					'ROOT' => array( 'value', '_element' => 'letter' )
				) ),
		);
	}

	/**
	 * @dataProvider provideAppendValue
	 */
	public function testAppendValue( $path, $key, $value, $tag, $indexed, $expected ) {
		$result = $this->getDefaultResult();
		$builder = $this->getResultBuilder( $result, null, $indexed );

		$builder->appendValue( $path, $key, $value, $tag );
		$data = $result->getResultData();
		$this->removeElementsWithKeysRecursively( $data, array( '_type' ) );
		$this->assertResultStructure( $expected, $data );
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

		$this->setExpectedException( 'InvalidArgumentException' );
		$builder->appendValue( $path, $key, $value, $tag );
	}

	protected function assertResultStructure( $expected, $actual, $path = null ) {
		foreach ( $expected as $key => $value ) {
			$this->assertArrayHasKey( $key, $actual, $path );

			if ( is_array( $value ) ) {
				$this->assertInternalType( 'array', $actual[$key], $path );

				$subKey = $path === null ? $key : $path . '/' . $key;
				$this->assertResultStructure( $value, $actual[$key], $subKey );
			} else {
				$this->assertEquals( $value, $actual[$key] );
			}
		}

		$this->assertEquals( array_keys( $expected ), array_keys( $actual ), "Keys of $path:" );
	}

	private function removeElementsWithKeysRecursively( array &$array, array $unwantedKeys ) {
		foreach ( $unwantedKeys as $unwantedKey ) {
			unset( $array[$unwantedKey] );
			foreach ( $array as &$value ) {
				if ( is_array( $value ) ) {
					$this->removeElementsWithKeysRecursively( $value, array( $unwantedKey ) );
				}
			}
		}
	}

}
