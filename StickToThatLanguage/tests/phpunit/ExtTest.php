<?php

namespace STTLanguage\Test;
use STTLanguage\Ext as Ext;

/**
 * Tests for the STTLanguage\Ext class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup STTLanguage
 * @ingroup Test
 *
 * @group STTLanguage
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
class ExtTest extends \MediaWikiTestCase {
	/**
	 * @group STTLanguage
	 * @dataProvider providerGetUserLanguageCodes
	 */
	public function testGetUserLanguageCodes( $langs, $testMsg ) {
		// create dummy user for test:
		$user = new \User();

		// NOTE: have to get this BEFORE using setOption() on the user, otherwise setOption will leave all other
		//       options to null instead of getting the default options when calling getOption() afterwards!
		$defaultLang = $user->getOption( 'language' );

		foreach( $langs as $code ) {
			$user->setOption( "wb-languages-$code", 1 );
		}

		// users default lang expected to be returned always by getUserLanguageCodes()
		$langs[] = $defaultLang;
		$langs = array_unique( $langs );

		$result = Ext::getUserLanguageCodes( $user );
		$this->assertEquals(
			sort( $langs ),
			sort( $result ),
			$testMsg
		);
	}

	public function providerGetUserLanguageCodes() {
		return array(
			array( array( 'fr', 'de', 'it' ), 'All languages the user set in his options should be returned' ),
			array( array(), 'The users default language should be returned if the never touched his options' ),
		);
	}
}
