<?php

namespace Wikibase\Test;

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
class SectionEditLinkGeneratorTest extends \MediaWikiLangTestCase {

	/**
	 * @dataProvider getHtmlForEditSectionProvider
	 */
	public function testGetHtmlForEditSection( $expected, $pageName, $action, $enabled, $langCode ) {
		$generator = new SectionEditLinkGenerator();

		$key = $action === 'add' ? 'wikibase-add' : 'wikibase-edit';
		$msg = wfMessage( $key )->inLanguage( $langCode );

		$html = $generator->getHtmlForEditSection( $pageName, array(), $msg, $enabled );
		$matcher = array(
			'tag' => 'span',
			'class' => 'wikibase-toolbar'
		);

		$this->assertTag( $matcher, $html, "$action action" );
		$this->assertRegExp( $expected, $html, "$action button label" );
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
	 */
	public function testGetHtmlForEditSection_editUrl( $expected, $specialPageName, $specialPageParams ) {
		$generator = new SectionEditLinkGenerator();

		$html = $generator->getHtmlForEditSection( $specialPageName, $specialPageParams, wfMessage( 'wikibase-add' ) );

		$this->assertTag( $expected, $html );
	}

	public function getHtmlForEditSection_editUrlProvider() {
		return array(
			array(
				array(
					'tag' => 'a',
					'attributes' => array( 'href' => 'regexp:+\bSpecial:Version/Q1$+' )
				),
				'Version',
				array( 'Q1' )
			),
			array(
				array(
					'tag' => 'a',
					'attributes' => array( 'href' => 'regexp:+\bSpecial:SetLabel/Q1/de$+' )
				),
				'SetLabel',
				array( 'Q1', 'de' ),
			)
		);
	}

	/**
	 * @dataProvider getHtmlForEditSection_disabledProvider
	 */
	public function testGetHtmlForEditSection_disabled( $specialPageName, $specialPageUrlParams, $enabled ) {
		$generator = new SectionEditLinkGenerator();

		$html = $generator->getHtmlForEditSection(
			$specialPageName,
			$specialPageUrlParams,
			wfMessage( 'wikibase-edit' ),
			$enabled
		);

		$this->assertNotTag( array(
			'tag' => 'a',
			'attributes' => array( 'href' => 'regexp:+\bSpecial:SetLabel\b+' )
		), $html );
		$this->assertTag( array(
			'tag' => 'span',
			'attributes' => array( 'class' => 'ui-state-disabled' )
		), $html );
	}

	public function getHtmlForEditSection_disabledProvider() {
		return array(
			array( 'SetLabel', array( 'Q1' ), false ),
			array( 'SetLabel', array(), true ),
			array( null, array( 'Q1' ), true ),
		);
	}

}
