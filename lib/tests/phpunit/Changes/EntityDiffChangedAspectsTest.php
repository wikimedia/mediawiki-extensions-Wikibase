<?php

namespace Wikibase\Lib\Tests\Changes;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;

/**
 * @covers Wikibase\Lib\Changes\EntityDiffChangedAspects
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityDiffChangedAspectsTest extends PHPUnit_Framework_TestCase {

	public function invalidConstructionProvider() {
		$validParams = [
			'labelChanges' => [ 'a', '1' ],
			'descriptionChanges' => [ 'b', '2' ],
			'statementChanges' => [ 'c', '3' ],
			'siteLinkChanges' => [ 'd' => true ],
			'otherChanges' => true,
		];

		$invalidLabelChanges = $validParams;
		$invalidLabelChanges['labelChanges'] = [ 'a', 1 ];

		$invalidDescriptionChanges = $validParams;
		$invalidDescriptionChanges['descriptionChanges'] = [ 'b', 2 ];

		$invalidStatementChanges = $validParams;
		$invalidStatementChanges['statementChanges'] = [ 'c', 3 ];

		$invalidSiteLinkChangesKeys = $validParams;
		$invalidSiteLinkChangesKeys['siteLinkChanges'] = [ 1 => true ];

		$invalidSiteLinkChangesValues = $validParams;
		$invalidSiteLinkChangesValues['siteLinkChanges'] = [ 'd' => 12 ];

		$invalidOtherChanges = $validParams;
		$invalidOtherChanges['otherChanges'] = null;

		return [
			'Invalid labelChanges' => $invalidLabelChanges,
			'Invalid descriptionChanges' => $invalidDescriptionChanges,
			'Invalid statementChanges' => $invalidStatementChanges,
			'Invalid siteLinkChanges keys' => $invalidSiteLinkChangesKeys,
			'Invalid siteLinkChanges values' => $invalidSiteLinkChangesValues,
			'Invalid otherChanges' => $invalidOtherChanges,
		];
	}

	/**
	 * @dataProvider invalidConstructionProvider
	 */
	public function testInvalidConstruction(
		array $labelChanges,
		array $descriptionChanges,
		array $statementChanges,
		array $siteLinkChanges,
		$otherChanges
	) {
		$this->setExpectedException( InvalidArgumentException::class );

		new EntityDiffChangedAspects( $labelChanges, $descriptionChanges, $statementChanges, $siteLinkChanges, $otherChanges );
	}

	private function getEntityDiffChangedAspects() {
		return new EntityDiffChangedAspects(
			[ 'a', '1' ],
			[ 'b', '2' ],
			[ 'c', '3' ],
			[ 'd' => true ],
			true
		);
	}

	public function testGetLabelChanges() {
		$this->assertSame(
			[ 'a', '1' ],
			$this->getEntityDiffChangedAspects()->getLabelChanges()
		);
	}

	public function testGetDescriptionChanges() {
		$this->assertSame(
			[ 'b', '2' ],
			$this->getEntityDiffChangedAspects()->getDescriptionChanges()
		);
	}

	public function testGetStatementChanges() {
		$this->assertSame(
			[ 'c', '3' ],
			$this->getEntityDiffChangedAspects()->getStatementChanges()
		);
	}

	public function testGetSiteLinkChanges() {
		$this->assertSame(
			[ 'd' => true ],
			$this->getEntityDiffChangedAspects()->getSiteLinkChanges()
		);
	}

	public function testHasOtherChanges() {
		$this->assertSame(
			true,
			$this->getEntityDiffChangedAspects()->hasOtherChanges()
		);
	}

	public function testToArray() {
		$expected = [
			'arrayFormatVersion' => EntityDiffChangedAspects::ARRAYFORMATVERSION,
			'labelChanges' => [ 'a', '1' ],
			'descriptionChanges' => [ 'b', '2' ],
			'statementChanges' => [ 'c', '3' ],
			'siteLinkChanges' => [ 'd' => true ],
			'otherChanges' => true,
		];

		$this->assertSame( $expected, $this->getEntityDiffChangedAspects()->toArray() );
	}

	public function testSerialize() {
		$entityDiffChangedAspects = $this->getEntityDiffChangedAspects();

		$entityDiffChangedAspectsClone = unserialize( serialize( $entityDiffChangedAspects ) );

		$this->assertSame( $entityDiffChangedAspects->toArray(), $entityDiffChangedAspectsClone->toArray() );
	}

}
