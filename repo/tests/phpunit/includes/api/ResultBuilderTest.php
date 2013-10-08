<?php

namespace Wikibase\Test\Api;

use ApiResult;
use DataValues\StringValue;
use PHPUnit_Framework_TestCase;
use Wikibase\Api\ResultBuilder;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\Api\ResultBuilder
 * @todo mock and inject serializers to avoid massive expected output
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ResultBuilderTest extends PHPUnit_Framework_TestCase {

	protected function getDefaultResult(){
		$apiMain =  $this->getMockBuilder( 'ApiMain' )->disableOriginalConstructor()->getMockForAbstractClass();
		return new ApiResult( $apiMain );
	}

	protected function getResultBuilder( $result ){
		$dataTypeLookup = $this->getMock( 'Wikibase\Lib\PropertyDataTypeLookup' );
		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'test' ) );

		$options = new SerializationOptions( array( SnakSerializer::OPT_DATA_TYPE_LOOKUP => $dataTypeLookup ) );
		$serializerFactory = new SerializerFactory( $options );
		return new ResultBuilder( $result, $serializerFactory );
	}

	public function testCanConstruct(){
		$result = $this->getDefaultResult();
		$resultBuilder = $this->getResultBuilder( $result );
		$this->assertInstanceOf( '\Wikibase\Api\ResultBuilder', $resultBuilder );
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
		$sitelinks = array( new SimpleSiteLink( 'enwiki', 'User:Addshore' ), new SimpleSiteLink( 'dewikivoyage', 'Berlin' ) );
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
									'datatype' => 'test',
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

}