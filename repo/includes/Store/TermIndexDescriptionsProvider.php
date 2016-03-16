<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;

/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermIndexDescriptionsProvider implements DescriptionsProvider {

	/**
	 * @var TermIndex
	 */
	private $termIndex = null;

	/**
	 * @var EntityId
	 */
	private $entityId = null;

	/**
	 * @var TermList
	 */
	private $descriptions = null;

	public function __construct( TermIndex $termIndex, EntityId $entityId ) {
		$this->termIndex = $termIndex;
		$this->entityId = $entityId;
	}

	/**
	 * @return TermList
	 */
	public function getDescriptions() {
		if ( !$this->descriptions ) {
			$this->descriptions = new TermList(
				array_map(
					function( TermIndexEntry $termIndexEntry ) {
						return new Term( $termIndexEntry->getLanguage(), $termIndexEntry->getText() );
					},
					$this->termIndex->getTermsOfEntity( $this->entityId, [ 'description' ] ) // FIXME: Filter languages?
				)
			);
		}
		return $this->descriptions;
	}

}
