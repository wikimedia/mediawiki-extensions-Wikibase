<?php

namespace Wikibase\DataAccess;

use InvalidArgumentException;
use Language;
use Status;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\PropertyLabelResolver;

class PropertyParserFunctionLanguageRenderer {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @param EntityLookup $entityLookup
	 * @param PropertyLabelResolver $propertyLabelResolver
	 */
	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
	}

	/**
	 * @param ItemId $itemId
	 * @param Language $language
	 * @param string $propertyLabel property label or ID (pXXX)
	 *
	 * @return string
	 */
	public function render( ItemId $itemId, Language $language, $propertyLabel ) {
		$renderer = $this->getRendererForLanguage( $language );

		try {
			$status = $renderer->renderForEntityId( $itemId, $propertyLabel );
		} catch ( PropertyLabelNotResolvedException $ex ) {
			$status = $this->getStatusForException( $propertyLabel, $ex->getMessage() );
		} catch ( InvalidArgumentException $ex ) {
			$status = $this->getStatusForException( $propertyLabel, $ex->getMessage() );
		}

		if ( !$status->isGood() ) {
			$error = $status->getMessage()->inLanguage( $language )->text();
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

	/**
	 * Build a PropertyParserFunctionRenderer object for a given language.
	 *
	 * @param Language $language
	 *
	 * @return PropertyParserFunctionRenderer
	 */
	private function getRendererForLanguage( Language $language ) {
		return new PropertyParserFunctionRenderer(
			$language,
			$this->entityLookup,
			$this->propertyLabelResolver,
			$this->newSnakFormatterForLanguage( $language )
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return SnakFormatter
	 */
	private function newSnakFormatterForLanguage( Language $language ) {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$languageFallbackChainFactory = $wikibaseClient->getLanguageFallbackChainFactory();
		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $language,
			LanguageFallbackChainFactory::FALLBACK_SELF | LanguageFallbackChainFactory::FALLBACK_VARIANTS
		);

		$options = new FormatterOptions( array(
			'languages' => $languageFallbackChain,
			// ...more options...
		) );

		$snakFormatter = $wikibaseClient->newSnakFormatter( SnakFormatter::FORMAT_WIKI, $options );

		return $snakFormatter;
	}

}
