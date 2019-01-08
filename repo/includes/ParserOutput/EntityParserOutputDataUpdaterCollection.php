<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikimedia\Assert\Assert;

/**
 * @todo have ItemParserOutputDataUpdater, etc. instead.
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityParserOutputDataUpdaterCollection {

	/**
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var EntityParserOutputUpdater[]
	 */
	private $dataUpdaters;

	/**
	 * @param ParserOutput $parserOutput
	 * @param EntityParserOutputUpdater[] $dataUpdaters
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ParserOutput $parserOutput, array $dataUpdaters ) {
		Assert::parameterElementType( EntityParserOutputUpdater::class, $dataUpdaters, '$dataUpdaters' );

		$this->parserOutput = $parserOutput;
		$this->dataUpdaters = $dataUpdaters;
	}

	public function updateParserOutput( EntityDocument $entity ) {
		foreach ( $this->dataUpdaters as $dataUpdater ) {
			$dataUpdater->updateParserOutput( $this->parserOutput, $entity );
		}
	}

}
