/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	function simpleWidget( name, fn ) {
		var widget = function ( options, element ) {
			$.Widget.apply( this, arguments );
			this._createWidget( options, element );
			fn.apply( this );
		};

		widget.prototype = new $.Widget();

		widget.prototype.widgetFullName = name;
		widget.prototype.widgetName = name;
		widget.prototype.widgetEventPrefix = name;
		widget.prototype.value = function () {};

		return widget;
	}

	wb.tests.getMockListItemAdapter = function ( widgetName, initFn ) {
		var Widget = simpleWidget( widgetName, initFn );

		return new $.wikibase.listview.ListItemAdapter( {
			listItemWidget: Widget,
			newItemOptionsFn: function ( value ) {
				return { value: value };
			}
		} );
	};

}( wikibase ) );
