<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
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
	 * @var EntityParserOutputDataUpdater[]
	 */
	private $dataUpdaters;

	/**
	 * @param ParserOutput $parserOutput
	 * @param EntityParserOutputThing[] $dataUpdaters
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ParserOutput $parserOutput, array $dataUpdaters ) {
		Assert::parameterElementType( EntityParserOutputDataUpdater::class, $dataUpdaters, '$dataUpdaters' );

		$this->parserOutput = $parserOutput;
		$this->dataUpdaters = $dataUpdaters;
	}

	public function updateParserOutput( EntityDocument $entity ) {
		foreach ( $this->dataUpdaters as $dataUpdater ) {
			$dataUpdater->processEntity( $entity );
			$dataUpdater->updateParserOutput( $this->parserOutput );
		}

		/**
		 * @var EntityParserOutputThing $parserOutputThing
		 */
		foreach ( $something as $parserOutputThing ) {
			if ( $parserOutputThing->canHandle( $entity ) ) {
				$parserOutputThing->updateParserOutput( $entity );
				break;
			}
		}
	}

}

interface EntityParserOutputThing {

	public function canHandle( EntityDocument $e ): bool;

	public function updateParserOutput( ParserOutput $po, EntityDocument $entity );

}

class LexemeParserOutputThing implements EntityParserOutputThing {

	public function __construct( StatementDataUpdater $statementDataUpdater ) {
		$this->statementDataUpdater = $statementDataUpdater;
	}

	public function canHandle( EntityDocument $e ): bool {
		return $e instanceof Lexeme;
	}

	public function updateParserOutput( ParserOutput $po, EntityDocument $entity ) {
		/**
		 * @var Lexeme $entity
		 */

		foreach ( $entity->getStatements() as $s ) {
			$this->statementDataUpdater->processStatement( $s );
		}

		foreach ( $entity->getForms()->toArray() as $l ) {
			foreach ( $l->getStatements() as $s ) {
				$this->statementDataUpdater->processStatement( $s );
			}
		}

		foreach ( $entity->getForms()->toArray() as $l ) {
			foreach ( $l->getStatements() as $s ) {
				$this->statementDataUpdater->processStatement( $s );
			}
		}
	}
}