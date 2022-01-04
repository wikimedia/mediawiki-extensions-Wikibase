import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import './storybook-global.scss';
import Vue from 'vue';
import messages from '@/mock-data/messages';

Vue.config.warnHandler = ( err, _vm, trace ) => {
	throw new Error( err + trace );
};

export const decorators = [
	() => ( {
		template: '<div class="wb-db-app"><story/></div>',
	} ),
];

export const parameters = {
	docs: {
		inlineStories: true,
	},
};

extendVueEnvironment(
	Vue,
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
		getText: ( messageKey ) => {
			return messages[ messageKey ] || `⧼${messageKey}⧽`;
		},
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
