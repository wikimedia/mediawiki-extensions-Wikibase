<?php

namespace Wikibase;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermStore\ItemTermStore;

/**
 * An adapter to turn a {@link TermIndex} into an {@link ItemTermStore}.
 *
 * @license GPL-2.0-or-later
 */
class TermIndexItemTermStore implements ItemTermStore {

	/** @var TermIndex */
	private $termIndex;

	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
		$item = new Item( $itemId, $terms );
		$this->termIndex->saveTermsOfEntity( $item );
	}

	public function deleteTerms( ItemId $itemId ) {
		$this->termIndex->deleteTermsOfEntity( $itemId );
	}

	public function getTerms( ItemId $itemId ): Fingerprint {
		$termIndexEntries = $this->termIndex->getTermsOfEntity(
			$itemId,
			[
				TermIndexEntry::TYPE_LABEL,
				TermIndexEntry::TYPE_DESCRIPTION,
				TermIndexEntry::TYPE_ALIAS,
			]
		);

		$labels = new TermList();
		$descriptions = new TermList();
		$aliases = new AliasGroupList();
		foreach ( $termIndexEntries as $termIndexEntry ) {
			switch ( $termIndexEntry->getTermType() ) {
				case TermIndexEntry::TYPE_LABEL:
					$labels->setTerm( $termIndexEntry->getTerm() );
					break;
				case TermIndexEntry::TYPE_DESCRIPTION:
					$descriptions->setTerm( $termIndexEntry->getTerm() );
					break;
				case TermIndexEntry::TYPE_ALIAS:
					$language = $termIndexEntry->getLanguage();
					$aliases->setAliasesForLanguage(
						$language,
						array_merge(
							 $aliases->hasGroupForLanguage( $language ) ?
								 $aliases->getByLanguage( $language )->getAliases() :
								 [],
							[ $termIndexEntry->getText() ]
						)
					);
					break;
				default:
					throw new LogicException(
						'TermIndex returned unknown term type: ' . $termIndexEntry->getTermType()
					);
			}
		}

		return new Fingerprint( $labels, $descriptions, $aliases );
	}

}
