<?php

namespace Wikibase\Test;

use Language;
use Wikibase\Datamodel\Entity\Entity;
use Wikibase\Datamodel\Entity\Item;
use Wikibase\Repo\View\SectionEditLinkGenerator;

/**
 * @covers Wikibase\SectionEditLinkGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 * @author Daniel Kinzler
 */
class SectionEditLinkGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getHtmlForEditSectionProvider
	 */
	public function testGetHtmlForEditSection( $expected, $url, $tag, $action, $enabled, $langCode ) {
		$generator = new SectionEditLinkGenerator();

		$key = $action === 'add' ? 'wikibase-add' : 'wikibase-edit';
		$msg = wfMessage( $key )->inLanguage( $langCode );

		$editSectionHtml = $generator->getHtmlForEditSection( $url, $msg, $tag, $enabled );
		$matcher = array(
			'tag' => $tag,
			'class' => 'wb-editsection'
		);

		$this->assertTag( $matcher, $editSectionHtml, "$action action" );
		$this->assertRegExp( $expected, $editSectionHtml, "$action button label" );
	}

	public function getHtmlForEditSectionProvider() {
		return array(
			array(
				'/' . wfMessage( 'wikibase-edit' )->inLanguage( 'es' )->text() . '/',
				'',
				'div',
				'edit',
				true,
				'es'
			),
			array(
				'/' . wfMessage( 'wikibase-add' )->inLanguage( 'de' )->text() . '/',
				'',
				'span',
				'add',
				true,
				'de'
			)
		);
	}


}
