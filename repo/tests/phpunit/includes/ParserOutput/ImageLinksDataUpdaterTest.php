<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use File;
use ParserOutput;
use RepoGroup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;

/**
 * @covers \Wikibase\Repo\ParserOutput\ImageLinksDataUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ImageLinksDataUpdaterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return ImageLinksDataUpdater
	 */
	private function newInstance() {
		$matcher = $this->createMock( PropertyDataTypeMatcher::class );
		$matcher->method( 'isMatchingDataType' )
			->willReturnCallback( function( PropertyId $id, $type ) {
				return $id->getSerialization() === 'P1';
			} );
		$repoGroup = $this->createMock( RepoGroup::class );
		$repoGroup->method( 'findFile' )
			->willReturnCallback( function( string $fileName ) {
				if ( $fileName === 'Exists.png' ) {
					$file = $this->createMock( File::class );
					$file->expects( $this->once() )
						->method( 'getSha1' )
						->willReturn( 'ccde261bb2a1d49e1c9bfd06847f9a8c2b640fe9' );
					$file->expects( $this->once() )
						->method( 'getTimestamp' )
						->willReturn( '20121026200049' );
					return $file;
				}
				return false;
			} );

		return new ImageLinksDataUpdater( $matcher, $repoGroup );
	}

	/**
	 * @param StatementList $statements
	 * @param string $string
	 * @param int $propertyId
	 */
	private function addStatement( StatementList $statements, $string, $propertyId = 1 ) {
		$statements->addNewStatement(
			new PropertyValueSnak( $propertyId, new StringValue( $string ) )
		);
	}

	/**
	 * @dataProvider imageLinksProvider
	 */
	public function testUpdateParserOutput(
		StatementList $statements,
		array $expectedFiles,
		array $expectedFileSearchOptions
	) {
		$parserOutput = new ParserOutput();
		$instance = $this->newInstance();

		foreach ( $statements as $statement ) {
			$instance->processStatement( $statement );
		}

		$instance->updateParserOutput( $parserOutput );
		$this->assertSame( $expectedFiles, array_keys( $parserOutput->getImages() ) );
		$this->assertSame( $expectedFileSearchOptions, $parserOutput->getFileSearchOptions() );
	}

	public function imageLinksProvider() {
		$set1 = new StatementList();
		$this->addStatement( $set1, '1.jpg' );
		$this->addStatement( $set1, '' );
		$this->addStatement( $set1, 'no image property', 2 );

		$set2 = new StatementList();
		$this->addStatement( $set2, '2a.jpg' );
		$this->addStatement( $set2, '2b.jpg' );

		$set3 = new StatementList();
		$this->addStatement( $set3, '2a.jpg' );
		$this->addStatement( $set3, 'Exists.png' );

		return [
			[ new StatementList(), [], [] ],
			[
				$set1,
				[ '1.jpg' ],
				[ '1.jpg' => [ 'time' => false, 'sha1' => false ] ],
			],
			[
				$set2,
				[ '2a.jpg', '2b.jpg' ],
				[
					'2a.jpg' => [ 'time' => false, 'sha1' => false ],
					'2b.jpg' => [ 'time' => false, 'sha1' => false ],
				],
			],
			[
				$set3,
				[ '2a.jpg', 'Exists.png' ],
				[
					'2a.jpg' => [ 'time' => false, 'sha1' => false ],
					'Exists.png' => [
						'time' => '20121026200049',
						'sha1' => 'ccde261bb2a1d49e1c9bfd06847f9a8c2b640fe9',
					],
				],
			],
		];
	}

}
