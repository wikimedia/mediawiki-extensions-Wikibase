<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class MatchedData {

	private string $type;
	private string $languageCode;
	private string $text;

	public function __construct( string $type, string $languageCode, string $text ) {
		$this->type = $type;
		$this->languageCode = $languageCode;
		$this->text = $text;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getText(): string {
		return $this->text;
	}

}
