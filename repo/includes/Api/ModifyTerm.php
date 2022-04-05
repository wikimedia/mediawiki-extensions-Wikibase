<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Api;

use Wikibase\Lib\Summary;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API module to set the terms for a Wikibase entity.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 */
abstract class ModifyTerm extends ModifyEntity {

	/**
	 * Creates a Summary object based on the given API call parameters.
	 * The Summary will be initializes with the appropriate action name
	 * and target language. It will not have any summary arguments set.
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	protected function createSummary( array $params ): Summary {
		$set = isset( $params['value'] ) && strlen( $params['value'] ) > 0;

		$summary = parent::createSummary( $params );
		$summary->setAction( $set ? 'set' : 'remove' );
		$summary->setLanguage( $params['language'] );

		return $summary;
	}

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams(): array {
		return array_merge(
			parent::getAllowedParams(),
			[
				'language' => [
					// TODO inject TermsLanguages as a service
					ParamValidator::PARAM_TYPE => WikibaseRepo::getTermsLanguages()->getLanguages(),
					ParamValidator::PARAM_REQUIRED => true,
				],
				'value' => [
					ParamValidator::PARAM_TYPE => 'string',
				],
			]
		);
	}

}
