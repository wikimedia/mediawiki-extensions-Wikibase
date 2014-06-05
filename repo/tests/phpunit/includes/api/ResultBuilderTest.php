<?php

namespace Wikibase\Test\Api;

use ApiResult;
use DataValues\StringValue;
use Wikibase\Api\ResultBuilder;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\EntityRevision;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;

/**
 * @covers Wikibase\Api\ResultBuilder
 * @todo mock and inject serializers to avoid massive expected output?
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ResultBuilderTest extends \PHPUnit_Framework_TestCase {

	protected function getDefaultResult( $indexedMode = false ){
		$apiMain =  $this->getMockBuilder( 'ApiMain' )->disableOriginalConstructor()->getMockForAbstractClass();
		$result = new ApiResult( $apiMain );

		if ( $indexedMode ) {
			$result->setRawMode();
		}

		return $result;
	}

	protected function getResultBuilder( $result, $options = null ){
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

		$mockEntityTitleLookup = $this->getMock( '\Wikibase\EntityTitleLookup' );
		$mockEntityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $mockTitle ) );

		$mockPropertyDataTypeLookup = $this->getMock( '\Wikibase\Lib\PropertyDataTypeLookup' );
		$mockPropertyDataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnCallback( function( $propertyId ) {
				return 'DtIdFor_' . $propertyId;
			} ) );

		// @todo inject EntityFactory and SiteStore
		$serializerFactory = new SerializerFactory(
			null, //no serialization options
			$mockPropertyDataTypeLookup
		);

		$builder = new ResultBuilder(
			$result,
			$mockEntityTitleLookup,
			$serializerFactory
		);

		if ( is_array( $options ) ) {
			$builder->getOptions()->setOptions( $options );
		} elseif ( $options instanceof SerializationOptions ) {
			$builder->getOptions()->merge( $options );
		}

		return $builder;
	}

	public function testCanConstruct(){
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$this->assertInstanceOf( '\Wikibase\Api\ResultBuilder', $resultBuilder );
	}

	/**
	 * @dataProvider provideBadConstructionData
	 */
	public function testBadConstruction( $result ){
		$this->setExpectedException( 'InvalidArgumentException' );
		$this->getResultBuilder( $result );
	}

	public static function provideBadConstructionData() {
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
	public function testMarkResultSuccess( $param, $expected ){
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
		$this->assertEquals( array( 'success' => $expected ),  $result->getData() );
	}

	public static function provideMarkResultSuccess() {
		return array( array( true, 1 ), array( 1, 1 ), array( false, 0 ), array( 0, 0 ), array( null, 0 ) );
	}

	/**
	 * @dataProvider provideMarkResultSuccessExceptions
	 */
	public function testMarkResultSuccessExceptions( $param ){
		$this->setExpectedException( 'InvalidArgumentException' );
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
	}

	public static function provideMarkResultSuccessExceptions() {
		return array( array( 3 ), array( -1 ) );
	}

	public function testAddEntityRevision() {
		$result = $this->getDefaultResult();
		$props = array( 'info' );
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q123098' ) );

		//Basic
		$item->setLabel( 'de', 'foo' );
		$item->setLabel( 'zh_classical', 'Longer Label' );
		$item->addAliases( 'en', array( 'bar', 'baz' ) );
		$item->addAliases( 'zh', array( '????????' ) );
		$item->setDescription( 'pt', 'ptDesc' );
		$item->setDescription( 'pl', 'Longer Description For An Item' );
		$item->addSiteLink( new SiteLink( 'enwiki', 'Berlin', array( new ItemId( 'Q333' ) ) ) );
		$item->addSiteLink( new SiteLink( 'zh_classicalwiki', 'User:Addshore', array() ) );

		$claim = $item->newClaim( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );
		$claim->setGuid( 'imaguid' );

		$qualifiers = new SnakList();
		$qualifiers->addSnak( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );
		$qualifiers->addSnak( new PropertyValueSnak( new PropertyId( 'P65' ), new StringValue( 'string!' ) ) );
		$claim->setQualifiers( $qualifiers );

		$references = new ReferenceList();
		$referenceSnaks = new SnakList();
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new PropertyId( 'P65' ) ) );
		$referenceSnaks->addSnak( new PropertySomeValueSnak( new PropertyId( 'P68' ) ) );
		$references->addReference( new Reference( $referenceSnaks ) );
		$claim->setReferences( $references );

		$item->addClaim( $claim );

		$entityRevision = new EntityRevision( $item, 33, '20131126202923' );

		$expected = array( 'entities' => array( 'Q123098' => array(
			'pageid' => 123, //mocked
			'ns' => 456, //mocked
			'title' => 'MockPrefixedText', //mocked
			'id' => 'Q123098',
			'type' => 'item',
			'lastrevid' => 33,
			'modified' => '2013-11-26T20:29:23Z',
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
									'P65', 'P68'
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
					'badges' => array( )
				),
			),
		) ) );

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision( $entityRevision, new SerializationOptions(), $props );

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddEntityRevision_SiteLinksFilterDoesNotCorruptIndexedMode() {
		$indexedMode = true;

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q123099' ) );
		$item->addSiteLink( new SiteLink( 'enwiki', 'Berlin' ) );
		$item->addSiteLink( new SiteLink( 'dewiki', 'Berlin' ) );
		$entityRevision = new EntityRevision( $item, 0, '20010203040506' );

		$options = new SerializationOptions();
		$options->setIndexTags( $indexedMode );
		$props = array( 'sitelinks' );
		$siteIds = array( 'enwiki' );

		$result = $this->getDefaultResult( $indexedMode );
		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addEntityRevision( $entityRevision, $options, $props, $siteIds );

		$expected = array( 'entities' => array(
			array(
				'id' => 'Q123099',
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

		$this->assertEquals( $expected, $result->getData() );
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

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddBasicEntityInformationForcedNumeric() {
		$result = $this->getDefaultResult();
		$entityId = new ItemId( 'Q67' );
		$expected = array( 'entity' => array(
			'id' => '67',
			'type' => 'item',
		) );

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addBasicEntityInformation( $entityId, 'entity', true );

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddLabels(){
		$result = $this->getDefaultResult();
		$labels = array( 'en' => 'foo', 'de' => 'bar' );
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

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddDescriptions(){
		$result = $this->getDefaultResult();
		$descriptions = array( 'en' => 'foo', 'de' => 'bar' );
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

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddAliases(){
		$result = $this->getDefaultResult();
		$aliases = array( 'en' => array( 'boo', 'hoo' ), 'de' => array( 'ham', 'cheese' ) );
		$path = array( 'entities', 'Q1' );
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addAliases( $aliases, $path );

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddSiteLinks(){
		$result = $this->getDefaultResult();
		$sitelinks = array( new SiteLink( 'enwiki', 'User:Addshore' ), new SiteLink( 'dewikivoyage', 'Berlin' ) );
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
					),
				),
			),
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addSiteLinks( $sitelinks, $path );

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddClaims(){
		$result = $this->getDefaultResult();
		$claim1 = new Claim( new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) ) );
		$claim1->setGuid( 'fooguidbar' );
		$claims = array( $claim1 );
		$path = array( 'entities', 'Q1' );
		$expected = array(
			'entities' => array(
				'Q1' => array(
					'claims' => array(
						'P12' => array(
							array(
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
								'type' => 'claim',
							)
						)
					),
				),
			),
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addClaims( $claims, $path );

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddClaim(){
		$result = $this->getDefaultResult();
		$claim = new Claim( new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) ) );
		$claim->setGuid( 'fooguidbar' );
		$expected = array(
			'claim' => array(
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
				'type' => 'claim',
			),
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addClaim( $claim );

		$this->assertEquals( $expected, $result->getData() );
	}

	public function testAddReference(){
		$result = $this->getDefaultResult();
		$reference = new Reference( new SnakList( array( new PropertyValueSnak( new PropertyId( 'P12' ), new StringValue( 'stringVal' ) ) ) ) );
		$hash = $reference->getHash();
		$expected = array(
			'reference' => array(
				'hash' => $hash,
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
		);

		$resultBuilder = $this->getResultBuilder( $result );
		$resultBuilder->addReference( $reference );

		$this->assertEquals( $expected, $result->getData() );
	}

	/**
	 * @dataProvider provideMissingEntity
	 */
	public function testAddMissingEntity( $missingEntities, $expected ){
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );

		foreach( $missingEntities as $missingDetails ){
			$resultBuilder->addMissingEntity( $missingDetails );
		}

		$this->assertEquals( $expected, $result->getData() );
	}

	public static function provideMissingEntity() {
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
							//@todo fix bug 45509 useless missing flag
							'missing' => '',
						)
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
							//@todo fix bug 45509 useless missing flag
							'missing' => '',
						),
						'-2' => array(
							'site' => 'dewiki',
							'title' => 'Foo',
							//@todo fix bug 45509 useless missing flag
							'missing' => '',
						)
					),
				)
			),
		);
	}

	public function testAddNormalizedTitle(){
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

		$this->assertEquals( $expected, $result->getData() );
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

		$this->assertEquals( $expected, $result->getData() );
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
		);
	}

	/**
	 * @dataProvider provideSetList
	 */
	public function testSetList( $path, $name, array $values, $tag, $indexed, $expected ) {
		$result = $this->getDefaultResult( $indexed );
		$builder = $this->getResultBuilder( $result );

		$builder->setList( $path, $name, $values, $tag );
		$this->assertResultStructure( $expected, $result->getData() );
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
		$result = $this->getDefaultResult( $indexed );
		$builder = $this->getResultBuilder( $result );

		$builder->setValue( $path, $name, $value );
		$this->assertResultStructure( $expected, $result->getData() );
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
			'null path' => array( null, null, 'value', 'letter', false, array( 'value' ) ),

			'empty path' => array( array(), null, 'value', 'letter', false,
				array( 'value' )
			),

			'string path' => array( 'ROOT', null, 'value', 'letter', false,
				array(
					'ROOT' => array( 'value' )
				) ),

			'actual path' => array( array( 'one', 'two' ), null, array( 'X' => 'x', 'Y' => 'y' ), 'letter', false,
				array(
					'one' => array(
						'two' => array( array( 'X' => 'x', 'Y' => 'y' ) ) )
				) ),


			'int key' => array( 'ROOT', -2, 'value', 'letter', false,
				array(
					'ROOT' => array( -2 => 'value' )
				) ),

			'string key' => array( 'ROOT', 'Q7', 'value', 'letter', false,
				array(
					'ROOT' => array( 'Q7' => 'value' )
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
		$result = $this->getDefaultResult( $indexed );
		$builder = $this->getResultBuilder( $result );

		$builder->appendValue( $path, $key, $value, $tag );
		$this->assertResultStructure( $expected, $result->getData() );
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
	}
}
