<?php

namespace Wikibase\Test\Api;

use ApiResult;
use PHPUnit_Framework_TestCase;
use Wikibase\Api\ResultBuilder;
use Wikibase\Claim;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Statement;

class ResultBuilderTest extends PHPUnit_Framework_TestCase {

	function getDefaultResult(){
		$apiMain =  $this->getMockBuilder( 'ApiMain' )->disableOriginalConstructor()->getMockForAbstractClass();
		return new ApiResult( $apiMain );
	}

	function testCanConstruct(){
		$resultBuilder = new ResultBuilder( $this->getDefaultResult() );
		$this->assertInstanceOf( '\Wikibase\Api\ResultBuilder', $resultBuilder );
	}

	/**
	 * @dataProvider provideBadConstructionData
	 */
	function testBadConstruction( $result ){
		$this->setExpectedException( 'InvalidArgumentException' );
		new ResultBuilder( $result );
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
	function testMarkResultSuccess( $param, $expected ){
		$result = $this->getDefaultResult();
		$resultBuilder = new ResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
		$this->assertEquals( array( 'success' => $expected ),  $result->getData() );
	}

	public static function provideMarkResultSuccess() {
		return array( array( true, 1 ), array( 1, 1 ), array( false, 0 ), array( 0, 0 ), array( null, 0 ) );
	}

	/**
	 * @dataProvider provideMarkResultSuccessExceptions
	 */
	function testMarkResultSuccessExceptions( $param ){
		$this->setExpectedException( 'InvalidArgumentException' );
		$result = $this->getDefaultResult();
		$resultBuilder = new ResultBuilder( $result );
		$resultBuilder->markSuccess( $param );
	}

	public static function provideMarkResultSuccessExceptions() {
		return array( array( 3 ), array( -1 ) );
	}

	//TODO test addEntities

	/**
	 * @dataProvider provideAddEntity
	 */
	function testAddEntity( $entities, $expected ){
		$result = $this->getDefaultResult();
		$resultBuilder = new ResultBuilder( $result );
		foreach( $entities as $entity ){
			$resultBuilder->addEntity( $entity );
		}
		$this->assertEquals( $expected, $result->getData() );
	}

	public static function provideAddEntity() {
		$testCases = array();

		$item = new Item( array() );
		$testCases[] = array(
			array( clone $item ),
			array( 'entities' => array(
				'-1' => array( 'missing' => 1 ) ) ) );
		$testCases[] = array(
			array( clone $item, clone $item ),
			array( 'entities' => array(
				'-1' => array( 'missing' => 1 ),
				'-2' => array( 'missing' => 1 ) ) ) );

		$item->setId( new ItemId( 'Q123' ) );
		$testCases[] = array(
			array( clone $item ),
			array( 'entities' => array(
				'Q123' => array( 'labels' => array(), 'descriptions' => array(), 'aliases' => array(), 'sitelinks' => array(), 'claims' => array() ) ) ) );

		$item->setLabel( 'en', 'foo' );
		$testCases[] = array(
			array( clone $item ),
			array( 'entities' => array(
				'Q123' => array( 'labels' => array( 'en' => 'foo' ), 'descriptions' => array(), 'aliases' => array(), 'sitelinks' => array(), 'claims' => array() ) ) ) );

		$item->setDescription( 'de', 'desc' );
		$testCases[] = array(
			array( clone $item ),
			array( 'entities' => array(
				'Q123' => array( 'labels' => array( 'en' => 'foo' ), 'descriptions' => array( 'de' => 'desc' ), 'aliases' => array(), 'sitelinks' => array(), 'claims' => array() ) ) ) );

		$item->addAliases( 'nl', array( 'bacon', 'cheese' ) );
		$testCases[] = array(
			array( clone $item ),
			array( 'entities' => array(
				'Q123' => array( 'labels' => array( 'en' => 'foo' ), 'descriptions' => array( 'de' => 'desc' ), 'aliases' => array( 'nl' => array( 'bacon', 'cheese' ) ), 'sitelinks' => array(), 'claims' => array() ) ) ) );

		$claim = new Claim( new PropertySomeValueSnak( new PropertyId( 'P12' ) ) );
		$claim->setGuid( 'Q123$DE935EEE-E556-4585-BD31-7ED2F76C4134' );
		$item->addClaim( clone $claim );
		$testCases[] = array(
			array( clone $item ),
			array( 'entities' => array(
				'Q123' => array( 'labels' => array( 'en' => 'foo' ), 'descriptions' => array( 'de' => 'desc' ), 'aliases' => array( 'nl' => array( 'bacon', 'cheese' ) ), 'sitelinks' => array(),
					'claims' => array( 'P12' => array( array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P12' ), 'type' => 'claim', 'id' => 'Q123$DE935EEE-E556-4585-BD31-7ED2F76C4134' ) ) ) ) ) ) );

		return $testCases;
	}

	/**
	 * @dataProvider provideAddClaims
	 */
	function testAddClaims( $claims, $expected ){
		$result = $this->getDefaultResult();
		$resultBuilder = new ResultBuilder( $result );
		$resultBuilder->addClaims( $claims );
		$this->assertEquals( $expected, $result->getData() );
	}

	public static function provideAddClaims() {
		$testCases = array();

		$claims[1] = new Claim( new PropertySomeValueSnak( new PropertyId( 'P12' ) ) );
		$claims[1]->setGuid( 'Q123$DE935EEE-E556-4585-BD31-7ED2F76C4134' );
		$testCases[] = array(
			array( $claims[1] ),
			array( 'claims' => array(
				'P12' => array(
					array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P12' ), 'type' => 'claim', 'id' => 'Q123$DE935EEE-E556-4585-BD31-7ED2F76C4134' ) ) )
			) );

		$claims[2] = new Statement( new PropertySomeValueSnak( new PropertyId( 'P12' ) ) );
		$claims[2]->setGuid( 'Q123$DE935EEE-E556-4585-BD31-111111111111' );
		$testCases[] = array(
			array( $claims[1], $claims[2] ),
			array( 'claims' => array(
				'P12' => array(
					array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P12' ), 'type' => 'claim', 'id' => 'Q123$DE935EEE-E556-4585-BD31-7ED2F76C4134' ),
					array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P12' ), 'type' => 'statement', 'id' => 'Q123$DE935EEE-E556-4585-BD31-111111111111', 'rank' => 'normal' ) ) )
			) );

		$claims[3] = new Claim( new PropertySomeValueSnak( new PropertyId( 'P22' ) ) );
		$claims[3]->setGuid( 'Q123$DE935EEE-E556-4585-XXXX-7ED2F76C4134' );
		$testCases[] = array(
			array( $claims[1], $claims[2], $claims[3] ),
			array( 'claims' => array(
				'P12' => array(
					array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P12' ), 'type' => 'claim', 'id' => 'Q123$DE935EEE-E556-4585-BD31-7ED2F76C4134' ),
					array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P12' ), 'type' => 'statement', 'id' => 'Q123$DE935EEE-E556-4585-BD31-111111111111', 'rank' => 'normal' ) ),
				'P22' => array(
					array( 'mainsnak' => array( 'snaktype' => 'somevalue', 'property' => 'P22' ), 'type' => 'claim', 'id' => 'Q123$DE935EEE-E556-4585-XXXX-7ED2F76C4134' ) ) )
			) );

		return $testCases;
	}

}