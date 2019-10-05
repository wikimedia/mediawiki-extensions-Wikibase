<?php

namespace Wikibase\View\Tests;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\SpecialPageLinker;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * @covers \Wikibase\View\ToolbarEditSectionGenerator
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ToolbarEditSectionGeneratorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @dataProvider getAddStatementToGroupSectionProvider
	 */
	public function testGetAddStatementToGroupSection( $propertyId ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertEquals(
			'<wb:sectionedit><span class="wikibase-toolbar-container"></span></wb:sectionedit>',
			$generator->getAddStatementToGroupSection( $propertyId )
		);
	}

	public function getAddStatementToGroupSectionProvider() {
		return [
			[ new PropertyId( 'P1' ) ],
		];
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
		return [
			[
				'en',
				new PropertyId( 'P1' ),
				'Special:SetLabelDescriptionAliases/P1/en'
			]
		];
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
		return [
			[ new PropertyId( 'P1' ), 'Special:SetSiteLink/P1' ]
		];
	}

	/**
	 * @dataProvider getStatementEditSection
	 */
	public function testGetStatementEditSection( $statement ) {
		$generator = $this->newToolbarEditSectionGenerator();

		$this->assertEquals(
			'<wb:sectionedit><span class="wikibase-toolbar-container"></span></wb:sectionedit>',
			$generator->getStatementEditSection( $statement )
		);
	}

	public function getStatementEditSection() {
		return [
			[ new Statement( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ) ]
		];
	}

	private function newToolbarEditSectionGenerator() {
		$specialPageLinker = $this->createMock( SpecialPageLinker::class );
		$specialPageLinker->method( 'getLink' )
			->will( $this->returnCallback( function( $specialPage, $params = [] ) {
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
