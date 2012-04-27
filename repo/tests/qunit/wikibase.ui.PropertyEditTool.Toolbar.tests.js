( function () {
	module( 'wikibase.ui.PropertyEditTool.Toolbar', QUnit.newMwEnvironment() );

	var toolbar = new window.wikibase.ui.PropertyEditTool.Toolbar();

	test( 'basic', function() {
		expect( 2 );

		ok(
			toolbar._items instanceof Array,
			'toolbar._items instanceof Array'
		);

		equal(
			toolbar._elem[0].nodeName,
			'DIV',
			'toolbar._elem[0].nodeName == \'DIV\''
		)

	} );

}() );
