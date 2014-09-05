<?php

namespace Wikibase;

/**
 * Factory to create EntityParserOutputGenerator objects.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutputGeneratorFactory {

	public function getEntityParserOutputGenerator() {
		
	}

	public function getEntityView(
		IContextSource $context,
		SnakFormatter $snakFormatter,
		EntityTitleLookup $entityTitleLookup,
		EntityInfoBuilderFactory $entityInfoBuilderFactory
	) {
		$sectionEditLinkGenerator = new SectionEditLinkGenerator();

		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup
		);

		$claimHtmlGenerator = new ClaimHtmlGenerator(
			$snakHtmlGenerator,
			$entityTitleLookup
		);

		$language = $context->getLanguage();

		$claimsView = new ClaimsView(
			$entityInfoBuilderFactory,
			$entityTitleLookup,
			$sectionEditLinkGenerator,
			$claimHtmlGenerator,
			$language->getCode()
		);

		$fingerprintView = new FingerprintView(
			$sectionEditLinkGenerator,
			$language->getCode()
		);

		return $this->newEntityView(
			$fingerprintView,
			$claimsView,
			$language
		);
	}

}
