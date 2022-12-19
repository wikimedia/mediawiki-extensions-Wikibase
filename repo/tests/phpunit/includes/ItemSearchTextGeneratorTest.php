<?php

namespace Wikibase\Repo\Tests;

use MediaWikiTestCaseTrait;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\ItemSearchTextGenerator;

/**
 * @covers \Wikibase\Repo\ItemSearchTextGenerator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class ItemSearchTextGeneratorTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	public function generateProvider() {
		$item = new Item();

		$item->setLabel( 'en', 'Test' );
		$item->setLabel( 'de', 'Testen' );
		$item->setDescription( 'en', 'city in Spain' );
		$item->setAliases( 'en', [ 'abc', 'cde' ] );
		$item->setAliases( 'de', [ 'xyz', 'uvw' ] );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Berlin' );
		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Rome' );

		$patterns = [
			'/^Test$/',
			'/^Testen$/',
			'/^city in Spain$/',
			'/^abc$/',
			'/^cde$/',
			'/^uvw$/',
			'/^xyz$/',
			'/^(?!abcde).*$/',
			'/^Berlin$/',
			'/^Rome$/',
		];

		return [
			[ $item, $patterns ],
		];
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
			$this->assertMatchesRegularExpression( $pattern . 'm', $text );
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
