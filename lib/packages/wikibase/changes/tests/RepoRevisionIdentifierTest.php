<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Tests\Changes;

use Exception;
use TypeError;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;

/**
 * @covers \Wikibase\Lib\Changes\RepoRevisionIdentifier
 *
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class RepoRevisionIdentifierTest extends \PHPUnit\Framework\TestCase {

	public function invalidConstructionProvider() {
		$validParams = [
			'entityIdSerialization' => 'Q12',
			'revisionTimestamp' => '20200302125300',
			'revisionId' => 123,
		];

		$invalidEntityIdSerialization = $validParams;
		$invalidEntityIdSerialization['entityIdSerialization'] = 12;

		$invalidRevisionTimestamp = $validParams;
		$invalidRevisionTimestamp['revisionTimestamp'] = 20200302125300;

		$invalidRevisionId = $validParams;
		$invalidRevisionId['revisionId'] = '123';

		return [
			'Invalid entityIdSerialization' => $invalidEntityIdSerialization,
			'Invalid revisionTimestamp' => $invalidRevisionTimestamp,
			'Invalid revisionId' => $invalidRevisionId,
		];
	}

	/**
	 * @dataProvider invalidConstructionProvider
	 */
	public function testInvalidConstruction(
		$entityIdSerialization,
		$revisionTimestamp,
		$revisionId
	) {
		$this->expectException( TypeError::class );

		new RepoRevisionIdentifier( $entityIdSerialization, $revisionTimestamp, $revisionId );
	}

	private function newRepoRevisionIdentifier() {
		return new RepoRevisionIdentifier(
			'Q12',
			'20200302125300',
			123,
			23
		);
	}

	public function testGetEntityIdSerialization() {
		$this->assertSame(
			'Q12',
			$this->newRepoRevisionIdentifier()->getEntityIdSerialization()
		);
	}

	public function testGetRevisionTimestamp() {
		$this->assertSame(
			'20200302125300',
			$this->newRepoRevisionIdentifier()->getRevisionTimestamp()
		);
	}

	public function testGetRevisionId() {
		$this->assertSame(
			123,
			$this->newRepoRevisionIdentifier()->getRevisionId()
		);
	}

	public function testToArray() {
		$expected = [
			'arrayFormatVersion' => RepoRevisionIdentifier::ARRAYFORMATVERSION,
			'entityIdSerialization' => 'Q12',
			'revisionTimestamp' => '20200302125300',
			'revisionId' => 123,
		];

		$this->assertSame( $expected, $this->newRepoRevisionIdentifier()->toArray() );
	}

	public function testSerialize() {
		$repoRevisionIdentifier = $this->newRepoRevisionIdentifier();

		$repoRevisionIdentifierClone = unserialize( serialize( $repoRevisionIdentifier ) );

		$this->assertSame( $repoRevisionIdentifier->toArray(), $repoRevisionIdentifierClone->toArray() );
	}

	/**
	 * @return string
	 */
	private function getKnownGoodSerialization() {
		return 'C:43:"Wikibase\Lib\Changes\RepoRevisionIdentifier":108:{{"arrayFormatVersion":1,' .
			'"entityIdSerialization":"Q12","revisionTimestamp":"20200302125300","revisionId":123}}';
	}

	public function testUnserialize() {
		$entityDiffChangedAspects = unserialize( $this->getKnownGoodSerialization() );

		$this->assertSame(
			( $this->newRepoRevisionIdentifier() )->toArray(),
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
			'":108:',
			'":' . ( strlen( strval( $arrayFormatVersion ) ) + 107 ) . ':',
			$entityDiffChangedAspectsSerialization
		);

		$this->expectException( Exception::class );
		unserialize( $entityDiffChangedAspectsSerialization );
	}

}
