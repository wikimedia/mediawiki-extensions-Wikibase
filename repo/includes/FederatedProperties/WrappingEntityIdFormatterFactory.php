<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\FederatedProperties;

use Language;
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

	public function getOutputFormat() {
		return $this->inner->getOutputFormat();
	}

	public function getEntityIdFormatter( Language $language ) {
		return new FederatedPropertiesEntityIdFormatter(
			$this->inner->getEntityIdFormatter( $language )
		);
	}

}
