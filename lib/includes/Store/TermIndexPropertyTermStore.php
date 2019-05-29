<?php

namespace Wikibase;

use LogicException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\DataType;
use Wikibase\TermStore\PropertyTermStore;

/**
 * An adapter to turn a {@link TermIndex} into a {@link PropertyTermStore}.
 *
 * @license GPL-2.0-or-later
 */
class TermIndexPropertyTermStore implements PropertyTermStore {

	/** @var TermIndex */
	private $termIndex;

	public function __construct( TermIndex $termIndex ) {
		$this->termIndex = $termIndex;
	}

	public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
		$property = new Property( $propertyId, $terms, 'fake data type' );
		$this->termIndex->saveTermsOfEntity( $property );
	}

	public function deleteTerms( PropertyId $propertyId ) {
		$this->termIndex->deleteTermsOfEntity( $propertyId );
	}

	public function getTerms( PropertyId $propertyId ): Fingerprint {
		$termIndexEntries = $this->termIndex->getTermsOfEntity(
			$propertyId,
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
