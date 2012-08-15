<?php

namespace Wikibase;

/**
 * Class representing a Wikibase statement.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Statements
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementObject implements Statement {

	/**
	 * The id of the entity.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $entityId;

	/**
	 * The type of the entity.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	protected $entityType;

	/**
	 * The number of the statement within the entity.
	 *
	 * @since 0.1
	 *
	 * @var integer
	 */
	protected $statementNumber;

	/**
	 * @since 0.1
	 *
	 * @var Claim
	 */
	protected $claim;

	/**
	 * @since 0.1
	 *
	 * @var array of Reference
	 */
	protected $references;

	/**
	 * @since 0.1
	 *
	 * @var integer, element of the Statement::RANK_ enum
	 */
	protected $rank;

}