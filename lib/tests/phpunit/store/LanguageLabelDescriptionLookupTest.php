<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\TermIndexEntry;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTermLookup;
use Wikibase\Lib\Store\LanguageLabelDescriptionLookup;

/**
 * @covers Wikibase\Lib\Store\LanguageLabelDescriptionLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageLabelDescriptionLookupTest extends MediaWikiTestCase {

	public function testGetLabel() {
		$termLookup = $this->getTermLookup();
		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'en' );

		$term = $labelDescriptionLookup->getLabel( new ItemId( 'Q116' ) );

		$this->assertEquals( 'New York City', $term->getText() );
		$this->assertEquals( 'en', $term->getLanguageCode() );
	}

	public function testGetLabel_entityNotFound() {
		$termLookup = $this->getTermLookup();
		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'en' );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelDescriptionLookup->getLabel( new ItemId( 'Q120' ) );
	}

	public function testGetLabel_notFound() {
		$termLookup = $this->getTermLookup();
		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'fa' );

		$this->setExpectedException( 'OutOfBoundsException' );

		$labelDescriptionLookup->getLabel( new ItemId( 'Q116' ) );
	}

	public function testGetDescription() {
		$termLookup = $this->getTermLookup();
		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'en' );

		$term = $labelDescriptionLookup->getDescription( new ItemId( 'Q116' ) );

		$this->assertEquals( 'Big Apple', $term->getText() );
		$this->assertEquals( 'en', $term->getLanguageCode() );
	}

	public function testGetDescription_entityNotFound() {
		$termLookup = $this->getTermLookup();
		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'en' );

		$this->setExpectedException( 'OutOfBoundsException' );
		$labelDescriptionLookup->getDescription( new ItemId( 'Q120' ) );
	}

	public function testGetDescription_notFound() {
		$termLookup = $this->getTermLookup();
		$labelDescriptionLookup = new LanguageLabelDescriptionLookup( $termLookup, 'fa' );

		$this->setExpectedException( 'OutOfBoundsException' );

		$labelDescriptionLookup->getDescription( new ItemId( 'Q116' ) );
	}

	private function getTermLookup() {
		return new EntityTermLookup( $this->getTermIndex() );
	}

	private function getTermIndex() {
		$terms = array(
			new TermIndexEntry( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'New York City'
			) ),
			new TermIndexEntry( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'es',
				'termText' => 'New York City'
			) ),
			new TermIndexEntry( array(
				'entityId' => 116,
				'entityType' => 'item',
				'termType' => 'description',
				'termLanguage' => 'en',
				'termText' => 'Big Apple'
			) ),
			new TermIndexEntry( array(
				'entityId' => 117,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'en',
				'termText' => 'Berlin'
			) ),
			new TermIndexEntry( array(
				'entityId' => 118,
				'entityType' => 'item',
				'termType' => 'label',
				'termLanguage' => 'zh-cn',
				'termText' => '测试'
			) ),
		);

		return new MockTermIndex( $terms );
	}

}
