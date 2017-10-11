( function ( QUnit ) {
	sinon.assert.fail = function ( msg ) {
		QUnit.assert.ok( false, msg );
	};
	sinon.assert.pass = function ( msg ) {
		QUnit.assert.ok( true, msg );
	};
} )( QUnit );
