<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataAccess\ParserFunctions;

use Exception;
use InvalidArgumentException;
use Language;
use MediaWiki\MediaWikiServices;
use Message;
use ParserOutput;
use Status;
use Title;
use Wikibase\Client\DataAccess\StatementTransclusionInteractor;
use Wikibase\Client\PropertyLabelNotResolvedException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\MessageException;

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
			$trackingCategories = MediaWikiServices::getInstance()->getTrackingCategories();
			$trackingCategories->addTrackingCategory(
				$this->parserOutput,
				'unresolved-property-category',
				$this->title
			);

			$status = $this->getStatusForException( $propertyLabelOrId, $ex );
		} catch ( EntityLookupException | InvalidArgumentException $ex ) {
			$status = $this->getStatusForException( $propertyLabelOrId, $ex );
		}

		if ( !$status->isGood() ) {
			$error = $status->getMessage()->inLanguage( $this->language )->text();
			return '<p class="error wikibase-error">' . $error . '</p>';
		}

		return $status->getValue();
	}

	private function getStatusForException( string $propertyLabel, Exception $exception ): Status {
		if ( $exception instanceof MessageException ) {
			$message = new Message( $exception->getKey(), $exception->getParams() );
		} else {
			$message = $exception->getMessage();
		}
		return Status::newFatal(
			'wikibase-property-render-error',
			$propertyLabel,
			$message
		);
	}

}
