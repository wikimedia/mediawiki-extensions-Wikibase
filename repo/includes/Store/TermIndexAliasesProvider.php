<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermIndexAliasesProvider implements AliasesProvider {

	/**
	 * @var TermIndex
	 */
	private $termIndex = null;

	/**
	 * @var EntityId
	 */
	private $entityId = null;

	/**
	 * @var AliasGroupList
	 */
	private $aliases = null;

	public function __construct( TermIndex $termIndex, EntityId $entityId ) {
		$this->termIndex = $termIndex;
		$this->entityId = $entityId;
	}

	/**
	 * @return AliasGroupList
	 */
	public function getAliasGroups() {
		if ( !$this->aliases ) {
			$groupedAliases = [];
			$aliasesEntries = $this->termIndex->getTermsOfEntity( $this->entityId, [ 'alias' ] ); // FIXME: Filter languages?
			foreach ( $aliasesEntries as $aliasEntry ) {
				$lang = $aliasEntry->getLanguage();
				$groupedAliases[ $lang ] = isset( $groupedAliases[ $lang ] ) ? $groupedAliases[ $lang ] : [];
				$groupedAliases[ $lang ][] = $aliasEntry->getText();
			}
			$this->aliases = new AliasGroupList( array_map(
				function( $lang, $aliases ) {
					return new AliasGroup( $lang, $aliases );
				},
				array_keys( $groupedAliases ),
				$groupedAliases
			) );
		}
		return $this->aliases;
	}

}
