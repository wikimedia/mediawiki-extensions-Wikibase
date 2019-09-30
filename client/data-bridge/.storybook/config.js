import { configure } from '@storybook/vue';
import { addDecorator } from '@storybook/vue';
import { withInfo } from 'storybook-addon-vue-info';
import { withA11y } from '@storybook/addon-a11y';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';

addDecorator( withInfo );
addDecorator( withA11y );

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
		get( messageKey ) {
			return `<${messageKey}>`;
		},
	},
	{
		usePublish: true,
	},
);

const req = require.context( '../stories', true, /\.js$/ );
function loadStories() {
	req.keys().forEach( ( filename ) => req( filename ) );
}

configure( loadStories, module );
