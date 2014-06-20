<?php

namespace Wikibase\Test;

use Language;
use SpecialPageFactory;
use Wikibase\Datamodel\Entity\Entity;
use Wikibase\Datamodel\Entity\Item;
use Wikibase\Repo\View\SectionEditLinkGenerator;

/**
 * @covers Wikibase\Repo\View\SectionEditLinkGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 * @author Daniel Kinzler
 * @author Adrian Lang
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

	/**
	 * @dataProvider getEditUrlProvider
	 */
	public function testGetEditUrl( $expected, $specialpagename, Entity $entity, $language = null ) {
		$generator = new SectionEditLinkGenerator();

		$editUrl = $generator->getEditUrl( $specialpagename, $entity, $language );

		$this->assertRegExp( $expected, $editUrl );
	}

	public function getEditUrlProvider() {
		return array(
			array(
				'+' . preg_quote( SpecialPageFactory::getLocalNameFor( 'Version' ), '+' ) . '/Q1$+',
				'Version',
				new Item( array( 'entity' => 'Q1' ) )
			),
			array(
				'+' . preg_quote( SpecialPageFactory::getLocalNameFor( 'Version' ), '+' ) . '/Q1/de$+',
				'Version',
				new Item( array( 'entity' => 'Q1' ) ),
				Language::factory( 'de' )
			)
		);
	}

}
