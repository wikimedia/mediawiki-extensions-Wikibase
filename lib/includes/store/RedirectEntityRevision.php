<?php

namespace Wikibase;

use Wikibase\DataModel\Entity\EntityId;
/**
 * Redirected entity revision - also stores the original ID
 */
class RedirectEntityRevision extends EntityRevision {
	/**
	 * Source ID for the redirect
	 * @var EntityId
	 */
	protected $source;

	/**
	 * Get the source ID
	 * @return \Wikibase\DataModel\Entity\EntityId
	 */
	public function getSource()
	{
		return $this->source;
	}

	public function __construct( EntityRevision $rev, EntityId $source ) {
		$this->entity = $rev->entity;
		$this->revisionId = $rev->revisionId;
		$this->mwTimestamp = $rev->mwTimestamp;
		$this->source = $source;
	}
}
