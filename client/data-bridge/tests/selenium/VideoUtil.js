const fs = require( 'fs' ),
	path = require( 'path' );

let ffmpeg, videoPath;

// get current test title and clean it, to use it as file name
function fileName( title ) {
	return encodeURIComponent( title.replace( /\s+/g, '-' ) );
}

// build file path
function filePath( test, screenshotPath, extension ) {
	return path.join( screenshotPath, `${fileName( test.parent )}_${fileName( test.title )}.${extension}` );
}

module.exports = {

	/**
	 * Start a recording of the test with ffmpeg
	 *
	 * @param {Object} test Mocha Test object
	 */
	startVideoRecording( test ) {
		videoPath = filePath( test, browser.config.screenshotPath, 'mp4' );
		const { spawn } = require( 'child_process' );
		ffmpeg = spawn( 'ffmpeg', [
			'-f', 'x11grab', //  grab the X11 display
			'-video_size', '1280x1024', // video size
			'-i', process.env.DISPLAY, // input file url
			'-loglevel', 'error', // log only errors
			'-y', // overwrite output files without asking
			videoPath, // output file
		] );

		const logBuffer = function ( buffer, prefix ) {
			const lines = buffer.toString().trim().split( '\n' );
			lines.forEach( function ( line ) {
				/* eslint-disable-next-line no-console */
				console.log( prefix + line );
			} );
		};

		ffmpeg.stdout.on( 'data', ( data ) => {
			logBuffer( data, 'ffmpeg stdout: ' );
		} );

		ffmpeg.stderr.on( 'data', ( data ) => {
			logBuffer( data, 'ffmpeg stderr: ' );
		} );
	},

	/**
	 * stop ffmpeg if it is running, delete the video if test passed
	 *
	 * @param {Object} test Mocha Test object
	 */
	stopVideoRecording( test ) {
		if ( ffmpeg ) {
			// stop video recording
			ffmpeg.kill( 'SIGTERM' );

			if ( test.passed ) {
				fs.unlinkSync( videoPath );
			} else {
				/* eslint-disable-next-line no-console */
				console.log( 'Video location:', videoPath );
			}
		}
	},
};
