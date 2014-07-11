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

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionLanguageRenderer implements PropertyParserFunctionRenderer {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyLabelResolver
	 */
	private $propertyLabelResolver;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param EntityLookup $entityLookup
	 * @param PropertyLabelResolver $propertyLabelResolver
	 */
	public function __construct(
		EntityLookup $entityLookup,
		PropertyLabelResolver $propertyLabelResolver,
		Language $language
	) {
		$this->entityLookup = $entityLookup;
		$this->propertyLabelResolver = $propertyLabelResolver;
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
		$renderer = $this->getEntityRendererForLanguage();

		try {
			$status = $renderer->renderForEntityId( $itemId, $propertyLabel );
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

	/**
	 * Build a PropertyParserFunctionRenderer object for a given language.
	 *
	 * @fixme inject / refactor this
	 *
	 * @return PropertyParserFunctionRenderer
	 */
	private function getEntityRendererForLanguage() {
		return new PropertyParserFunctionEntityRenderer(
			$this->language,
			$this->entityLookup,
			$this->propertyLabelResolver,
			$this->newSnakFormatterForLanguage()
		);
	}

	/**
	 * @fixme inject this
	 *
	 * @param Language $language
	 *
	 * @return SnakFormatter
	 */
	private function newSnakFormatterForLanguage() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$languageFallbackChainFactory = $wikibaseClient->getLanguageFallbackChainFactory();
		$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $this->language,
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
