<?php

namespace Wikibase\Lib\Interactors;

use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermSearchOptions {

	private const HARD_LIMIT = 2500;

	/**
	 * @var bool do a case sensitive search
	 */
	private $isCaseSensitive = false;

	/**
	 * @var bool do a prefix search
	 */
	private $isPrefixSearch = false;

	/**
	 * @var bool use language fallback in the search
	 */
	private $useLanguageFallback = true;

	/**
	 * @var int
	 */
	private $limit = self::HARD_LIMIT;

	/**
	 * @return int
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * @return bool
	 */
	public function getIsCaseSensitive() {
		return $this->isCaseSensitive;
	}

	/**
	 * @return bool
	 */
	public function getIsPrefixSearch() {
		return $this->isPrefixSearch;
	}

	/**
	 * @return bool
	 */
	public function getUseLanguageFallback() {
		return $this->useLanguageFallback;
	}

	/**
	 * @param int $limit Hard upper limit of 2500
	 */
	public function setLimit( $limit ) {
		Assert::parameterType( 'integer', $limit, '$limit' );
		Assert::parameter( $limit > 0, '$limit', 'Must be positive' );
		if ( $limit > self::HARD_LIMIT ) {
			$limit = self::HARD_LIMIT;
		}
		$this->limit = $limit;
	}

	/**
	 * @param bool $caseSensitive
	 */
	public function setIsCaseSensitive( $caseSensitive ) {
		Assert::parameterType( 'boolean', $caseSensitive, '$caseSensitive' );
		$this->isCaseSensitive = $caseSensitive;
	}

	/**
	 * @param bool $prefixSearch
	 */
	public function setIsPrefixSearch( $prefixSearch ) {
		Assert::parameterType( 'boolean', $prefixSearch, '$prefixSearch' );
		$this->isPrefixSearch = $prefixSearch;
	}

	/**
	 * @param bool $useLanguageFallback
	 */
	public function setUseLanguageFallback( $useLanguageFallback ) {
		Assert::parameterType( 'boolean', $useLanguageFallback, '$useLanguageFallback' );
		$this->useLanguageFallback = $useLanguageFallback;
	}

}
