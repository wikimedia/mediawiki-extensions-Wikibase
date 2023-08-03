<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\EntityUpdateFailed;

/**
 * @license GPL-2.0-or-later
 */
interface ItemUpdater {

	/**
	 * @throws EntityUpdateFailed
	 */
	public function update( Item $item, EditMetadata $editMetadata ): ItemRevision;

}
