<?php

namespace Wikibase\Repo\Tests;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\FingerprintSearchTextGenerator;

/**
 * @covers \Wikibase\Repo\FingerprintSearchTextGenerator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FingerprintSearchTextGeneratorTest extends \PHPUnit\Framework\TestCase {

	public function testGenerate() {
		$entity = new Item();
		$entity->setLabel( 'en', 'Test' );
		$entity->setLabel( 'de', 'Testen' );
		$entity->setDescription( 'en', 'city in Spain' );
		$entity->setAliases( 'en', [ 'abc', 'cde' ] );
		$entity->setAliases( 'de', [ 'xyz', 'uvw' ] );

		$generator = new FingerprintSearchTextGenerator();
		$text = $generator->generate( $entity );

		$this->assertSame( "Test\nTesten\ncity in Spain\nabc\ncde\nxyz\nuvw", $text );
	}

	public function testGivenEmptyEntity_emptyStringIsReturned() {
		$entity = new Item();

		$generator = new FingerprintSearchTextGenerator();
		$text = $generator->generate( $entity );

		$this->assertSame( '', $text );
	}

	public function testGivenUntrimmedLabel_generateDoesNotTrim() {
		$entity = new Item();
		$entity->setLabel( 'en', ' untrimmed label ' );

		$generator = new FingerprintSearchTextGenerator();
		$text = $generator->generate( $entity );

		$this->assertSame( ' untrimmed label ', $text );
	}

}
