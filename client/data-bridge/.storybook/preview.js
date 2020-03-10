import { addDecorator, addParameters } from '@storybook/vue';
import { withA11y } from '@storybook/addon-a11y';
import { withKnobs } from '@storybook/addon-knobs';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import './storybook-global.scss';
import Vue from 'vue';

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
		get: ( key ) => {
			switch ( key ) {
				case 'wikibase-client-data-bridge-license-body':
					// eslint-disable-next-line max-len
					return '<p>Changing this value will also change it on repo and possibly on wikis in other languages.</p>\n<p>By clicking "save changes", you agree to the <a href="https://foundation.wikimedia.org/wiki/Terms_of_Use">terms of use</a>, and you irrevocably agree to release your contribution under <a href="https://creativecommons.org/publicdomain/zero/1.0/">Creative Commons CC0</a>.</p>';
				default:
					return `⧼${key}⧽`;
			}
		},
	},
	{
		usePublish: true,
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
