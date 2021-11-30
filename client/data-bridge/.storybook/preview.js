import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import './storybook-global.scss';
import { app } from '@storybook/vue3';
import messages from '@/mock-data/messages';

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
	app,
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
