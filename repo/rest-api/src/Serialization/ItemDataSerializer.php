<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use ArrayObject;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;

/**
 * @license GPL-2.0-or-later
 */
class ItemDataSerializer {

	private StatementListSerializer $statementsSerializer;
	private SiteLinkListSerializer $siteLinksSerializer;

	/**
	 * @param StatementListSerializer $statementsSerializer should have $useObjectsForMaps set to true when used within a json presenter
	 * @param SiteLinkListSerializer $siteLinksSerializer should have $useObjectsForMaps set to true when used within a json presenter
	 */
	public function __construct( StatementListSerializer $statementsSerializer, SiteLinkListSerializer $siteLinksSerializer ) {
		$this->statementsSerializer = $statementsSerializer;
		$this->siteLinksSerializer = $siteLinksSerializer;
	}

	public function serialize( ItemData $itemData ): array {
		return array_filter(
			[
				'id' => $itemData->getId()->getSerialization(),
				'type' => $itemData->getType(),
				'labels' => $itemData->getLabels() ? new ArrayObject( $itemData->getLabels()->toTextArray() ) : null,
				'descriptions' => $itemData->getDescriptions() ? new ArrayObject( $itemData->getDescriptions()->toTextArray() ) : null,
				'aliases' => $itemData->getAliases() ? new ArrayObject( $itemData->getAliases()->toTextArray() ) : null,
				'statements' => $itemData->getStatements() ? $this->statementsSerializer->serialize( $itemData->getStatements() ) : null,
				'sitelinks' => $itemData->getSiteLinks() ? $this->siteLinksSerializer->serialize( $itemData->getSiteLinks() ) : null,
			],
			function ( $part ) {
				return $part !== null;
			}
		);
	}

}
