<?php

namespace Wikibase\Repo;

use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use Wikimedia\Timestamp\TimestampFormat as TS;

/**
 * @license GPL-2.0-or-later
 */
class AddPageInfo {
	/**
	 * @var EntityTitleStoreLookup
	 */
	private $entityTitleStoreLookup;

	public function __construct( EntityTitleStoreLookup $entityTitleStoreLookup ) {
		$this->entityTitleStoreLookup = $entityTitleStoreLookup;
	}

	/**
	 * Adds MediaWiki page metadata to the record.
	 *
	 * @param array $record Record to which to add metadata.
	 * @param EntityRevision $entityRevision
	 *
	 * @return array Updated record.
	 */
	public function add( array $record, EntityRevision $entityRevision ): array {
		$title = $this->entityTitleStoreLookup->getTitleForId( $entityRevision->getEntity()->getId() );
		$record['pageid'] = $title->getArticleID();
		$record['ns'] = $title->getNamespace();
		$record['title'] = $title->getPrefixedText();
		$record['lastrevid'] = $entityRevision->getRevisionId();
		$record['modified'] = ConvertibleTimestamp::convert( TS::ISO_8601, $entityRevision->getTimestamp() );
		return $record;
	}
}
