<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ItemSearchTextGenerator;

/**
 * @covers Wikibase\Repo\ItemSearchTextGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class ItemSearchTextGeneratorTest extends \PHPUnit_Framework_TestCase {

	public function generateProvider() {
		$item = new Item();

		$item->setLabel( 'en', 'Test' );
		$item->setLabel( 'de', 'Testen' );
		$item->setDescription( 'en', 'city in Spain' );
		$item->setAliases( 'en', array( 'abc', 'cde' ) );
		$item->setAliases( 'de', array( 'xyz', 'uvw' ) );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Rome' );

		$patterns = array(
			'/^Test$/',
			'/^Testen$/',
			'/^city in Spain$/',
			'/^abc$/',
			'/^cde$/',
			'/^uvw$/',
			'/^xyz$/',
			'/^(?!abcde).*$/',
			'/^Berlin$/',
			'/^Rome$/'
		);

		return array(
			array( $item, $patterns )
		);
	}

	/**
	 * @dataProvider generateProvider
	 * @param Item $item
	 * @param string[] $patterns
	 */
	public function testGenerate( Item $item, array $patterns ) {
		$generator = new ItemSearchTextGenerator();
		$text = $generator->generate( $item );

		foreach ( $patterns as $pattern ) {
			$this->assertRegExp( $pattern . 'm', $text );
		}
	}

	public function testGivenEmptyItem_emptyStringIsReturned() {
		$generator = new ItemSearchTextGenerator();
		$item = new Item();
		$text = $generator->generate( $item );

		$this->assertSame( '', $text );
	}

	public function testGivenUntrimmedPageName_generateDoesNotTrim() {
		$item = new Item();
		$item->getFingerprint()->setLabel( 'en', ' untrimmed label ' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', ' untrimmed pageName ' );
		$generator = new ItemSearchTextGenerator();
		$text = $generator->generate( $item );

		$this->assertSame( " untrimmed label \n untrimmed pageName ", $text );
	}

}
