<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Serializers;

use Wikibase\DataModel\Serializers\SiteLinkListSerializer;
use Wikibase\DataModel\Serializers\StatementListSerializer;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;

/**
 * @license GPL-2.0-or-later
 */
class ItemDataSerializer {

	private $statementsSerializer;
	private $siteLinksSerializer;

	public function __construct( StatementListSerializer $statementsSerializer, SiteLinkListSerializer $siteLinksSerializer ) {
		$this->statementsSerializer = $statementsSerializer;
		$this->siteLinksSerializer = $siteLinksSerializer;
	}

	public function serialize( ItemData $itemData ): array {
		return array_filter( [
			'id' => $itemData->getId()->getSerialization(),
			'type' => $itemData->getType(),
			'labels' => $itemData->getLabels() ? $itemData->getLabels()->toTextArray() : null,
			'descriptions' => $itemData->getDescriptions() ? $itemData->getDescriptions()->toTextArray() : null,
			'aliases' => $itemData->getAliases() ? $itemData->getAliases()->toTextArray() : null,
			'statements' => $itemData->getStatements() ? $this->statementsSerializer->serialize( $itemData->getStatements() ) : null,
			'sitelinks' => $itemData->getSiteLinks() ? $this->siteLinksSerializer->serialize( $itemData->getSiteLinks() ) : null,
		], function ( $part ) {
			return $part !== null;
		} );
	}

}
