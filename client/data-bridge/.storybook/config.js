import { configure } from '@storybook/vue';
import { addDecorator } from '@storybook/vue';
import { withInfo } from 'storybook-addon-vue-info';
import { withA11y } from '@storybook/addon-a11y';

addDecorator( withInfo );
addDecorator( withA11y );

const req = require.context( '../stories', true, /\.js$/ );
function loadStories() {
	req.keys().forEach( ( filename ) => req( filename ) );
}

configure( loadStories, module );
