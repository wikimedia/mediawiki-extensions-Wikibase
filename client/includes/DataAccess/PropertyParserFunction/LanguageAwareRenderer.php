<?php

namespace Wikibase\DataAccess\PropertyParserFunction;

use InvalidArgumentException;
use Language;
use Status;
use Wikibase\DataAccess\EntityStatementsRenderer;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\PropertyLabelNotResolvedException;

/**
 * PropertyClaimsRenderer of the {{#property}} parser function.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Liangent < liangent@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class LanguageAwareRenderer implements PropertyClaimsRenderer {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var EntityStatementsRenderer
	 */
	private $entityStatementsRenderer;

	/**
	 * @param Language $language
	 * @param EntityStatementsRenderer $entityStatementsRenderer
	 */
	public function __construct(
		Language $language,
		EntityStatementsRenderer $entityStatementsRenderer
	) {
		$this->language = $language;
		$this->entityStatementsRenderer = $entityStatementsRenderer;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 *
	 * @return string
	 */
	public function render( EntityId $entityId, $propertyLabelOrId ) {
		try {
			$status = Status::newGood(
				$this->entityStatementsRenderer->render( $entityId, $propertyLabelOrId )
			);
		} catch ( PropertyLabelNotResolvedException $ex ) {
			// @fixme use ExceptionLocalizer
			$status = $this->getStatusForException( $propertyLabelOrId, $ex->getMessage() );
		} catch ( InvalidArgumentException $ex ) {
			$status = $this->getStatusForException( $propertyLabelOrId, $ex->getMessage() );
		}

		if ( !$status->isGood() ) {
			$error = $status->getMessage()->inLanguage( $this->language )->text();
			return '<p class="error wikibase-error">' . $error . '</p>';
		}

		return $status->getValue();
	}

	/**
	 * @param string $propertyLabel
	 * @param string $message
	 *
	 * @return Status
	 */
	private function getStatusForException( $propertyLabel, $message ) {
		return Status::newFatal(
			'wikibase-property-render-error',
			$propertyLabel,
			$message
		);
	}

}
