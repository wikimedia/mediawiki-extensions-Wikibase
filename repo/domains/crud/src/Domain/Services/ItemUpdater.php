<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemRevision;

/**
 * @license GPL-2.0-or-later
 */
interface ItemUpdater {

	public function update( Item $item, EditMetadata $editMetadata ): ItemRevision;

}
