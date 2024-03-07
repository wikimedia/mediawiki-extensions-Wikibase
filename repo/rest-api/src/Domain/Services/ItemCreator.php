<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;

/**
 * @license GPL-2.0-or-later
 */
interface ItemCreator {

	public function create( Item $item, EditMetadata $editMetadata ): ItemRevision;
}
