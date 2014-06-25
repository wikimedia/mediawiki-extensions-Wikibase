/**
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv, Formatter ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert handling input of globe coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'GlobeCoordinateInput', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this.preview = new vv.ExpertExtender.Preview( function() {
			return self.viewState().getFormattedValue();
		} );


		var precisionMsgKey = 'valueview-expert-globecoordinateinput-precision';
		var $precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( precisionMsgKey ) ) );

		this.precisionRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-precision',
			getPrecisionValues(),
			function( newPrecisionLevel ) {
				self._viewNotifier.notify( 'change' );
			},
			function(){
				var value = self.viewState().value();
				if( !value ) {
					return value;
				}
				value = value.getValue().getPrecision();
				return getPrecisionSetting( value ) || {
					custom: true,
					value: value,
					label: self._messageProvider.getMessage('valueview-expert-globecoordinateinput-customprecision', [ Formatter.PRECISIONTEXT( value ) ] )
				};
			}
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this.preview,
				new vv.ExpertExtender.Toggler(
					this._messageProvider,
					$precisionContainer
				),
				new vv.ExpertExtender.Container(
					$precisionContainer,
					this.precisionRotator
				)
			]
		);

		this.addExtension( inputExtender );
	}, {

		/**
		 * @type {jQuery.valueview.ExpertExtender.Preview}
		 */
		preview: null,

		/**
		 * @type {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		precisionRotator: null,

		/**
		 * Options.
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-globecoordinateinput-precision': 'Precision'
			}
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			if( !this.precisionRotator ) { // happens when called statically
				return {};
			}

			var options = {},
				precision = this.precisionRotator.getValue();

			if( precision !== null ) {
				options.precision = precision;
			}

			return options;
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.precisionRotator = null;
			this.preview = null;
			PARENT.prototype.destroy.call( this );
		}
	} );

	/**
	 * Rounds a given precision for being able to use it as internal "constant".
	 *
	 * @since 0.1
	 *
	 * @param {number} precision
	 * @return {number}
	 */
	function roundPrecision( precision ) {
		return parseFloat( precision.toPrecision(4) );
	}

	/**
	 * Returns the original precision level for an unrounded precision.
	 *
	 * @since 0.1
	 *
	 * @param {number} precision
	 * @return {number|null}
	 */
	function getPrecisionSetting( precision ) {
		var rounded,
			actualPrecision = null,
			roundedPrecision = roundPrecision( precision );

		$.each( PRECISIONS, function( i, precision ) {
			rounded = roundPrecision( precision );
			if( rounded === roundedPrecision ) {
				actualPrecision = precision;
				return false;
			}
		} );

		return actualPrecision;
	}

	function getPrecisionValues() {
		var precisionValues = [];
		$.each( PRECISIONS, function( i, precision ) {
			var label = Formatter.PRECISIONTEXT( precision );
			precisionValues.unshift( {
				value: roundPrecision( precision ),
				label: label
			} );
		} );
		return precisionValues;
	}

	var PRECISIONS = [
		10,
		1,
		0.1,
		0.01,
		0.001,
		0.0001,
		0.00001,
		0.000001,
		1 / 60,
		1 / 3600,
		1 / 36000,
		1 / 360000,
		1 / 3600000
	];

}( jQuery, jQuery.valueview, globeCoordinate.Formatter ) );
