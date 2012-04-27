( function () {
	module( 'wikibase.ui.PropertyEditTool', QUnit.newMwEnvironment() );

	var node = $( '<div/>' );
	var propertyEditTool = new window.wikibase.ui.PropertyEditTool( node );

	test( 'basic', function() {
		expect( 7 );

		equal(
			propertyEditTool._getToolbarParent().html(),
			node.html(),
			'propertyEditTool._getToolbarParent().html() == node.html()'
		);

		ok(
			propertyEditTool._toolbar instanceof window.wikibase.ui.PropertyEditTool.Toolbar,
			'propertyEditTool._toolbar instanceof window.wikibase.ui.PropertyEditTool.Toolbar'
		);

		ok(
			propertyEditTool._editableValues instanceof Array,
			'propertyEditTool._editableValues instanceof Array'
		);

		equal(
			propertyEditTool.isFull(),
			true,
			'isFull(): true'
		);

		equal(
			propertyEditTool.isInEditMode(),
			false,
			'isInEditMode(): false'
		);

		equal(
			propertyEditTool.isInAddMode(),
			false,
			'isInAddMode(): false'
		);

		equal(
			propertyEditTool._getValueElems().length,
			0,
			'propertyEditTool._getValueElems().length == 0'
		);

	} );

}() );
