<?php

namespace Wikibase\Lib\Store\Sql\MediaWikiTermStore;

use Database;
use MediaWiki\Storage\NameTableStore;
use Psr\Log\LoggerInterface;
use WANObjectCache;
use Wikimedia\Rdbms\ILoadBalancer;

class MediaWikiDatabaseNormalizedTermStore implements NormalizedTermStoreSchemaAccess {

	const PREFIX_TABLE = 'wbt_';
	const TABLE_TYPE = 'type';
	const TABLE_TEXT = 'text';
	const TABLE_TEXT_IN_LANG = 'text_in_lang';
	const TABLE_TERM_IN_LANG = 'term_in_lang';

	/**
	 * @var Database $dbMaster
	 */
	private $dbMaster;

	/**
	 * @var Database $dbReplica
	 */
	private $dbReplica;

	/**
	 * @var NameTableStore
	 */
	private $typeNameStore;

	public function __construct(
		ILoadBalancer $loadBalancer,
		WANObjectCache $cache,
		LoggerInterface $logger
	) {
		$this->dbMaster = $loadBalancer->getConnection( DB_MASTER );
		$this->dbReplica = $loadBalancer->getConnection( DB_REPLICA );
		$this->typeNameStore = new NameTableStore(
			$loadBalancer,
			$cache,
			$logger,
			self::PREFIX_TABLE . self::TABLE_TYPE,
			'wby_id',
			'wby_name'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function acquireTermIds( array $termsArray ) {
		$termsArray = $this->mapToTextIds( $termsArray );
		$termsArray = $this->mapToTextInLangIds( $termsArray );
		$termsArray = $this->mapToTypeIds( $termsArray );
		return $this->mapToTermInLangIds( $termsArray );
	}

	/**
	 * replace root keys containing type names in termsArray
	 * with their respective ids in wbt_type table
	 *
	 * @param array $termsArray terms per type per language:
	 * 	[
	 *		'type1' => [ ... ],
	 *		'type2' => [ ... ],
	 *		...
	 *  ]
	 *
	 * @return array
	 * 	[
	 *		<typeId1> => [ ... ],
	 *		<typeId2> => [ ... ],
	 *		...
	 *  ]
	 */
	private function mapToTypeIds( array $termsArray ) {
		$typeIds = $this->acquireTypeIds( array_keys( $termsArray ) );

		$termsArrayByTypeId = [];
		foreach ( $typeIds as $type => $typeId ) {
			$termsArrayByTypeId[ $typeId ] = $termsArray[ $type ];
		}
		return $termsArrayByTypeId;
	}

	private function acquireTypeIds( $types ) {
		$typeIds = [];
		foreach( $types as $type ) {
			$typeIds[ $type ] = $this->typeNameStore->acquireId( $type );
		}
		return $typeIds;
	}

	/**
	 * replace text at termsArray leaves with their ids in wbt_text table
	 * and return resulting array
	 *
	 * @param array $termsArray terms per type per language:
	 * 	[
	 *		'type' => [
	 *			[ 'language' => 'term' | [ 'term1', 'term2', ... ] ], ...
	 *		], ...
	 *  ]
	 *
	 * @return array
	 * 	[
	 *		'type' => [
	 *			[ 'language' => [ <textId1>, <textId2>, ... ] ], ...
	 *		], ...
	 *  ]
	 */
	private function mapToTextIds( array $termsArray ) {
	}

	private function acquireTextIds( array $termsArray ) {
	}

	/**
	 * replace ( lang => [ textId, ... ] ) entries with their respective ids
	 * in wbt_text_in_lang table and return resulting array
	 *
	 * @param array $termsArray text ids per type per langauge
	 * 	[
	 *		'type' => [
	 *			[ 'language' => [ <textId1>, <textId2>, ... ] ], ...
	 *		], ...
	 *  ]
	 *
	 * @return array
	 * 	[
	 *		'type' => [ <textInLangId1>, <textInLangId2>, ... ],
	 *		...
	 *  ]
	 */
	private function mapToTextInLangIds( array $termsArray ) {
	}

	private function acquireTextInLangIds( array $termsArray ) {
	}

	/**
	 * replace root ( type => [ textInLangId, ... ] ) entries with their respective ids
	 * in wbt_term_in_lang table and return resulting array
	 *
	 * @param array $termsArray text in lang ids per type
	 * 	[
	 *		'type' => [ <textInLangId1>, <textInLangId2>, ... ],
	 *		...
	 *  ]
	 *
	 * @return array
	 * 	[
	 *		<termInLang1>,
	 *		<termInLang2>,
	 *		...
	 *  ]
	 */
	private function mapToTermInLangIds( array $termsArray ) {
	}

	private function acquireTermInLangIds( array $termsArray ) {
	}

}
