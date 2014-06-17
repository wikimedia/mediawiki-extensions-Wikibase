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
	public function testGetHtmlForEditSection( $expected, $pageName, $action, $enabled, $langCode ) {
		$generator = new SectionEditLinkGenerator();

		$key = $action === 'add' ? 'wikibase-add' : 'wikibase-edit';
		$msg = wfMessage( $key )->inLanguage( $langCode );

		$editSectionHtml = $generator->getHtmlForEditSection( $pageName, array(), $msg, $enabled );
		$matcher = array(
			'tag' => 'span',
			'class' => 'wb-editsection'
		);

		$this->assertTag( $matcher, $editSectionHtml, "$action action" );
		$this->assertRegExp( $expected, $editSectionHtml, "$action button label" );
	}

	public function getHtmlForEditSectionProvider() {
		return array(
			array(
				'/' . wfMessage( 'wikibase-edit' )->inLanguage( 'es' )->text() . '/',
				'Version',
				'edit',
				true,
				'es'
			),
			array(
				'/' . wfMessage( 'wikibase-add' )->inLanguage( 'de' )->text() . '/',
				'Version',
				'add',
				true,
				'de'
			)
		);
	}

	/**
	 * @dataProvider getHtmlForEditSection_editUrlProvider
	 * @covers SectionEditLinkGenerator::getHtmlForEditSection
	 */
	public function testGetHtmlForEditSection_editUrl( $expected, $specialPageName, $specialPageParams ) {
		$generator = new SectionEditLinkGenerator();

		$editSection = $generator->getHtmlForEditSection( $specialPageName, $specialPageParams, wfMessage( 'wikibase-add' ) );

		$this->assertTag( $expected, $editSection );
	}

	public function getHtmlForEditSection_editUrlProvider() {
		return array(
			array(
				array(
					'tag' => 'a',
					'attributes' => array(
						'href' => 'regexp:+' . preg_quote( SpecialPageFactory::getLocalNameFor( 'Version' ), '+' ) . '/Q1$+',
					)
				),
				'Version',
				array( 'Q1' )
			),
			array(
				array(
					'tag' => 'a',
					'attributes' => array(
						'href' => 'regexp:+' . preg_quote( SpecialPageFactory::getLocalNameFor( 'Version' ), '+' ) . '/Q1/de$+',
						'href' => 'regexp:+Special:Version/Q1/de+'
					)
				),
				'Version',
				array( 'Q1', 'de' ),
			)
		);
	}

}
