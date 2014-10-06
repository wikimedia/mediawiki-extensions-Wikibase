<?php

namespace Wikibase\Test\Api;

use PHPUnit_Framework_TestCase;

/**
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */
class ApiConventionsTest extends PHPUnit_Framework_TestCase {

	public function wikibaseApiModuleProvider() {
		$argList = array();

		foreach ( $GLOBALS['wgAPIModules'] as $moduleClass ) {
			//make sure to only test Wikibase Api modules
			if ( strpos( $moduleClass, 'Wikibase' ) !== false ) {
				$argList[] = array( $moduleClass );
			}
		}

		return $argList;
	}

	/**
	* Connects the assertions for the different methods and iterates through the api modules
	* @dataProvider wikibaseApiModuleProvider
	*/
	public function testApiConventions( $moduleClass ) {
		$params = array();
		$user =  $GLOBALS['wgUser'];

		$request = new \FauxRequest( $params, true );
		$main = new \ApiMain( $request );
		$main->getContext()->setUser( $user );
		$module = new $moduleClass( $main, 'moduleClass' );

		$this->assertGetFinalParamDescription( $moduleClass, $module );
		$this->assertGetExamples( $moduleClass, $module );
		$this->assertGetFinalDescription( $moduleClass, $module );
	}

	/**
	 * This method is for the assertions in particular for getFinalDescription as defined in ApiBase
	 * @param string $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	private function assertGetFinalDescription ( $moduleClass, $module ) {
		$method = 'getFinalDescription';
		$descArray = $module->$method();

		$rMethod = new \ReflectionMethod( $module, $method );
		$this->assertTrue( $rMethod->isPublic(), 'the method ' . $method . ' of module ' . $moduleClass . ' is not public' );

		$this->assertNotEmpty( $module->$method(), 'the Module ' . $moduleClass . ' does not have the method ' . $method );
		$this->assertNotEmpty( $descArray, 'the array returned by the method ' . $method . ' of module ' . $moduleClass . ' is empty' );
		foreach ( $descArray as $desc ) {
			$this->assertInternalType( 'string', $desc, 'the ' . $desc . '. value returned by the method ' . $method . ' of the module ' . $moduleClass . ' is not a string' );
		}
	}

	/**
	 * This method is for the assertions for getFinalParamDescription as defined in ApiBase, depending on getFinalParams
	 * @param string $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	private function assertGetFinalParamDescription ( $moduleClass, $module ) {
		$method = 'getFinalParamDescription';
		$paramsMethod = 'getFinalParams';
		$paramsArray = $module->$paramsMethod();
		if ( !empty( $paramsArray ) ) {
			$paramDescArray = $module->$method();
			$this->assertNotEmpty( $paramDescArray, 'the array returned by the method ' . $method . ' of module ' . $moduleClass . ' is empty' );

			//comparing the keys of the arrays of getParamDescription and getParams
			$arrayKeysMatch = !array_diff_key( $paramDescArray, $paramsArray ) && !array_diff_key( $paramsArray, $paramDescArray );
			$this->assertTrue( $arrayKeysMatch, 'keys different at ' . $moduleClass );
		}
	}

	/**
	 * This method is for the assertions of getExamples as defined in ApiBase
	 * @param string $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	private function assertGetExamples( $moduleClass, $module ) {
		$method = 'getExamples';
		$rMethod = new \ReflectionMethod( $moduleClass,  $method );
		$rMethod->setAccessible( true );
		$exArray = $rMethod->invoke( $module );

		$this->assertNotEmpty( $exArray, 'there are no examples for ' . $moduleClass );

		foreach ( $exArray as $key => $value ) {
			$this->assertContains('api.php?action=', $key, 'the key ' . $key . ' is not an url at ' . $moduleClass );
			$this->assertInternalType( 'string', $value, 'the value of the example for ' . $key . ' in ' . $moduleClass . ' is not a string' );
		}

	}
}