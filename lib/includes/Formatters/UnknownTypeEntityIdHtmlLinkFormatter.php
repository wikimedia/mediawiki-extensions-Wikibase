<?php

namespace Wikibase\Lib\Formatters;

use Html;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * An EntityIdHtmlLinkFormatter for entity types without a configured entity-id-html-link-formatter-callback
 *
 * @license GPL-2.0-or-later
 */
class UnknownTypeEntityIdHtmlLinkFormatter implements EntityIdFormatter {

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $nonExistingFormatter;

	public function __construct(
		EntityTitleLookup $entityTitleLookup,
		EntityIdFormatter $nonExistingFormatter
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->nonExistingFormatter = $nonExistingFormatter;
	}

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string HTML
	 */
	public function formatEntityId( EntityId $entityId ) {
		$title = $this->entityTitleLookup->getTitleForId( $entityId );

		if ( $title === null ) {
			return $this->nonExistingFormatter->formatEntityId( $entityId );
		}

		$serializedId = $entityId->getSerialization();

		return Html::element( 'a', $this->getAttributes( $title ), $serializedId );
	}

	/**
	 * @param Title $title
	 *
	 * @return string[]
	 */
	private function getAttributes( Title $title ) {
		$attributes = [
			'title' => $title->getPrefixedText(),
			'href' => $title->isLocal() ? $title->getLocalURL() : $title->getFullURL(),
		];

		if ( $title->isLocal() && $title->isRedirect() ) {
			$attributes['class'] = 'mw-redirect';
		}

		return $attributes;
	}

}
