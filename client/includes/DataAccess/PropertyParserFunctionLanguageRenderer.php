<?php

namespace Wikibase\DataAccess;

use InvalidArgumentException;
use Language;
use Status;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\PropertyLabelNotResolvedException;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionLanguageRenderer implements PropertyParserFunctionRenderer {

	/**
	 * @var PropertyParserFunctionEntityRenderer
	 */
	private $entityRenderer;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param PropertyParserFunctionEntityRenderer $entityRenderer
	 * @param Language $language
	 */
	public function __construct(
		PropertyParserFunctionEntityRenderer $entityRenderer,
		Language $language
	) {
		$this->entityRenderer = $entityRenderer;
		$this->language = $language;
	}

	/**
	 * @param ItemId $itemId
	 * @param Language $language
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return string
	 */
	public function render( ItemId $itemId, $propertyLabel ) {
		try {
			$status = $this->entityRenderer->renderForEntityId( $itemId, $propertyLabel );
		} catch ( PropertyLabelNotResolvedException $ex ) {
			$status = $this->getStatusForException( $propertyLabel, $ex->getMessage() );
		} catch ( InvalidArgumentException $ex ) {
			$status = $this->getStatusForException( $propertyLabel, $ex->getMessage() );
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
