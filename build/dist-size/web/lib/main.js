/* global Plotly */

const FIELDS = {
	DATE: 'date',
	SIZE: 'size',
	SHA: 'sha',
	SUBJECT: 'subject',
};
const CUSTOM_FIELDS = {
	PREVIOUS_SIZE: 'previousSize',
	SIZE_DELTA_PERCENT: 'sizeDeltaPercent',
};

const ANNOTATION_THRESHOLD_PERCENT = 8;

function unpack( rows, key ) {
	return rows.map( ( row ) => row[ key ] );
}

function sortByDate( first, second ) {
	const a = new Date( first[ FIELDS.DATE ] ),
		b = new Date( second[ FIELDS.DATE ] );
	return a > b ? 1 : a < b ? -1 : 0;
}

function augmentFileHistoryBySizeDelta( fileHistory ) {
	const newHistory = JSON.parse( JSON.stringify( fileHistory ) );
	newHistory.sort( sortByDate );

	let previousRevision = null;
	newHistory.forEach( ( revision ) => {
		const previousSize = previousRevision ? previousRevision[ FIELDS.SIZE ] : null;
		const deltaPercent = Math.round(
			( revision[ FIELDS.SIZE ] - previousSize ) / previousSize * 100,
		);
		revision[ CUSTOM_FIELDS.PREVIOUS_SIZE ] = previousSize;
		revision[ CUSTOM_FIELDS.SIZE_DELTA_PERCENT ] = deltaPercent;
		previousRevision = revision;
	} );

	return newHistory;
}

function showRevisionInformation( revision ) {
	window.open(
		'https://github.com/wikimedia/mediawiki-extensions-Wikibase/commit/' + revision[ FIELDS.SHA ],
		'_blank',
	);
}

/**
 * Build an annotation if the size delta exceeds the configured threshold
 * Requires the revision to be augmented by `deltaPercent` and `previousSize` properties
 *
 * @param {Object} revision
 * @return {?Object}
 */
function buildSizeDeltaAnnotation( revision ) {
	if ( revision[ CUSTOM_FIELDS.PREVIOUS_SIZE ] === null || revision[ FIELDS.SIZE ] === null ) {
		// inception & removal may be a notable but not with respect to size
		return null;
	}
	const sizeDeltaPercent = revision[ CUSTOM_FIELDS.SIZE_DELTA_PERCENT ];
	if ( Math.abs( sizeDeltaPercent ) <= ANNOTATION_THRESHOLD_PERCENT ) {
		return null;
	}
	return {
		x: revision[ FIELDS.DATE ],
		y: revision[ FIELDS.SIZE ],
		xref: 'x',
		yref: 'y',
		text: `∆${sizeDeltaPercent}%`,
		hovertext: `<sub>${revision[ FIELDS.SHA ]}</sub><br>${revision[ FIELDS.SUBJECT ]}`,
		font: {
			color: sizeDeltaPercent > 0 ? 'red' : 'green',
			size: Math.max( 5, // minimum size
				Math.min( 18, // maximum size
					Math.round(
						Math.log( Math.abs( sizeDeltaPercent ) ) * 4, // derive size from delta
					) ) ),
		},
		showarrow: true,
		arrowhead: 7,
		ax: 0,
		ay: -40,
		captureevents: true,
		customdata: revision,
	};
}

function buildScatterTrace( fileName, fileHistory ) {
	return {
		type: 'scatter',
		mode: 'lines+markers',
		name: fileName,
		x: unpack( fileHistory, FIELDS.DATE ),
		y: unpack( fileHistory, FIELDS.SIZE ),
		customdata: fileHistory,
		hovertemplate:
			`%{y} = ∆%{customdata.${CUSTOM_FIELDS.SIZE_DELTA_PERCENT}}%<br>` +
			`<sup>%{customdata.${FIELDS.SHA}}</sup><br>` +
			`%{customdata.${FIELDS.SUBJECT}}`,
		marker: { size: 4 },
	};
}

function plot( el, historyData ) {
	const traces = [];
	const annotations = [];

	for ( const fileName in historyData ) {
		const fileHistory = augmentFileHistoryBySizeDelta( historyData[ fileName ] );

		traces.push( buildScatterTrace( fileName, fileHistory ) );

		fileHistory.forEach( ( revision ) => {
			const annotation = buildSizeDeltaAnnotation( revision );
			if ( annotation ) {
				annotations.push( annotation );
			}
		} );
	}

	Plotly.newPlot(
		el,
		traces,
		{
			title: 'File size over time',
			yaxis: {
				title: 'file size (bytes)',
				fixedrange: true,
			},
			xaxis: {
				title: 'date (ctrl+click to go to revision)',
			},
			annotations,
		},
		{
			scrollZoom: true,
		},
	);

	el.on( 'plotly_click', function ( data ) {
		if ( !data.event.ctrlKey ) {
			return;
		}

		showRevisionInformation( data.points[ 0 ].customdata );
	} );
	el.on( 'plotly_clickannotation', function ( data ) {
		showRevisionInformation( data.annotation.customdata );
	} );
}

function DistHist( el, historyDataFile ) {
	Plotly.d3.json( historyDataFile, ( error, historyData ) => {
		if ( error !== null ) {
			el.innerText = 'Data not found. Make sure to generate data.json by running the analyze script';
			return;
		}

		plot( el, historyData );
	} );
}

export {
	DistHist,
};
