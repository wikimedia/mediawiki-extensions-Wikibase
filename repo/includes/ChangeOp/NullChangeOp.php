<?php

namespace Wikibase\Repo\ChangeOp;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Summary;

/**
 * @license GPL-2.0+
 */
final class NullChangeOp implements ChangeOp {

	/**
	 * @var self
	 */
	private static $instance;

	public static function instance() {
		self::$instance = new self;
	}

	private function __construct() {
		// no op
	}

	/**
	 * @see ChangeOp::validate()
	 */
	public function validate( EntityDocument $entity ) {
		return Result::newSuccess();
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		// no op
	}
}
