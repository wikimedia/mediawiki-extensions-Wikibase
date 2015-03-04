<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use SpecialPage;
use SpecialPageFactory;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\View\ToolbarEditSectionGenerator;
use Wikibase\Template\TemplateFactory;
use Wikibase\Template\TemplateRegistry;

/**
 * @covers Wikibase\Repo\View\ToolbarEditSectionGeneratorTest
 *
 * @uses Wikibase\Template\Template
 * @uses Wikibase\Template\TemplateFactory
 * @uses Wikibase\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Adrian Heine
 */
class ToolbarEditSectionGeneratorTest extends MediaWikiTestCase {

	protected function setUp() {
		// Make sure wgSpecialPages has the special pages this class uses
		$this->setMwGlobals(
			'wgSpecialPages',
			array(
				'SetSiteLink' => new SpecialPage( 'SetSiteLink' ),
				'SetLabelDescriptionAliases' => new SpecialPage( 'SetLabelDescriptionAliases' )
			)
		);

		SpecialPageFactory::resetList();
		$doubleLanguage = $this->getMock( 'Language', array( 'getSpecialPageAliases' ) );
		$doubleLanguage->mCode = 'en';
		$doubleLanguage->expects( $this->any() )
			->method( 'getSpecialPageAliases' )
			->will( $this->returnValue(
				array(
					'SetSiteLink' => array( 'SetSiteLink' ),
					'SetLabelDescriptionAliases' => array( 'SetLabelDescriptionAliases' )
				)
			) );

		$this->setMwGlobals(
			'wgContLang',
			$doubleLanguage
		);
		parent::setUp();
	}

	/**
	 * @dataProvider getAddStatementToGroupSectionProvider
	 */
	public function testGetAddStatementToGroupSection( $propertyId, $entityId ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertEquals(
			'<span class="wikibase-toolbar-container"></span>',
			$generator->getAddStatementToGroupSection( $propertyId, $entityId )
		);
	}

	public function getAddStatementToGroupSectionProvider() {
		return array(
			array( new PropertyId( 'P1' ), null )
		);
	}

	/**
	 * @dataProvider getLabelDescriptionAliasesEditSectionProvider
	 */
	public function testGetLabelDescriptionAliasesEditSection( $languageCode, $entityId, $expectedMatch ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertRegExp(
			$expectedMatch,
			$generator->getLabelDescriptionAliasesEditSection( $languageCode, $entityId )
		);

	}

	public function getLabelDescriptionAliasesEditSectionProvider() {
		return array(
			array(
				'en',
				new PropertyId( 'P1' ),
				'/Special:SetLabelDescriptionAliases\/P1\/en/'
			)
		);
	}

	/**
	 * @dataProvider getSiteLinksEditSectionProvider
	 */
	public function testGetSiteLinksEditSection( $entityId, $expectedMatch ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertRegExp( $expectedMatch, $generator->getSiteLinksEditSection( $entityId ) );

	}

	public function getSiteLinksEditSectionProvider() {
		return array(
			array( new PropertyId( 'P1' ), '/Special:SetSiteLink\/P1/' )
		);
	}

	/**
	 * @dataProvider getStatementEditSection
	 */
	public function testGetStatementEditSection( $statement ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertEquals(
			'<span class="wikibase-toolbar-container"></span>',
			$generator->getStatementEditSection( $statement )
		);

	}

	public function getStatementEditSection() {
		return array(
			array( new Statement( new Claim( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ) ) )
		);
	}

	private function newToolbarEditSectionGenerator() {
		return new ToolbarEditSectionGenerator(
			new TemplateFactory( TemplateRegistry::getDefaultInstance() )
		);
	}

}
