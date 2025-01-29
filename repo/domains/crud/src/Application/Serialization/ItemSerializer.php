<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\Item as ItemWriteModel;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;

/**
 * @license GPL-2.0-or-later
 */
class ItemSerializer {

	private LabelsSerializer $labelsSerializer;
	private DescriptionsSerializer $descriptionsSerializer;
	private AliasesSerializer $aliasesSerializer;
	private StatementListSerializer $statementsSerializer;
	private SitelinksSerializer $sitelinksSerializer;

	public function __construct(
		LabelsSerializer $labelsSerializer,
		DescriptionsSerializer $descriptionsSerializer,
		AliasesSerializer $aliasesSerializer,
		StatementListSerializer $statementsSerializer,
		SitelinksSerializer $sitelinksSerializer
	) {
		$this->labelsSerializer = $labelsSerializer;
		$this->descriptionsSerializer = $descriptionsSerializer;
		$this->aliasesSerializer = $aliasesSerializer;
		$this->statementsSerializer = $statementsSerializer;
		$this->sitelinksSerializer = $sitelinksSerializer;
	}

	public function serialize( Item $item ): array {
		return [
			'id' => $item->getId()->getSerialization(),
			'type' => ItemWriteModel::ENTITY_TYPE,
			'labels' => $this->labelsSerializer->serialize( $item->getLabels() ),
			'descriptions' => $this->descriptionsSerializer->serialize( $item->getDescriptions() ),
			'aliases' => $this->aliasesSerializer->serialize( $item->getAliases() ),
			'statements' => $this->statementsSerializer->serialize( $item->getStatements() ),
			'sitelinks' => $this->sitelinksSerializer->serialize( $item->getSitelinks() ),
		];
	}

}
