<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\FederatedProperties;

use MediaWiki\Language\Language;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class WrappingEntityIdFormatterFactory implements EntityIdFormatterFactory {

	/** @var EntityIdFormatterFactory */
	private $inner;

	public function __construct( EntityIdFormatterFactory $inner ) {
		$this->inner = $inner;
	}

	/**
	 * @return string
	 */
	public function getOutputFormat() {
		return $this->inner->getOutputFormat();
	}

	/**
	 * @param Language $language
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter( Language $language ) {
		return new FederatedPropertiesEntityIdFormatter(
			$this->inner->getEntityIdFormatter( $language )
		);
	}

}
