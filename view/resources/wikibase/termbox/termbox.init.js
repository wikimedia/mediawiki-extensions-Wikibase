const termboxInit = require( 'wikibase.termbox.init' ).default;
const RepoApiWritingEntityRepository = require( './RepoApiWritingEntityRepository.js' );
const EntityLoadedHookEntityRepository = require( './EntityLoadedHookEntityRepository.js' );
const mountTermbox = require( './mountTermbox.js' );
const { tags } = require( './config.json' );
const repoConfig = mw.config.get( 'wbRepo' );
const repoApiUrl = repoConfig.url + repoConfig.scriptPath + '/api.php';
const mwApi = wikibase.api.getLocationAgnosticMwApi( repoApiUrl );
const isEditable = mw.config.get( 'wbIsEditView' ) && mw.config.get( 'wgRelevantPageIsProbablyEditable' );

termboxInit(
	{
		readingEntityRepository: new EntityLoadedHookEntityRepository(
			mw.hook( 'wikibase.entityPage.entityLoaded' )
		),
		writingEntityRepository: new RepoApiWritingEntityRepository(
			new wikibase.api.RepoApi(
				mwApi,
				mw.config.get( 'wgUserLanguage' ),
				tags
			)
		)
	},
	isEditable
).then( mountTermbox );
