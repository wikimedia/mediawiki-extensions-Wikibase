<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\EntitySearchTextGenerator;

/**
 * @covers Wikibase\Repo\EntitySearchTextGenerator
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntitySearchTextGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function generateProvider() {
		$item = Item::newEmpty();

		$item->getFingerprint()->setLabel( 'en', 'Test' );
		$item->getFingerprint()->setLabel( 'de', 'Testen' );
		$item->getFingerprint()->setDescription( 'en', 'city in Spain' );
		$item->getFingerprint()->setAliasGroup( 'en', array( 'abc', 'cde' ) );
		$item->getFingerprint()->setAliasGroup( 'de', array( 'xyz', 'uvw' ) );

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
			array( $item, $patterns )
		);
	}

	/**
	 * @dataProvider generateProvider
	 *
	 * @param EntityDocument $entity
	 * @param array $patterns
	 */
	public function testGenerate( EntityDocument $entity, array $patterns ) {
		$generator = new EntitySearchTextGenerator();
		$text = $generator->generate( $entity );

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}

	public function testGivenEntityWithoutFingerprint_emptyStringIsReturned() {
		$generator = new EntitySearchTextGenerator();
		$entityDocument = $this->getMock( 'Wikibase\DataModel\Entity\EntityDocument' );
		$this->assertSame( '', $generator->generate( $entityDocument ) );
	}

}
