<?php

namespace Wikibase\Lib\Test;

use OutOfBoundsException;
use Title;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers Wikibase\Lib\EntityIdHtmlLinkFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class EntityIdHtmlLinkFormatterTest extends \PHPUnit_Framework_TestCase {

	private function getLabelLookup() {
		$labelLookup = $this->getMock( 'Wikibase\Lib\Store\LabelLookup' );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( 'A label' ) );

		return $labelLookup;
	}

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
				'lookupLabel'	=> true
			),
			"has no label" => array(
				'expectedRegex'	=> '/' . $escapedItemUrl . '.*>Q42</',
				'lookupLabel'	=> true,
				'hasLabel'		=> false
			),
			"doesn't exist, lookup labels" => array(
				'expectedRegex'	=> '/^Q42' . preg_quote( wfMessage( 'word-separator' )->text(), '/' ) . '.*>' .
					preg_quote( wfMessage( 'parentheses', wfMessage( 'wikibase-deletedentity-item' )->text() )->text(), '/' ) .
					'</',
				'lookupLabel'	=> true,
				'hasLabel'		=> false,
				'exists'		=> false
			),
			"doesn't exist, don't lookup labels" => array(
				'expectedRegex'	=> '/' . $escapedItemUrl . '.*>Q42</',
				'lookupLabel'	=> false,
				'hasLabel'		=> false,
				'exists'		=> false
			),
			"Don't lookup labels, has label" => array(
				'expectedRegex'	=> '/' . $escapedItemUrl . '.*>Q42</',
				'lookupLabel'	=> false
			),
			"Don't lookup labels, no label" => array(
				'expectedRegex'	=> '/' . $escapedItemUrl . '.*>Q42</',
				'lookupLabel'	=> false,
				'hasLabel'		=> false
			),
		);
	}

	/**
	 * @dataProvider formatProvider
	 *
	 * @param string $expectedRegex
	 * @param bool $lookupLabel
	 * @param bool $hasLabel
	 * @param bool $exists
	 */
	public function testFormat( $expectedRegex, $lookupLabel, $hasLabel = true, $exists = true ) {
		$options = new FormatterOptions( array( EntityIdHtmlLinkFormatter::OPT_LOOKUP_LABEL => $lookupLabel ) );

		if ( $hasLabel ) {
			$labelLookup = $this->getLabelLookup();
		} else {
			$labelLookup = $this->getLabelLookupNoLabel();
		}

		$entityTitleLookup = $this->newEntityTitleLookup( $exists );

		$entityIdHtmlLinkFormatter = new EntityIdHtmlLinkFormatter( $options, $labelLookup, $entityTitleLookup );
		$result = $entityIdHtmlLinkFormatter->format( new ItemId( 'Q42' ) );

		$this->assertRegExp( $expectedRegex, $result );
	}

}
