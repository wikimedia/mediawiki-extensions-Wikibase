<?php

namespace Wikibase\Lib\Tests\Changes;

use Exception;
use InvalidArgumentException;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;

/**
 * @covers \Wikibase\Lib\Changes\EntityDiffChangedAspects
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityDiffChangedAspectsTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructionProvider() {
		$validParams = [
			'labelChanges' => [ 'a', '1' ],
			'descriptionChanges' => [ 'b', '2' ],
			'statementChanges' => [ 'c', '3' ],
			'siteLinkChanges' => [ 'd' => [ null, null, true ] ],
			'otherChanges' => true,
		];

		$invalidLabelChanges = $validParams;
		$invalidLabelChanges['labelChanges'] = [ 'a', 1 ];

		$invalidDescriptionChanges = $validParams;
		$invalidDescriptionChanges['descriptionChanges'] = [ 'b', 2 ];

		$invalidStatementChanges = $validParams;
		$invalidStatementChanges['statementChanges'] = [ 'c', 3 ];

		$invalidSiteLinkChangesKeys = $validParams;
		$invalidSiteLinkChangesKeys['siteLinkChanges'] = [ 1 => [ null, null, true ] ];

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
		$this->expectException( InvalidArgumentException::class );

		new EntityDiffChangedAspects( $labelChanges, $descriptionChanges, $statementChanges, $siteLinkChanges, $otherChanges );
	}

	private function getEntityDiffChangedAspects() {
		return new EntityDiffChangedAspects(
			[ 'a', '1' ],
			[ 'b', '2' ],
			[ 'c', '3' ],
			[ 'd' => [ null, null, true ] ],
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
			[ 'd' => [ null, null, true ] ],
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
			'siteLinkChanges' => [ 'd' => [ null, null, true ] ],
			'otherChanges' => true,
		];

		$this->assertSame( $expected, $this->getEntityDiffChangedAspects()->toArray() );
	}

	public function testSerialize() {
		$entityDiffChangedAspects = $this->getEntityDiffChangedAspects();

		$entityDiffChangedAspectsClone = unserialize( serialize( $entityDiffChangedAspects ) );

		$this->assertSame( $entityDiffChangedAspects->toArray(), $entityDiffChangedAspectsClone->toArray() );
	}

	/**
	 * @return string
	 */
	private function getKnownGoodSerialization() {
		return 'C:45:"Wikibase\Lib\Changes\EntityDiffChangedAspects":129:' .
			'{{"arrayFormatVersion":1,"labelChanges":[],"descriptionChanges":[],' .
			'"statementChanges":[],"siteLinkChanges":[],"otherChanges":true}}';
	}

	public function testUnserialize() {
		$entityDiffChangedAspects = unserialize( $this->getKnownGoodSerialization() );

		$this->assertSame(
			( new EntityDiffChangedAspects( [], [], [], [], true ) )->toArray(),
			$entityDiffChangedAspects->toArray()
		);
	}

	public function wrongArrayFormatVersionProvider() {
		// NOTE: If you remove versions here, make sure all good ones can be unserialized!
		return [
			[ -1 ],
			[ 0 ],
			[ 2 ],
			[ '"Milch"' ],
		];
	}

	/**
	 * @dataProvider wrongArrayFormatVersionProvider
	 */
	public function testUnserialize_wrongFormatVersion( $arrayFormatVersion ) {
		$entityDiffChangedAspectsSerialization = $this->getKnownGoodSerialization();

		// Change the array version in the serialization
		$entityDiffChangedAspectsSerialization = str_replace(
			'"arrayFormatVersion":1',
			'"arrayFormatVersion":' . $arrayFormatVersion,
			$entityDiffChangedAspectsSerialization
		);
		// Change the length of the serialization "body" (the content from Serializable::serialize)
		$entityDiffChangedAspectsSerialization = str_replace(
			'":129:',
			'":' . ( strlen( $arrayFormatVersion ) + 128 ) . ':',
			$entityDiffChangedAspectsSerialization
		);

		$this->expectException( Exception::class );
		unserialize( $entityDiffChangedAspectsSerialization );
	}

}
