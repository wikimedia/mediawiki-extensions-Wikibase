<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Description {

	private string $languageCode;
	private string $text;

	public function __construct( string $languageCode, string $text ) {
		$this->languageCode = $languageCode;
		$this->text = $text;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getText(): string {
		return $this->text;
	}

}
