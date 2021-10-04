<?php

namespace Wikibase\Repo\Validators;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikimedia\Assert\Assert;

/**
 * Validator for interwiki links.
 * Checks whether the page title exists on the foreign wiki.
 *
 * @license GPL-2.0-or-later
 * @author Jonas Kress
 */
class InterWikiLinkExistsValidator implements ValueValidator {

	/**
	 * @var MediaWikiPageNameNormalizer
	 */
	private $mediaWikiPageNameNormalizer;

	/**
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @param MediaWikiPageNameNormalizer $mediaWikiPageNameNormalizer
	 * @param string $apiUrl
	 */
	public function __construct( MediaWikiPageNameNormalizer $mediaWikiPageNameNormalizer, $apiUrl ) {
		$this->mediaWikiPageNameNormalizer = $mediaWikiPageNameNormalizer;
		$this->apiUrl = $apiUrl;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param StringValue|string $value
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( $value ) {
		Assert::parameterType( 'string|DataValues\StringValue', $value, '$value' );

		if ( $value instanceof StringValue ) {
			$value = $value->getValue();
		}

		$errors = [];
		if ( !$this->isPageNameExisting( $value ) ) {
			$errors[] = Error::newError(
				"Page does not exist: " . $value,
				null,
				'page-not-exists',
				[ $value ]
			);
		}

		return empty( $errors ) ? Result::newSuccess() : Result::newError( $errors );
	}

	/**
	 * @param string $pageName
	 * @return boolean
	 */
	private function isPageNameExisting( $pageName ) {
		$actualPageName = $this->mediaWikiPageNameNormalizer->normalizePageName(
			$pageName,
			$this->apiUrl
		);

		return $actualPageName !== false;
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
