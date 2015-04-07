<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\FingerprintSearchTextGenerator;

/**
 * @covers Wikibase\Repo\FingerprintSearchTextGenerator
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintSearchTextGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function generateProvider() {
		$fingerprint = new Fingerprint();

		$fingerprint->setLabel( 'en', 'Test' );
		$fingerprint->setLabel( 'de', 'Testen' );
		$fingerprint->setDescription( 'en', 'city in Spain' );
		$fingerprint->setAliasGroup( 'en', array( 'abc', 'cde' ) );
		$fingerprint->setAliasGroup( 'de', array( 'xyz', 'uvw' ) );

		$patterns = array(
			'/^Test$/',
			'/^Testen$/',
			'/^city in Spain$/',
			'/^abc$/',
			'/^cde$/',
			'/^uvw$/',
			'/^xyz$/',
			'/^(?!abcde).*$/',
		);

		return array(
			array( $fingerprint, $patterns )
		);
	}

	/**
	 * @dataProvider generateProvider
	 * @param Fingerprint $fingerprint
	 * @param string[] $patterns
	 */
	public function testGenerate( Fingerprint $fingerprint, array $patterns ) {
		$generator = new FingerprintSearchTextGenerator();
		$text = $generator->generate( $fingerprint );

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}

	public function testGivenEmptyEntity_newlineIsReturned() {
		$generator = new FingerprintSearchTextGenerator();
		$fingerprint = new Fingerprint();
		$text = $generator->generate( $fingerprint );

		$this->assertSame( "\n", $text );
	}

	public function testGivenUntrimmedLabel_generateDoesNotTrim() {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', ' untrimmed label ' );
		$generator = new FingerprintSearchTextGenerator();
		$text = $generator->generate( $fingerprint );

		$this->assertSame( " untrimmed label \n", $text );
	}

}
