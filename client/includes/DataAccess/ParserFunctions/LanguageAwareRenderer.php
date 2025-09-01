<?php

declare( strict_types = 1 );

namespace Wikibase\Client\DataAccess\ParserFunctions;

use Exception;
use InvalidArgumentException;
use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
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

	private Language $language;
	private StatementTransclusionInteractor $statementTransclusionInteractor;
	private ParserOutput $parserOutput;
	private Title $title;

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
	public function render( EntityId $entityId, $propertyLabelOrId ): string {
		try {
			return $this->statementTransclusionInteractor->render(
				$entityId,
				$propertyLabelOrId
			);
		} catch ( PropertyLabelNotResolvedException $ex ) {
			$trackingCategories = MediaWikiServices::getInstance()->getTrackingCategories();
			$trackingCategories->addTrackingCategory(
				$this->parserOutput,
				'unresolved-property-category',
				$this->title
			);

			$message = $this->getMessageForException( $propertyLabelOrId, $ex );
		} catch ( EntityLookupException | InvalidArgumentException $ex ) {
			$message = $this->getMessageForException( $propertyLabelOrId, $ex );
		}

		$error = $message->inLanguage( $this->language )->text();
		return '<p class="error wikibase-error">' . $error . '</p>';
	}

	private function getMessageForException( string $propertyLabel, Exception $exception ): Message {
		$message = new Message( 'wikibase-property-render-error' );
		$message->plaintextParams( $propertyLabel );
		if ( $exception instanceof MessageException ) {
			$message->params( new Message( $exception->getKey(), $exception->getParams() ) );
		} else {
			$message->plaintextParams( $exception->getMessage() );
		}
		return $message;
	}

}
