<?php

namespace Wikibase\Test;

use Wikibase\Repo\View\TocGenerator;

/**
 * @covers Wikibase\Repo\View\TocGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TocGeneratorTest extends \MediaWikiLangTestCase {

	public function testGetHtmlForToc() {
		$tocGenerator = new TocGenerator();
		$tocSections = array(
			'edit-id' => 'wikibase-edit',
			'no-id' => 'placeholder',
			'save-id' => 'wikibase-save'
		);
		$html = $tocGenerator->getHtmlForToc( $tocSections );

		$this->assertContains( '<li class="toclevel-1 tocsection-1"', $html );
		$this->assertContains( '<a href="#edit-id"', $html );
		$this->assertContains( '<span class="toctext">edit</span>', $html );

		$this->assertNotContains( '<li class="toclevel-1 tocsection-2"', $html );
		$this->assertContains( 'placeholder', $html );
		$this->assertNotContains( 'no-id', $html );

		$this->assertContains( '<li class="toclevel-1 tocsection-3"', $html );
		$this->assertContains( '<a href="#save-id"', $html );
		$this->assertContains( '<span class="toctext">save</span>', $html );
	}

	public function testGetHtmlForToc_returnsNothing() {
		
		$tocGenerator = new TocGenerator();
		$tocSections = array(
			'id-1' => 'msg-1',
			'id-2' => 'msg-2'
		);
		$html = $tocGenerator->getHtmlForToc( $tocSections );
		$this->assertEquals( '', $html );
	}

}
