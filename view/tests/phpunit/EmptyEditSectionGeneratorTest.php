<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\View\EmptyEditSectionGenerator;

/**
 * @covers Wikibase\Repo\View\EmptyEditSectionGeneratorTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class EmptyEditSectionGeneratorTest extends MediaWikiTestCase {

	/**
	 * @dataProvider getAddStatementToGroupSectionProvider
	 */
	public function testGetAddStatementToGroupSection( $propertyId, $entityId ) {
		$generator = $this->newEmptyEditSectionGenerator();

		$this->assertEquals(
			'',
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
	public function testGetLabelDescriptionAliasesEditSection( $languageCode, $entityId ) {
		$generator = $this->newEmptyEditSectionGenerator();

		$this->assertEquals(
			'',
			$generator->getLabelDescriptionAliasesEditSection( $languageCode, $entityId )
		);
	}

	public function getLabelDescriptionAliasesEditSectionProvider() {
		return array(
			array( 'en', new PropertyId( 'P1' ) )
		);
	}

	/**
	 * @dataProvider getSiteLinksEditSectionProvider
	 */
	public function testGetSiteLinksEditSection( $entityId ) {
		$generator = $this->newEmptyEditSectionGenerator();

		$this->assertEquals(
			'',
			$generator->getSiteLinksEditSection( $entityId )
		);

	}

	public function getSiteLinksEditSectionProvider() {
		return array(
			array( new PropertyId( 'P1' ) )
		);
	}

	/**
	 * @dataProvider getStatementEditSection
	 */
	public function testGetStatementEditSection( $statement ) {
		$generator = $this->newEmptyEditSectionGenerator();

		$this->assertEquals(
			'',
			$generator->getStatementEditSection( $statement )
		);

	}

	public function getStatementEditSection() {
		return array(
			array( new Statement( new Claim( new PropertyNoValueSnak( new PropertyId( 'P1' ) ) ) ) )
		);
	}

	private function newEmptyEditSectionGenerator() {
		return new EmptyEditSectionGenerator();
	}

}
