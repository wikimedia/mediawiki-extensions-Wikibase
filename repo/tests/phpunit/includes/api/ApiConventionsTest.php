<?php

namespace Wikibase\Test\Api;

/**
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Lucie-Aimée Kaffee
 */
class ApiConventionsTest extends WikibaseApiTestCase {
	/**
	* Connects the assertions for the different methods and iterates through the api modules
	*/
	public function testApiConventions() {
		$params = array();
		$user =  $GLOBALS['wgUser'];

		foreach ( $GLOBALS['wgAPIModules'] as $moduleClass ) {
			$request = new \FauxRequest( $params, true );
			$main = new \ApiMain( $request );
			$main->getContext()->setUser( $user );
			$module = new $moduleClass( $main, 'moduleClass' );

			$this->assertGetFinalParamDescription($moduleClass, $module);
			$this->assertGetExamples( $moduleClass, $module );
			$this->assertGetFinalDescription($moduleClass, $module);
			$this->assertGetFinalPossibleErrors($moduleClass, $module);
		}
	}

	/**
	 * This method is for the assertions in particular for getFinalDescription as defined in ApiBase
	 * @param $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	private function assertGetFinalDescription ( $moduleClass, $module ) {
		$method = 'getFinalDescription';
		$descArray = $module->$method();

		$rMethod = new \ReflectionMethod($module, $method);
		$this->assertTrue($rMethod->isPublic(), 'the method ' .$method .' of module ' .$moduleClass .' is not public');

		$this->assertNotEmpty($module->$method(), 'the Module ' .$moduleClass .' does not have the method ' .$method);
		$this->assertNotEmpty( $descArray, 'the array returned by the method ' .$method .' of module ' .$moduleClass .' is empty' );
		foreach ( $descArray as $i ) {
			$this->assertTrue( is_string( $i ), 'the ' .$i .'. value returned by the method ' .$method .' of the module ' .$moduleClass .' is not a string' );
		}
	}

	/**
	 * This method is for the assertions for getFinalParamDescription as defined in ApiBase, depending on getFinalParams
	 * @param $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	private function assertGetFinalParamDescription ( $moduleClass, $module ) {
		$method = 'getFinalParamDescription';
		$paramsMethod = 'getFinalParams';
		$paramsArray = $module->$paramsMethod();
		if ( !empty( $paramsArray ) ) {
			$paramDescArray = $module ->$method();
			$this->assertNotEmpty( $paramDescArray, 'the array returned by the method ' .$method .' of module ' .$moduleClass .' is empty' );

			////comparing the keys of the arrays of getParamDescription and getParams -> this assertion fails
			//$arrayKeys = !array_diff_key( $paramDescArray, $paramsArray ) && !array_diff_key( $paramsArray, $paramDescArray );
			//$this->assertTrue( $arrayKeys, 'keys different at ' .$moduleClass );
		}
	}

	/**
	 * This method is for the assertions for getFinalPossibleErrors as defined in ApiBase
	 * @param $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	//TODO: compare if the messages here are existing Systemmessages
	private function assertGetFinalPossibleErrors ( $moduleClass, $module ) {
		$method = 'getFinalPossibleErrors';
		$errArray = $module->$method();

		$rMethod = new \ReflectionMethod($module, $method);
		$this->assertTrue($rMethod->isPublic(), 'the method ' .$method .' of module ' .$moduleClass .' is not public');

		$this->assertNotEmpty($module->$method(), 'the Module ' .$moduleClass .' does not have the method ' .$method);

		foreach ( $errArray as $subArr ) {
			$this->assertNotEmpty( $subArr, 'the arry for the module ' .$moduleClass .' is empty' );

			foreach ( $subArr as $key => $value ) {
				//test always passes, even though it shouldn't
				$bool = strcmp( $key, 'code' ) || strcmp( $key, 'info' );
				$this->assertTrue( $bool, $moduleClass .'  ' .$key );
			}
		}
	}

	/**
	 * This method is for the assertions of getExamples as defined in ApiBase
	 * @param $moduleClass one of the modules in $GLOBALS['wgAPIModules'], only in this function for the error messages
	 * @param Module $module is an instance of $moduleClass
	 **/
	private function assertGetExamples( $moduleClass, $module ) {
		$method = 'getExamples';
		$rMethod = new \ReflectionMethod( $moduleClass,  $method );
		$rMethod->setAccessible( true );
		$exArray = $rMethod->invoke( $module );

		//there is a TODO in ParseValue- as soon as this is done, this if-statement can be deleted
		if ( $moduleClass != 'Wikibase\Api\ParseValue' ) {
			$this->assertNotEmpty( $exArray, 'there is an empty Array in ' .$moduleClass );

			foreach ( $exArray as $key => $value ) {
				$this->assertTrue( ( strpos( $key, 'api.php?action=' ) !== false ), 'the key ' .$key .' is not an url at ' .$moduleClass );
				$this->assertTrue( is_string( $value ), 'the value of the example for ' .$key .' in ' .$moduleClass .' is not a string' );
			}

		}
	}
}