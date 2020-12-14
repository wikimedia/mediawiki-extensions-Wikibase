module.exports = ( function( $, vv ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

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

	/**
	 * `Valueview` expert handling input of `GlobeCoordinate` values.
	 *
	 * @class jQuery.valueview.experts.GlobeCoordinateValue
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'GlobeCoordinateInput', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this.preview = new vv.ExpertExtender.Preview(
			function() {
				return self.viewState().getFormattedValue();
			},
			this._messageProvider
		);

		var precisionMsgKey = 'valueview-expert-globecoordinateinput-precision';
		var $precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' )
				.addClass( 'ui-listrotator-caption' )
				.text( this._messageProvider.getMessage( precisionMsgKey ) ) );

		this.precisionRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-precision',
			this._getPrecisionValues(),
			function( newPrecisionLevel ) {
				self._viewNotifier.notify( 'change' );
			},
			function() {
				var value = self.viewState().value();
				if ( !value ) {
					return value;
				}

				var precision = value.getValue().getPrecision();
				if ( !precision ) {
					return {
						custom: true,
						value: null,
						label: self._messageProvider.getMessage(
							'valueview-expert-globecoordinateinput-nullprecision'
						)
					};
				}

				return self._getPrecisionSetting( precision ) || {
					custom: true,
					value: precision,
					label: self._messageProvider.getMessage(
						'valueview-expert-globecoordinateinput-customprecision',
						[ self._getPrecisionLabel( precision ) ]
					)
				};
			},
			this._messageProvider
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this.preview,
				new vv.ExpertExtender.Container(
					$precisionContainer,
					this.precisionRotator
				)
			]
		);

		this.addExtension( inputExtender );
	}, {

		/**
		 * @property {jQuery.valueview.ExpertExtender.Preview}
		 */
		preview: null,

		/**
		 * @property {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		precisionRotator: null,

		/**
		 * @inheritdoc
		 * @protected
		 */
		_options: {
			messages: {
				'valueview-expert-globecoordinateinput-precision': 'Precision'
			}
		},

		/**
		 * @inheritdoc
		 */
		valueCharacteristics: function() {
			var options = {},
				precision = this.precisionRotator.getValue();

			if ( precision !== null ) {
				options.precision = precision;
			}

			return options;
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			if ( this.precisionRotator ) {
				this.precisionRotator.destroy();
				this.precisionRotator = null;
			}
			if ( this.preview ) {
				this.preview.destroy();
				this.preview = null;
			}

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Rounds a given precision for being able to use it as internal "constant".
		 *
		 * @ignore
		 * @private
		 *
		 * @param {number} precision
		 * @return {number}
		 */
		_roundPrecision: function( precision ) {
			return parseFloat( precision.toPrecision( 6 ) );
		},

		/**
		 * @private
		 *
		 * @param {number} precision
		 * @return {string}
		 */
		_getPrecisionLabel: function( precision ) {
			var presets = {
				'valueview-expert-globecoordinateinput-precisionlabel-arcminute': 1 / 60,
				'valueview-expert-globecoordinateinput-precisionlabel-arcsecond': 1 / 3600,
				'valueview-expert-globecoordinateinput-precisionlabel-tenth-of-arcsecond': 1 / 36000,
				'valueview-expert-globecoordinateinput-precisionlabel-hundredth-of-arcsecond': 1 / 360000,
				'valueview-expert-globecoordinateinput-precisionlabel-thousandth-of-arcsecond': 1 / 3600000,
				'valueview-expert-globecoordinateinput-precisionlabel-tenthousandth-of-arcsecond': 1 / 36000000
			};

			for ( var labelMsg in presets ) {
				if ( Math.abs( precision - presets[labelMsg] ) < 0.000000000001 ) {
					return this._messageProvider.getMessage( labelMsg );
				}
			}

			return '±' + this._roundPrecision( precision ) + '°';
		},

		/**
		 * Returns the original precision level for an unrounded precision.
		 *
		 * @ignore
		 * @private
		 *
		 * @param {number} precision
		 * @return {number|null}
		 */
		_getPrecisionSetting: function( precision ) {
			var self = this,
				actualPrecision = null,
				roundedPrecision = this._roundPrecision( precision );

			$.each( PRECISIONS, function( i, precision ) {
				if ( self._roundPrecision( precision ) === roundedPrecision ) {
					actualPrecision = roundedPrecision;
					return false;
				}
			} );

			return actualPrecision;
		},

		/**
		 * @ignore
		 * @private
		 *
		 * @return {Object[]}
		 */
		_getPrecisionValues: function() {
			var self = this,
				precisionValues = [];
			$.each( PRECISIONS, function( i, precision ) {
				precisionValues.unshift( {
					value: self._roundPrecision( precision ),
					label: self._getPrecisionLabel( precision )
				} );
			} );
			return precisionValues;
		}
	} );

	return vv.experts.GlobeCoordinateInput;

}( jQuery, jQuery.valueview ) );
