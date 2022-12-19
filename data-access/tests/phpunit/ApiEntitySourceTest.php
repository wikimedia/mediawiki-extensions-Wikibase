<?php

namespace Wikibase\DataAccess\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @covers \Wikibase\DataAccess\ApiEntitySource
 */
class ApiEntitySourceTest extends TestCase {

	/**
	 * @dataProvider provideInvalidConstructorArguments
	 */
	public function testGivenInvalidArg_constructorThrowsException(
			  $slotName,
		array $entityType,
			  $conceptBaseUri,
			  $validRdfNodeNamespacePrefix,
			  $validRdfPredicateNamespacePrefix,
			  $interwikiPrefix
	) {
		$this->expectException( InvalidArgumentException::class );
		new ApiEntitySource(
			$slotName,
			$entityType,
			$conceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$interwikiPrefix
		);
	}

	public function provideInvalidConstructorArguments() {
		$validSourceName = 'testsource';
		$validEntityData = [ 'item', 'property' ];
		$validRdfNodeNamespacePrefix = 'wd';
		$validRdfPredicateNamespacePrefix = '';
		$validConceptBaseUri = 'concept:';
		$validInterwikiPrefix = 'test';

		yield 'Source name not a string' => [
			1000,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'entity type not a string' => [
			$validSourceName,
			[ 1 ],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'no entity type ' => [
			$validSourceName,
			[],
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'Concept base URI not a string' => [
			$validSourceName,
			$validEntityData,
			100,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'RDF node namespace prefix not a string' => [
			$validSourceName,
			$validEntityData,
			$validConceptBaseUri,
			100,
			$validRdfPredicateNamespacePrefix,
			$validInterwikiPrefix,
		];
		yield 'RDF predicate namespace prefix not a string' => [
			$validSourceName,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			100,
			$validInterwikiPrefix,
		];
		yield 'Interwiki prefix not a string' => [
			$validSourceName,
			$validEntityData,
			$validConceptBaseUri,
			$validRdfNodeNamespacePrefix,
			$validRdfPredicateNamespacePrefix,
			100,
		];
	}

	public function testGetSourceName() {
		$entitySource = new ApiEntitySource( 'name', [ 'property', 'item' ], '', '', '', '' );
		$this->assertEquals( 'name', $entitySource->getSourceName() );
	}

	public function testGetEntityTypes() {
		$entitySource = new ApiEntitySource( '', [ 'property', 'item' ], '', '', '', '' );
		$this->assertEquals( [ 'property', 'item' ], $entitySource->getEntityTypes() );
	}

	public function testGetType() {
		$entitySource = new ApiEntitySource( '', [ 'property' ], '', '', '', '' );
		$this->assertEquals( ApiEntitySource::TYPE, $entitySource->getType() );
	}

	public function testGetConceptBaseUri() {
		$entitySource = new ApiEntitySource( '', [ 'property' ], 'someUri', '', '', '' );
		$this->assertEquals( 'someUri', $entitySource->getConceptBaseUri() );
	}

	public function testInterwikiPrefix() {
		$entitySource = new ApiEntitySource( '', [ 'property' ], '', '', '', 'foo' );
		$this->assertEquals( 'foo', $entitySource->getInterwikiPrefix() );
	}

	public function testGetRdfNodeNamespacePrefix() {
		$entitySource = new ApiEntitySource( '', [ 'property' ], '', 'foo', '', '' );
		$this->assertEquals( 'foo', $entitySource->getRdfNodeNamespacePrefix() );
	}

	public function testGetRdfPredicateNamespacePrefix() {
		$entitySource = new ApiEntitySource( '', [ 'property' ], '', '', 'foo', '' );
		$this->assertEquals( 'foo', $entitySource->getRdfPredicateNamespacePrefix() );
	}
}
