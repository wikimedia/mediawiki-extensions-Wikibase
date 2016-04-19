<?php

namespace Wikibase\Lib\Test;

use MediaWikiTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers Wikibase\Lib\EntityIdHtmlLinkFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityIdHtmlLinkFormatterTest extends MediaWikiTestCase {

	/**
	 * @param Term|null $term
	 *
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookup( Term $term = null ) {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $term ?: new Term( 'xy', 'A label' ) ) );

		return $labelDescriptionLookup;
	}

	/**
	 * @return LabelDescriptionLookup
	 */
	private function getLabelDescriptionLookupNoLabel() {
		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->throwException( new LabelDescriptionLookupException(
				new ItemId( 'Q100' ),
				'meep'
			) ) );

		return $labelDescriptionLookup;
	}

	/**
	 * @param bool $exists
	 *
	 * @return EntityTitleLookup
	 */
	private function newEntityTitleLookup( $exists = true ) {
		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function ( EntityId $id ) use ( $exists ) {
				$title = Title::newFromText( $id->getSerialization() );
				$title->resetArticleID( $exists ? $id->getNumericId() : 0 );

				return $title;
			} )
		);

		return $entityTitleLookup;
	}

	public function formatProvider() {
		$escapedItemUrl = preg_quote( Title::newFromText( 'Q42' )->getLocalURL(), '/' );

		return array(
			'has a label' => array(
				'expectedRegex' => '/' . $escapedItemUrl . '.*>A label</',
			),
			"has no label" => array(
				'expectedRegex' => '/' . $escapedItemUrl . '.*>Q42</',
				'hasLabel' => false
			),
			"doesn't exist, lookup labels" => array(
				'expectedRegex' => '/^Q42' . preg_quote( wfMessage( 'word-separator' )->text(), '/' ) . '.*>' .
					preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '/' ) .
					'</',
				'hasLabel' => false,
				'exists' => false
			),
		);
	}

	private function getFormatter( $hasLabel, $exists, Term $term = null ) {
		if ( $hasLabel ) {
			$labelDescriptionLookup = $this->getLabelDescriptionLookup( $term );
		} else {
			$labelDescriptionLookup = $this->getLabelDescriptionLookupNoLabel();
		}

		$entityTitleLookup = $this->newEntityTitleLookup( $exists );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnCallback( function( $languageCode ) {
				$names = array(
						'de' => 'Deutsch',
						'de-at' => 'Österreichisches Deutsch',
						'de-ch' => 'Schweizer Hochdeutsch',
						'en' => 'english in german',
						'en-ca' => 'Canadian English'
				);
				return $names[ $languageCode ];
			} ) );

		$entityIdHtmlLinkFormatter = new EntityIdHtmlLinkFormatter(
			$labelDescriptionLookup,
			$entityTitleLookup,
			$languageNameLookup
		);

		return $entityIdHtmlLinkFormatter;
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $expectedRegex, $hasLabel = true, $exists = true ) {
		$entityIdHtmlLinkFormatter = $this->getFormatter( $hasLabel, $exists );
		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q42' ) );

		$this->assertRegExp( $expectedRegex, $result );
	}

	public function formatProvider_fallback() {
		$deTerm = new Term( 'de', 'Kätzchen' );
		$deTermFallback = new TermFallback( 'de', 'Kätzchen', 'de', 'de' );
		$deAtTerm = new TermFallback( 'de-at', 'Kätzchen', 'de', 'de' );
		$atDeTerm = new TermFallback( 'de', 'Kätzchen', 'de-at', 'de-at' );
		$deChTerm = new TermFallback( 'de-ch', 'Frass', 'de-ch', 'de' );
		$enGbEnCaTerm = new TermFallback( 'en-gb', 'Kitten', 'en', 'en-ca' );
		$deEnTerm = new TermFallback( 'de', 'Kitten', 'en', 'en' );

		$translitDeCh = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Deutsch', 'Schweizer Hochdeutsch' )->text();
		$translitEnCa = wfMessage( 'wikibase-language-fallback-transliteration-hint', 'Canadian English', 'English' )->text();

		return array(
			'plain term' => array(
				'expectedRegex' => '@>Kätzchen<@',
				'term' => $deTerm,
			),
			'plain fallabck term' => array(
				'expectedRegex' => '@>Kätzchen<@',
				'term' => $deTermFallback,
			),
			'fallback to base' => array(
				'expectedRegex' => '@ lang="de">Kätzchen</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-variant">Deutsch</sup>@',
				'term' => $deAtTerm,
			),
			'fallback to variant' => array(
				'expectedRegex' => '@ lang="de-at">Kätzchen</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-variant">Österreichisches Deutsch</sup>@',
				'term' => $atDeTerm,
			),
			'transliteration to requested language' => array(
				'expectedRegex' => '@>Frass</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-transliteration">'
					. preg_quote( $translitDeCh, '@' )
					. '</sup>@',
				'term' => $deChTerm,
			),
			'transliteration to other variant' => array(
				'expectedRegex' => '@ lang="en">Kitten</a><sup class="wb-language-fallback-'
					. 'indicator wb-language-fallback-transliteration wb-language-fallback-'
					. 'variant">'
					. preg_quote( $translitEnCa, '@' )
					. '</sup>@',
				'term' => $enGbEnCaTerm,
			),
			'fallback to alternative language' => array(
				'expectedRegex' => '@ lang="en">Kitten</a><sup class="wb-language-fallback-'
					. 'indicator">english in german</sup>@',
				'term' => $deEnTerm,
			),
		);
	}

	/**
	 * @dataProvider formatProvider_fallback
	 */
	public function testFormat_fallback( $expectedRegex, Term $term ) {
		$entityIdHtmlLinkFormatter = $this->getFormatter( true, true, $term );

		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q42' ) );

		$this->assertRegExp( $expectedRegex, $result );
	}

}
