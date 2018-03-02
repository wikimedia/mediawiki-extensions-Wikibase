<?php

namespace Wikibase\Client\DataAccess\ParserFunctions;

use InvalidArgumentException;
use Language;
use ParserOutput;
use Status;
use Title;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;

/**
 * StatementGroupRenderer of the {{#property}} parser function.
 *
 * @license GPL-2.0-or-later
 */
class LanguageAwareRenderer implements StatementGroupRenderer {

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @var StatementTransclusionInteractor
	 */
	private $statementTransclusionInteractor;

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var Title
	 */
	private $title;

	public function __construct(
		Language $language,
		StatementTransclusionInteractor $statementTransclusionInteractor,
		ParserOutput $parserOutput,
		Title $title
	) {
		$this->language = $language;
		$this->statementTransclusionInteractor = $statementTransclusionInteractor;
		$this->parserOutput = $parserOutput;
		$this->title = $title;
	}

	/**
	 * @param EntityId $entityId
	 * @param string $propertyLabelOrId property label or ID (pXXX)
	 *
	 * @return string Wikitext
	 */
	public function render( EntityId $entityId, $propertyLabelOrId ) {
		try {
			$status = Status::newGood(
				$this->statementTransclusionInteractor->render(
					$entityId,
					$propertyLabelOrId
				)
			);
		} catch ( PropertyLabelNotResolvedException $ex ) {
			$this->parserOutput->addTrackingCategory( 'unresolved-property-category',
				$this->title );

			// @fixme use ExceptionLocalizer
			$status = $this->getStatusForException( $propertyLabelOrId, $ex->getMessage() );
		} catch ( EntityLookupException $ex ) {
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
