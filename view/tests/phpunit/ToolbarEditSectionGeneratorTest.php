<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @covers Wikibase\View\ToolbarEditSectionGenerator
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ToolbarEditSectionGeneratorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getAddStatementToGroupSectionProvider
	 */
	public function testGetAddStatementToGroupSection( $propertyId ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertEquals(
			'<span class="wikibase-toolbar-container"></span>',
			$generator->getAddStatementToGroupSection( $propertyId )
		);
	}

	public function getAddStatementToGroupSectionProvider() {
		return array(
			array( new PropertyId( 'P1' ) ),
		);
	}

	/**
	 * @dataProvider getLabelDescriptionAliasesEditSectionProvider
	 */
	public function testGetLabelDescriptionAliasesEditSection(
		$languageCode,
		EntityId $entityId,
		$expected
	) {
		$generator = $this->newToolbarEditSectionGenerator();
		$html = $generator->getLabelDescriptionAliasesEditSection( $languageCode, $entityId );
		$this->assertContains( $expected, $html );
	}

	public function getLabelDescriptionAliasesEditSectionProvider() {
		return array(
			array(
				'en',
				new PropertyId( 'P1' ),
				'Special:SetLabelDescriptionAliases/P1/en'
			)
		);
	}

	/**
	 * @dataProvider getSiteLinksEditSectionProvider
	 */
	public function testGetSiteLinksEditSection( EntityId $entityId, $expected ) {
		$generator = $this->newToolbarEditSectionGenerator();
		$html = $generator->getSiteLinksEditSection( $entityId );
		$this->assertContains( $expected, $html );
	}

	public function getSiteLinksEditSectionProvider() {
		return array(
			array( new PropertyId( 'P1' ), 'Special:SetSiteLink/P1' )
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
		$specialPageLinker = $this->getMock( SpecialPageLinker::class );
		$specialPageLinker->expects( $this->any() )
			->method( 'getLink' )
			->will( $this->returnCallback( function( $specialPage, $params = array() ) {
				return 'Special:' . $specialPage . '/' . implode( '/', $params );
			} ) );

		$templateFactory = TemplateFactory::getDefaultInstance();

		return new ToolbarEditSectionGenerator(
			$specialPageLinker,
			$templateFactory,
			$this->getMock( LocalizedTextProvider::class )
		);
	}

}
