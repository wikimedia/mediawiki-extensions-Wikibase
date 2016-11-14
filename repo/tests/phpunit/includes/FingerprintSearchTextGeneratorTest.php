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
 * @license GPL-2.0+
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
		$fingerprint->setAliasGroup( 'en', [ 'abc', 'cde' ] );
		$fingerprint->setAliasGroup( 'de', [ 'xyz', 'uvw' ] );

		$patterns = [
			'/^Test$/',
			'/^Testen$/',
			'/^city in Spain$/',
			'/^abc$/',
			'/^cde$/',
			'/^uvw$/',
			'/^xyz$/',
			'/^(?!abcde).*$/',
		];

		return [
			[ $fingerprint, $patterns ]
		];
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

	public function testGivenEmptyEntity_emptyStringIsReturned() {
		$generator = new FingerprintSearchTextGenerator();
		$fingerprint = new Fingerprint();
		$text = $generator->generate( $fingerprint );

		$this->assertSame( '', $text );
	}

	public function testGivenUntrimmedLabel_generateDoesNotTrim() {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', ' untrimmed label ' );
		$generator = new FingerprintSearchTextGenerator();
		$text = $generator->generate( $fingerprint );

		$this->assertSame( " untrimmed label ", $text );
	}

}
