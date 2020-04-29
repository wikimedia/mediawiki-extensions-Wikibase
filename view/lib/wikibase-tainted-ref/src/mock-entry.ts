import Vue from 'vue';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';
import { launch } from '@/main';
import CSRHookHandler from '@/CSRHookHandler';

( () => {
	function trackMock( topic: string, data?: object ): void {
		// eslint-disable-next-line no-console
		console.debug( `tracked: ${topic}`, data );
	}
	function messageToTextMock( key: string ): string {
		return `(${key})`;
	}

	Vue.use( Track, { trackingFunction: trackMock } );
	Vue.use( Message, { messageToTextFunction: messageToTextMock } );
} )();
function trackMock( topic: string, data?: object|number|string ): void {
	// eslint-disable-next-line no-console
	console.debug( `tracked: ${topic}`, data );
}
launch(
	new CSRHookHandler(),
	'https://www.wikidata.org/wiki/Special:MyLanguage/Help:Sources',
	trackMock,
);
