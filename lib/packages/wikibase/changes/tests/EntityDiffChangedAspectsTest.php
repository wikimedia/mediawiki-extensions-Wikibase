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

	public static function invalidConstructionProvider() {
		$validParams = [
			'labelChanges' => [ 'a', '1' ],
			'descriptionChanges' => [ 'b', '2' ],
			'aliasChanges' => [ 'f', '1' ],
			'statementChangesExcludingQualOrRefOnlyChanges' => [ 'c', '3' ],
			'statementChangesQualOrRefOnly' => [ 'q', '4' ],
			'siteLinkChanges' => [ 'd' => [ null, null, true ] ],
			'otherChanges' => true,
		];

		$invalidLabelChanges = $validParams;
		$invalidLabelChanges['labelChanges'] = [ 'a', 1 ];

		$invalidDescriptionChanges = $validParams;
		$invalidDescriptionChanges['descriptionChanges'] = [ 'b', 2 ];

		$invalidAliasChanges = $validParams;
		$invalidAliasChanges['aliasChanges'] = [ 'f', 1 ];

		$invalidStatementChangesExcludingQualOrRefOnlyChanges = $validParams;
		$invalidStatementChangesExcludingQualOrRefOnlyChanges['statementChangesExcludingQualOrRefOnlyChanges'] = [ 'c', 3 ];

		$invalidStatementChangesQualOrRefOnly = $validParams;
		$invalidStatementChangesQualOrRefOnly['statementChangesQualOrRefOnly'] = [ 'q', 4 ];

		$invalidSiteLinkChangesKeys = $validParams;
		$invalidSiteLinkChangesKeys['siteLinkChanges'] = [ 1 => [ null, null, true ] ];

		$invalidSiteLinkChangesValues = $validParams;
		$invalidSiteLinkChangesValues['siteLinkChanges'] = [ 'd' => 12 ];

		$invalidOtherChanges = $validParams;
		$invalidOtherChanges['otherChanges'] = null;

		return [
			'Invalid labelChanges' => $invalidLabelChanges,
			'Invalid descriptionChanges' => $invalidDescriptionChanges,
			'Invalid aliasChanges' => $invalidAliasChanges,
			'Invalid statementChangesExcludingQualOrRefOnlyChanges' => $invalidStatementChangesExcludingQualOrRefOnlyChanges,
			'Invalid statementChangesQualOrRefOnly' => $invalidStatementChangesQualOrRefOnly,
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
		array $aliasChanges,
		array $statementChangesExcludingQualOrRefOnlyChanges,
		array $statementChangesQualOrRefOnly,
		array $siteLinkChanges,
		$otherChanges
	) {
		$this->expectException( InvalidArgumentException::class );

		$entity = new EntityDiffChangedAspects(
			$labelChanges,
			$descriptionChanges,
			$aliasChanges,
			$statementChangesExcludingQualOrRefOnlyChanges,
			$statementChangesQualOrRefOnly,
			$siteLinkChanges,
			$otherChanges );
	}

	private function getEntityDiffChangedAspects() {
		return new EntityDiffChangedAspects(
			[ 'a', '1' ],
			[ 'b', '2' ],
			[ 'f', '1' ],
			[ 'c', '3' ],
			[ 'q', '4' ],
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

	public function getStatementChangesExcludingQualOrRefOnly() {
		$this->assertSame(
			[ 'c', '3' ],
			$this->getEntityDiffChangedAspects()->getStatementChangesExcludingQualOrRefOnly()
		);
	}

	public function getStatementChangesQualOrRefOnly() {
		$this->assertSame(
			[ 'q', '4' ],
			$this->getEntityDiffChangedAspects()->getStatementChangesQualOrRefOnly()
		);
	}

	public function getStatementChanges() {
 //combines to get all changes whether qual/ref or not
		$this->assertSame(
			[ 'c', '3', 'q', '4' ],
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
			'aliasChanges' => [ 'f', '1' ],
			'statementChangesExcludingQualOrRefOnlyChanges' => [ 'c', '3' ],
			'statementChangesQualOrRefOnly' => [ 'q', '4' ],
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
			return 'C:45:"Wikibase\Lib\Changes\EntityDiffChangedAspects":211:' .
			'{{"arrayFormatVersion":1,"labelChanges":[],"descriptionChanges":[],' .
			'"aliasChanges":[],"statementChangesExcludingQualOrRefOnlyChanges":[],' .
				'"statementChangesQualOrRefOnly":[],"siteLinkChanges":[],"otherChanges":true}}';
	}

	/**
	 * Serialization test to ensure backwards compatibility
	 *
	 * @return string
	 */
	private function getOldKnownGoodSerialization() {
		// note: if you change the structure of EntityDiffChangedAspects,
		// you need to update the number for length of the serialized payload here but also update numbers
		// in 2 places in testUnserialize_wrongFormatVersion below
		return 'C:45:"Wikibase\Lib\Changes\EntityDiffChangedAspects":193:' .
			'{{"arrayFormatVersion":1,"labelChanges":[],"descriptionChanges":[],"statementChangesExcludingQualOrRefOnlyChanges":[],' .
			'"statementChangesQualOrRefOnly":[],"siteLinkChanges":[],"otherChanges":true}}';
	}

	public function testUnserialize() {
		$entityDiffChangedAspects = unserialize( $this->getKnownGoodSerialization() );

		$this->assertSame(
			( new EntityDiffChangedAspects( [], [], [], [], [], [], true ) )->toArray(),
			$entityDiffChangedAspects->toArray()
		);
	}

	public function testOldUnserialize() {
		$entityDiffChangedAspects = unserialize( $this->getOldKnownGoodSerialization() );

		$this->assertSame(
			( new EntityDiffChangedAspects( [], [], [], [], [], [], true ) )->toArray(),
			$entityDiffChangedAspects->toArray()
		);
	}

	public static function wrongArrayFormatVersionProvider() {
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
			'":211:',
			'":' . ( strlen( $arrayFormatVersion ) + 210 ) . ':',
			$entityDiffChangedAspectsSerialization
		);

		$this->expectException( Exception::class );
		unserialize( $entityDiffChangedAspectsSerialization );
	}

}
