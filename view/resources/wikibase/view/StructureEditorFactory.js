( function( wb ) {
	'use strict';

	var MODULE = wb.view;

	/**
	 * A factory for creating structure editors
	 *
	 * @class wikibase.view.StructureEditorFactory
	 * @license GPL-2.0+
	 * @since 0.5
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 * @constructor
	 */
	var SELF = MODULE.StructureEditorFactory = function StructureEditorFactory( toolbarFactory ) {
		this._toolbarFactory = toolbarFactory;
	};

	SELF.prototype.getAdder = function( add, $dom, label ) {
		var options = { label: label };
		$dom = this._toolbarFactory.getToolbarContainer( $dom );
		$dom.on(
			'addtoolbaradd.addtoolbar',
			function( event ) {
				if ( event.target !== $dom.get( 0 ) ) {
					// This is a different toolbar than we thought
					return;
				}
				add();
			}
		);
		return this._toolbarFactory.getAddToolbar( options, $dom );
	};

	SELF.prototype.getRemover = function( remove, $dom, title ) {
		var options = { title: title };
		$dom = this._toolbarFactory.getToolbarContainer( $dom );
		$dom.on( 'removetoolbarremove.removetoolbar', remove );
		return this._toolbarFactory.getRemoveToolbar( options, $dom );
	};

}( wikibase ) );
