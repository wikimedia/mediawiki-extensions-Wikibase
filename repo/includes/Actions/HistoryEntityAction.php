<?php

namespace Wikibase;

use HistoryAction;
use IContextSource;
use Page;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Store\EntityIdLookup;

/**
 * Handles the history action for Wikibase entities.
 *
 * @since 0.3
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class HistoryEntityAction extends HistoryAction {

	/**
	 * @var EntityIdLookup $entityIdLookup
	 */
	private $entityIdLookup;

	/**
	 * @var LabelDescriptionLookup $labelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @param Page $page
	 * @param IContextSource|null $context
	 * @param EntityIdLookup $entityIdLookup
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		Page $page,
		IContextSource $context = null,
		EntityIdLookup $entityIdLookup,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		parent::__construct( $page, $context );
		$this->entityIdLookup = $entityIdLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * Return a string for use as title.
	 *
	 * @return string
	 */
	protected function getPageTitle() {
		$title = $this->getTitle();

		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );

		if ( !$entityId ) {
			return parent::getPageTitle();
		}

		$label = $this->labelDescriptionLookup->getLabel( $entityId );
		$idSerialization = $entityId->getSerialization();

		if ( $label !== null ) {
			$labelText = $label->getText();
			// Escaping HTML characters in order to retain original label that may contain HTML
			// characters. This prevents having characters evaluated or stripped via
			// OutputPage::setPageTitle:
			return $this->msg( 'wikibase-history-title-with-label' )
				->rawParams( $idSerialization, htmlspecialchars( $labelText ) )->text();
		} else {
			return $this->msg( 'wikibase-history-title-without-label' )
				->rawParams( $idSerialization )->text();
		}
	}

}
