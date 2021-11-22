import { launch } from '@/main';
import CSRHookHandler from '@/CSRHookHandler';

function trackMock( topic: string, data?: object|number|string ): void {
	// eslint-disable-next-line no-console
	console.debug( `tracked: ${topic}`, data );
}
function messageToTextMock( key: string ): string {
	return `(${key})`;
}
launch(
	new CSRHookHandler(),
	'https://www.wikidata.org/wiki/Special:MyLanguage/Help:Sources',
	messageToTextMock,
	trackMock,
);
