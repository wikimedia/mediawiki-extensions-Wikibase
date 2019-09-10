( function ( wb ) {
	'use strict';

	/**
	 * @constructor
	 * @param {string} entityId
	 * @param {wikibase.datamodel.StatementGroupSet} statementsGroupSet
	 */
	var SELF = wb.entityChangers.StatementsChangerState = function WbEntityChangersStatementsChanger(
		entityId,
		statementsGroupSet
	) {
		this._entityId = entityId;
		this._statementGroupSet = statementsGroupSet;
	};

	SELF.prototype.getEntityId = function () {
		return this._entityId;
	};

	SELF.prototype.getStatements = function () {
		return this._statementGroupSet;
	};

}( wikibase ) );
