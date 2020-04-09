import { addDecorator, addParameters } from '@storybook/vue';
import { withA11y } from '@storybook/addon-a11y';
import { withKnobs } from '@storybook/addon-knobs';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import './storybook-global.scss';
import Vue from 'vue';
import messages from '@/mock-data/messages';

Vue.config.warnHandler = ( err, _vm, trace ) => {
	throw new Error( err + trace );
};

addDecorator( withA11y );

addDecorator( withKnobs );

addDecorator( () => ( {
	template: '<div class="wb-db-app"><story/></div>',
} ) );

addParameters( {
	docs: {
		inlineStories: true,
	},
	knobs: {
		disableDebounce: true,
	},
} );

extendVueEnvironment(
	{
		resolve( languageCode ) {
			switch ( languageCode ) {
				case 'en':
					return { code: 'en', directionality: 'ltr' };
				case 'he':
					return { code: 'he', directionality: 'rtl' };
				default:
					return { code: languageCode, directionality: 'auto' };
			}
		},
	},
	{
		get: ( messageKey ) => {
			return messages[ messageKey ] || `⧼${messageKey}⧽`;
		},
	},
	{
		usePublish: true,
		issueReportingLink: 'https://example.com/issue/new?title=Bridge+error&description=<body>&tags=Wikidata-Bridge',
	},
	{
		getPageUrl( title, params ) {
			let url = `http://repo/${title}`;
			if ( params ) {
				url += '?' + new URLSearchParams( params ).toString();
			}
			return url;
		},
	},
	{
		getPageUrl( title, params ) {
			let url = `https://client.wiki.example/wiki/${title.replace( / /g, '_' )}`;
			if ( params ) {
				url += '?' + new URLSearchParams( params ).toString();
			}
			return url;
		},
	},
);
