<?php


namespace Wikibase\View\Tests;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\View\EntityMetaTags;
use Wikibase\View\FingerprintableEntityMetaTags;

/**
 * @covers Wikibase|View|FingerprintableEntityMetaTags
 *
 * @group Wikibase
 */
class FingerprintableEntityMetaTagsTest extends EntityMetaTagsTest {

	public function provideTestGetMetaTags() {
		$mock = $this->getMockBuilder(LanguageFallbackChain::class)
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( $input ) {
				$langString = $input['en'] ?? null;
				if ($langString !== null) {
					return array('value' => $langString);
				}
				return;
			} ) );


		$fingerprintableEntityMetaTags = new FingerprintableEntityMetaTags( $mock );
		return [
			[
				$fingerprintableEntityMetaTags,
				new Item(new ItemId('Q365287')),
				array('title' => 'Q365287')
			],
			[
				$fingerprintableEntityMetaTags,
				new Item(new ItemId('Q538250'), $this->getEnglishFingerprint('foo', 'bar')),
				array(
					'title' => 'foo',
					'description' => 'bar'
				)
			],
		];
	}

	private function getEnglishFingerprint($title, $description) {
		return new Fingerprint(
			new TermList(
				array (new Term('en', $title) )
			),
			new TermList(
				array ( new Term('en', $description) )
			)
		);
	}

}
