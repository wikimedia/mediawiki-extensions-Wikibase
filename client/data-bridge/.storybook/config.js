import Vue from 'vue';
import { configure } from '@storybook/vue';
import { addDecorator } from '@storybook/vue';
import { withInfo } from 'storybook-addon-vue-info';
import { withA11y } from '@storybook/addon-a11y';
import inlanguage from '@/presentation/directives/inlanguage';

addDecorator( withInfo );
addDecorator( withA11y );

Vue.directive( 'inlanguage', inlanguage( {
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
} ) );

const req = require.context( '../stories', true, /\.js$/ );
function loadStories() {
	req.keys().forEach( ( filename ) => req( filename ) );
}

configure( loadStories, module );
