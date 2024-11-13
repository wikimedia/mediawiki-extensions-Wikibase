/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'wikibase.entityChangers.StatementsChangerState' );

	var SUBJECT = wikibase.entityChangers.StatementsChangerState,
		entityId = new datamodel.EntityId( 'Q1' ),
		statements = new datamodel.StatementGroupSet();

	QUnit.test( 'get EntityId', ( assert ) => {
		var state = new SUBJECT( entityId, statements );
		assert.strictEqual(
			state.getEntityId(),
			entityId,
			'returns the Id'
		);
	} );

	QUnit.test( 'get StatementGroupSet', ( assert ) => {
		var state = new SUBJECT( entityId, statements );
		assert.strictEqual(
			state.getStatements(),
			statements,
			'returns the statements'
		);
	} );

}( wikibase ) );
