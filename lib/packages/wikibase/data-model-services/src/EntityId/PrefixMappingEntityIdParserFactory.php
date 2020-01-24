<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @since 3.7
 *
 * @license GPL-2.0-or-later
 */
class PrefixMappingEntityIdParserFactory {

	/**
	 * @var string[]
	 */
	private $idPrefixMapping;

	/**
	 * @var EntityIdParser
	 */
	private $parser;

	/**
	 * @var PrefixMappingEntityIdParser[]
	 */
	private $parsers = [];

	/**
	 * @since 3.7
	 *
	 * @param EntityIdParser $parser
	 * @param array[] $idPrefixMapping An associative array mapping repository names (strings) to id serialization
	 *        prefix mappings specific to the particular repository (@see PrefixMappingEntityIdParser).
	 *        If an empty-string key is provided in the mapping for some repository, its value must be the same
	 *        as the repository name.
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( EntityIdParser $parser, array $idPrefixMapping ) {
		Assert::parameterElementType( 'string', array_keys( $idPrefixMapping ), 'array_keys( $idPrefixMapping)' );
		foreach ( $idPrefixMapping as $repositoryName => $mapping ) {
			Assert::parameter(
				strpos( $repositoryName, ':' ) === false,
				'keys in $idPrefixMapping',
				'must not contain a colon'
			);
			Assert::parameterType( 'array', $mapping, '$idPrefixMapping[' . $repositoryName . ']' );
			Assert::parameterElementType( 'string', $mapping, '$idPrefixMapping[' . $repositoryName . ']' );
			Assert::parameterElementType(
				'string',
				array_keys( $mapping ),
				'array_keys( $idPrefixMapping[' . $repositoryName . '] )'
			);
			Assert::parameter(
				!array_key_exists( '', $mapping ) || $mapping[''] === $repositoryName,
				'$idPrefixMapping[' . $repositoryName . '] )',
				'must either not contain empty-string prefix mapping or it must be equal to repository name'
			);
		}

		$this->idPrefixMapping = $idPrefixMapping;
		$this->parser = $parser;
	}

	/**
	 * Create a PrefixMappingEntityIdParser for the particular repository using id prefix mappings
	 * defined in the constructor.
	 *
	 * @since 3.7
	 *
	 * @param string $repository
	 *
	 * @return PrefixMappingEntityIdParser
	 * @throws ParameterAssertionException
	 */
	public function getIdParser( $repository ) {
		if ( !isset( $this->parsers[$repository] ) ) {
			$this->parsers[$repository] = $this->newIdParserForRepository( $repository );
		}

		return $this->parsers[$repository];
	}

	/**
	 * @param string $repository
	 *
	 * @return PrefixMappingEntityIdParser
	 * @throws ParameterAssertionException
	 */
	private function newIdParserForRepository( $repository ) {
		Assert::parameterType( 'string', $repository, '$repository' );
		Assert::parameter( strpos( $repository, ':' ) === false, '$repository', 'must not contain a colon' );
		$mapping = [ '' => $repository ];
		if ( isset( $this->idPrefixMapping[$repository] ) ) {
			$mapping = array_merge( $mapping, $this->idPrefixMapping[$repository] );
		}
		return new PrefixMappingEntityIdParser( $mapping, $this->parser );
	}

}
