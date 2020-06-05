<?php

namespace Wikibase\Lib\Interactors;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class TermSearchResult {

	public const CONCEPTURI_META_DATA_KEY = 'concepturi';

	/**
	 * @var Term
	 */
	private $matchedTerm;

	/**
	 * @var string
	 */
	private $matchedTermType;

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var null|Term
	 */
	private $displayLabel;

	/**
	 * @var null|Term
	 */
	private $displayDescription;

	/**
	 * @var array
	 */
	private $metaData;

	/**
	 * @param Term $matchedTerm
	 * @param string $matchedTermType
	 * @param EntityId $entityId
	 * @param Term|null $displayLabel
	 * @param Term|null $displayDescription
	 */
	public function __construct(
		Term $matchedTerm,
		string $matchedTermType,
		EntityId $entityId,
		Term $displayLabel = null,
		Term $displayDescription = null,
		array $metaData = []
	) {
		$this->matchedTerm = $matchedTerm;
		$this->matchedTermType = $matchedTermType;
		$this->entityId = $entityId;
		$this->displayLabel = $displayLabel;
		$this->displayDescription = $displayDescription;
		$this->metaData = $metaData;
	}

	/**
	 * @return Term
	 */
	public function getMatchedTerm() {
		return $this->matchedTerm;
	}

	/**
	 * @return string
	 */
	public function getMatchedTermType() {
		return $this->matchedTermType;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return Term|null
	 */
	public function getDisplayLabel() {
		return $this->displayLabel;
	}

	/**
	 * @return Term|null
	 */
	public function getDisplayDescription() {
		return $this->displayDescription;
	}

	/**
	 * @return array [ key => value ] map of meta data about the returned search result or its associated entity
	 */
	public function getMetaData(): array {
		return $this->metaData;
	}

}
