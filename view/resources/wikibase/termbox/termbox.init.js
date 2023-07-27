const termboxInit = require( 'wikibase.termbox.init' ).default;
const RepoApiWritingEntityRepository = require( './RepoApiWritingEntityRepository.js' );
const { tags } = require( './config.json' );
const repoConfig = mw.config.get( 'wbRepo' );
const repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';
const mwApi = wikibase.api.getLocationAgnosticMwApi( repoApiUrl );

termboxInit( {
	readingEntityRepository: {
		getFingerprintableEntity() {
			return new Promise( ( resolve ) => {
				mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( entity ) {
					resolve( JSON.parse( JSON.stringify( entity ) ) );
				} );
			} );
		}
	},
	writingEntityRepository: new RepoApiWritingEntityRepository(
		new wikibase.api.RepoApi(
			mwApi,
			mw.config.get( 'wgUserLanguage' ),
			tags
		)
	)
} );
