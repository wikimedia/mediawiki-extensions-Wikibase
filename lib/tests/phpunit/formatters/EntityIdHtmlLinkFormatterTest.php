<?php

namespace Wikibase\Lib\Test;

use OutOfBoundsException;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LabelLookup;

/**
 * @covers Wikibase\Lib\EntityIdHtmlLinkFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityIdHtmlLinkFormatterTest extends \MediaWikiTestCase {

	/**
	 * @param Term $term
	 *
	 * @return LabelLookup
	 */
	private function getLabelLookup( Term $term = null ) {
		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $term ?: new Term( 'xy', 'A label' ) ) );

		return $labelLookup;
	}

	/**
	 * @return LabelLookup
	 */
	private function getLabelLookupNoLabel() {
		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->throwException( new OutOfBoundsException( 'meep' ) ) );

		return $labelLookup;
	}

	/**
	 * @param bool $exists
	 *
	 * @return EntityTitleLookup
	 */
	private function newEntityTitleLookup( $exists = true ) {
		$entityTitleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function ( EntityId $entityId ) use ( $exists ) {
				$title = Title::newFromText( $entityId->getSerialization() );
				$title->resetArticleID( $exists ? $entityId->getNumericId() : 0 );

				return $title;
			} )
		);

		return $entityTitleLookup;
	}

	public function formatProvider() {
		$escapedItemUrl = preg_quote( Title::newFromText( 'Q42' )->getLocalURL(), '/' );

		return array(
			'has a label' => array(
				'expectedRegex'	=> '/' . $escapedItemUrl . '.*>A label</',
			),
			"has no label" => array(
				'expectedRegex'	=> '/' . $escapedItemUrl . '.*>Q42</',
				'hasLabel'		=> false
			),
			"doesn't exist, lookup labels" => array(
				'expectedRegex'	=> '/^Q42' . preg_quote( wfMessage( 'word-separator' )->text(), '/' ) . '.*>' .
					preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '/' ) .
					'</',
				'hasLabel'		=> false,
				'exists'		=> false
			),
		);
	}

	private function getFormatter( $hasLabel, $exists, $term = null ) {
		if ( $hasLabel ) {
			$labelLookup = $this->getLabelLookup( $term );
		} else {
			$labelLookup = $this->getLabelLookupNoLabel();
		}

		$entityTitleLookup = $this->newEntityTitleLookup( $exists );

		$languageNameLookup = $this->getMock( 'Wikibase\Lib\LanguageNameLookup' );
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
			$labelLookup,
			$entityTitleLookup,
			$languageNameLookup
		);

		return $entityIdHtmlLinkFormatter;
	}

	/**
	 * @dataProvider formatProvider
	 *
	 * @param string $expectedRegex
	 * @param bool $lookupLabel
	 * @param bool $hasLabel
	 * @param bool $exists
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
				'expectedRegex'	=> '@>Kätzchen<@',
				'term'	=> $deTerm,
			),
			'plain fallabck term' => array(
				'expectedRegex'	=> '@>Kätzchen<@',
				'term'	=> $deTermFallback,
			),
			'fallback to base' => array(
				'expectedRegex'	=> '@ lang="de">Kätzchen</a><sup class="wb-language-fallback-indicator wb-language-fallback-variant">Deutsch</sup>@',
				'term'	=> $deAtTerm,
			),
			'fallback to variant' => array(
				'expectedRegex'	=> '@ lang="de-at">Kätzchen</a><sup class="wb-language-fallback-indicator wb-language-fallback-variant">Österreichisches Deutsch</sup>@',
				'term'	=> $atDeTerm,
			),
			'transliteration to requested language' => array(
				'expectedRegex'	=> '@>Frass</a><sup class="wb-language-fallback-indicator wb-language-fallback-transliteration">'
					. preg_quote( $translitDeCh, '@' )
					. '</sup>@',
				'term'	=> $deChTerm,
			),
			'transliteration to other variant' => array(
				'expectedRegex'	=> '@ lang="en">Kitten</a><sup class="wb-language-fallback-indicator wb-language-fallback-transliteration wb-language-fallback-variant">'
					. preg_quote( $translitEnCa, '@' )
					. '</sup>@',
				'term'	=> $enGbEnCaTerm,
			),
			'fallback to alternative language' => array(
				'expectedRegex'	=> '@ lang="en">Kitten</a><sup class="wb-language-fallback-indicator">english in german</sup>@',
				'term'	=> $deEnTerm,
			),
		);
	}

	/**
	 * @dataProvider formatProvider_fallback
	 *
	 * @param string $expectedRegex
	 * @param TermFallback $term
	 */
	public function testFormat_fallback( $expectedRegex, $term ) {
		$entityIdHtmlLinkFormatter = $this->getFormatter( true, true, $term );

		$result = $entityIdHtmlLinkFormatter->formatEntityId( new ItemId( 'Q42' ) );

		$this->assertRegExp( $expectedRegex, $result );
	}

}
