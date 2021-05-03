<?php

namespace Wikibase\Repo;

use Language;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\Formatters\DispatchingEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\UnknownTypeEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\EntityIdFormatterFactory;
use Wikimedia\Assert\Assert;

/**
 * A factory for generating EntityIdFormatter returning HTML.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityIdHtmlLinkFormatterFactory implements EntityIdFormatterFactory {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var callable[]
	 */
	private $formatterCallbacks;

	public function __construct(
		EntityTitleLookup $titleLookup,
		array $formatterCallbacks = []
	) {
		Assert::parameterElementType( 'callable', $formatterCallbacks, '$formatterCallbacks' );

		$this->formatterCallbacks = $formatterCallbacks;
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @see EntityIdFormatterFactory::getOutputFormat
	 *
	 * @return string SnakFormatter::FORMAT_HTML
	 */
	public function getOutputFormat() {
		return SnakFormatter::FORMAT_HTML;
	}

	/**
	 * @see EntityIdFormatterFactory::getEntityIdFormatter
	 *
	 * @param Language $language
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter( Language $language ) {
		return new DispatchingEntityIdHtmlLinkFormatter(
			$this->buildFormatters( $language ),
			new UnknownTypeEntityIdHtmlLinkFormatter(
				$this->titleLookup,
				new NonExistingEntityIdHtmlFormatter( 'wikibase-deletedentity-' )
			)
		);
	}

	/**
	 * @param Language $language
	 *
	 * @return EntityIdFormatter[]
	 */
	private function buildFormatters( Language $language ) {
		$formatters = [];

		foreach ( $this->formatterCallbacks as $type => $func ) {
			$formatters[ $type ] = call_user_func( $func, $language );
		}

		return $formatters;
	}

}
