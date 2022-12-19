<?php

namespace Wikibase\View\Tests;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\View\FingerprintableEntityMetaTagsCreator;

/**
 * @covers \Wikibase\View\FingerprintableEntityMetaTagsCreator
 *
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class FingerprintableEntityMetaTagsCreatorTest extends EntityMetaTagsCreatorTestCase {

	public function provideTestGetMetaTags() {
		$mock = $this->createMock( TermLanguageFallbackChain::class );
		$mock->method( 'extractPreferredValue' )
			->willReturnCallback( function( $input ) {
				$langString = $input['en'] ?? null;
				if ( $langString !== null ) {
					return [ 'value' => $langString ];
				}
				return null;
			} );

		$fingerprintableEntityMetaTags = new FingerprintableEntityMetaTagsCreator( $mock );

		yield 'entity meta tags created with Item that has no label or description' => [
			$fingerprintableEntityMetaTags,
			new Item( new ItemId( 'Q365287' ) ),
			[ 'title' => 'Q365287' ],
		];

		yield 'entity meta tags created with Item that has both label and description' => [
			$fingerprintableEntityMetaTags,
			new Item( new ItemId( 'Q538250' ), $this->getEnglishFingerprint( 'foo', 'bar' ) ),
			[
				'title' => 'foo',
				'description' => 'bar',
			],
		];
	}

	private function getEnglishFingerprint( $title, $description ) {
		return new Fingerprint(
			new TermList(
				[ new Term( 'en', $title ) ]
			),
			new TermList(
				[ new Term( 'en', $description ) ]
			)
		);
	}

}
