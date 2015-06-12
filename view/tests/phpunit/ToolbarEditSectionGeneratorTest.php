<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @covers Wikibase\View\ToolbarEditSectionGenerator
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Adrian Heine
 */
class ToolbarEditSectionGeneratorTest extends MediaWikiTestCase {

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
			array( new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ) )
		);
	}

	private function newToolbarEditSectionGenerator() {
		$specialPageLinker = $this->getMock( 'Wikibase\View\SpecialPageLinker' );
		$specialPageLinker->expects( $this->any() )
			->method( 'getLink' )
			->will( $this->returnCallback( function( $specialPage, $params = array() ) {
				return 'Special:' . $specialPage . '/' . implode( '/', $params );
			} ) );

		$templateFactory = TemplateFactory::getDefaultInstance();

		return new ToolbarEditSectionGenerator( $specialPageLinker, $templateFactory );
	}

}
