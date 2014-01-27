<?php

namespace Wikibase\Test;

use Wikibase\Entity;
use Wikibase\Item;
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

		$item->setLabel( 'en', 'Test' );
		$item->setLabel( 'de', 'Testen' );
		$item->setDescription( 'en', 'city in Spain' );
		$item->setAliases( 'en', array( 'abc', 'cde' ) );
		$item->setAliases( 'de', array( 'xyz', 'uvw' ) );

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
	 * @param Entity $entity
	 * @param array $patterns
	 */
	public function testGenerate( Entity $entity, array $patterns ) {
		$generator = new EntitySearchTextGenerator();
		$text = $generator->generate( $entity );

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}

}
