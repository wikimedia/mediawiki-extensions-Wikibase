<?php

namespace Wikibase\Test\Api;

use ApiMain;
use ReflectionMethod;
use Wikibase\Api\MergeItems;

/**
 * Unit tests for MergeItems api module
 *
 * @todo factor this out so the more general tests cover all modules..
 * @todo include in core?
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group MergeItemsTest
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MergeItemsUnitTest extends \PHPUnit_Framework_TestCase {

	static $moduleName = 'wbmergeitems';

	protected function getModule() {
		return new MergeItems( new ApiMain(), self::$moduleName );
	}

	/**
	 * @covers Wikibase\Api\MergeItems::isWriteMode
	 */
	public function testIsWriteMode() {
		$module = $this->getModule();
		$this->assertTrue( $module->isWriteMode() );
	}

	/**
	 * @covers Wikibase\Api\MergeItems::getExamples
	 */
	public function testGetExamples() {
		$module = $this->getModule();
		$method = new ReflectionMethod( $module, 'getExamples' );
		$method->setAccessible( true );

		$examples = $method->invoke( $module );

		$this->assertGreaterThan( 0, $examples );
		foreach( $examples as $example => $description ) {
			$this->assertInternalType( 'string', $example );
			$this->assertInternalType( 'string', $description );
			$this->assertContains( 'api.php?action=' . self::$moduleName, $example );
		}
	}

	/**
	 * @covers Wikibase\Api\MergeItems::getDescription
	 */
	public function testGetDescription() {
		$module = $this->getModule();
		$description = $module->getDescription();

		$this->assertInternalType( 'array', $description );
		foreach( $description as $line ) {
			$this->assertInternalType( 'string', $line );
		}
	}

	/**
	 * @covers Wikibase\Api\MergeItems::getAllowedParams
	 */
	public function testGetAllowedParams() {
		$hadParams = array();
		$module = $this->getModule();
		$params = $module->getAllowedParams();

		$this->assertInternalType( 'array', $params );
		foreach( $params as $param => $settings ) {
			$this->assertInternalType( 'string', $param );
			if( !is_null( $settings ) ){
				$this->assertInternalType( 'array', $settings );
			}
			//@todo further check $settings
			$this->assertEquals( $param, strtolower( $param ), 'param is not all lower case' );
			$this->assertFalse( in_array( $param, $hadParams ), 'duplicate params detected' );
			$hadParams[] = $param;
		}
	}

	/**
	 * @dataProvider provideValidateParams
	 */
	public function testValidateParams( $params, $shouldPass ) {
		$module = $this->getModule();
		$method = new ReflectionMethod( $module, 'validateParams' );
		$method->setAccessible( true );

		if( !$shouldPass ) {
			$this->setExpectedException( 'UsageException' );
		}

		$method->invoke( $module, $params );
		//perhaps the method should return true.. For now we must do this
		$this->assertTrue( true );
	}

	public static function provideValidateParams() {
		$testCases = array();

		$testCases[ 'NoParams' ] = array(
			array(),
			false,
		);

		$testCases[ 'GoodParams' ] = array(
			array(
				'fromid' => 'Q1',
				'toid' => 'Q2',
			),
			true,
		);

		$testCases[ 'MissingToId' ] = array(
			array(
				'fromid' => 'Q1',
			),
			false,
		);
		$testCases[ 'MissingFromId' ] = array(
			array(
				'toid' => 'Q1',
			),
			false,
		);


		return $testCases;
	}

	//@todo getSummary

	//@todo validateEntityContents
	//@todo getEntityContentFromIdString
	//@todo addEntityToOutput
	//@todo getIgnoreConflicts
	//@todo execute?
	//@todo getRequiredPermissions
	//@todo getPossibleErrors
	//@todo attemptSaveMerge

} 