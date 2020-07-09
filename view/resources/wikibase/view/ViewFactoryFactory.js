( function ( wb ) {
	'use strict';

	var ViewFactoryFactory = function () {};

	$.extend( ViewFactoryFactory.prototype, {

		/**
		 * Returns a ControllerViewFactory or ReadModeViewFactory depending on whether the page
		 * is editable. It removes the first two items of factoryArguments in case it is not.
		 *
		 * @param {boolean} isEditable
		 * @param {Array} factoryArguments
		 *
		 * @return {wikibase.view.ControllerViewFactory|wikibase.view.ReadModeViewFactory}
		 */
		getViewFactory: function ( isEditable, factoryArguments ) {
			if ( isEditable ) {
				return this._getControllerViewFactory( factoryArguments );
			}

			return this._getReadModeViewFactory( factoryArguments );
		},

		_getControllerViewFactory: function ( factoryArguments ) {
			return this._getInstance(
				wb.view.ControllerViewFactory,
				factoryArguments
			);
		},

		_getReadModeViewFactory: function ( factoryArguments ) {
			factoryArguments.shift();
			factoryArguments.shift();

			return this._getInstance(
				wb.view.ReadModeViewFactory,
				factoryArguments
			);
		},

		_getInstance: function ( clazz, args ) {
			args.unshift( null );

			return new ( Function.prototype.bind.apply(
				clazz,
				args
			) )();
		}

	} );

	module.exports = ViewFactoryFactory;
}( wikibase ) );
