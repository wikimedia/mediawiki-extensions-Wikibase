/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.StatementsChangerState' );

	var SUBJECT = wikibase.entityChangers.StatementsChangerState,
		entityId = new wikibase.datamodel.EntityId( 'Q1' ),
		statements = new wikibase.datamodel.StatementGroupSet();

	QUnit.test( 'get EntityId', function ( assert ) {
		var state = new SUBJECT( entityId, statements );
		assert.strictEqual(
			state.getEntityId(),
			entityId,
			'returns the Id'
		);
	} );

	QUnit.test( 'get StatementGroupSet', function ( assert ) {
		var state = new SUBJECT( entityId, statements );
		assert.strictEqual(
			state.getStatements(),
			statements,
			'returns the statements'
		);
	} );

}( wikibase ) );
