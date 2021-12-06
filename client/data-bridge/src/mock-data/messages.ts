/* eslint-disable max-len */
import MessageKeys from '@/definitions/MessageKeys';
import * as clientMessages from '../../../i18n/en.json';

const messages = {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	...clientMessages.default,
	[ MessageKeys.BRIDGE_DIALOG_TITLE ]: 'bridge dev',
	[ MessageKeys.SAVE_CHANGES ]: 'save changes',
	[ MessageKeys.CANCEL ]: 'cancel',
	[ MessageKeys.BAILOUT_SUGGESTION_GO_TO_REPO ]: 'Edit the value on repo. Click the button below to edit the value directly (link opens in a new tab).',
	[ MessageKeys.BAILOUT_SUGGESTION_GO_TO_REPO_BUTTON ]: 'Edit the value on the repo',
	[ MessageKeys.BAILOUT_SUGGESTION_EDIT_ARTICLE ]: 'Depending on the template used, it might be possible to overwrite the value locally using <a href="https://example.com">the article editor</a>. If at all possible, we recommend that you instead add the value to repo via the button above.',
	[ MessageKeys.UNSUPPORTED_DATATYPE_ERROR_BODY ]: '$1 is of the datatype $2 on repo. Editing this datatype is currently not supported.',
	[ MessageKeys.PERMISSIONS_HEADING ]: 'You do not have permission to edit this value, for the following reason:',
	[ MessageKeys.PERMISSIONS_CASCADE_PROTECTED_HEADING ]: '<strong>This value is currently cascade protected on repo and can be edited only by <a href="https://example.com">administrators</a>.</strong>',
	[ MessageKeys.PERMISSIONS_CASCADE_PROTECTED_BODY ]: '<p><strong>Why is this value protected?</strong></p>\n<p>This value is <a href="https://example.com">transcluded</a> in the following pages, which are protected with the "<a href="https://example.com">cascading</a>" option:</p>\n$2',
	[ MessageKeys.LICENSE_BODY ]: '<p>Changing this value will also change it on repo and possibly on wikis in other languages.</p>\n<p>By clicking "save changes", you agree to the <a href="https://foundation.wikimedia.org/wiki/Terms_of_Use">terms of use</a>, and you irrevocably agree to release your contribution under <a href="https://creativecommons.org/publicdomain/zero/1.0/">Creative Commons CC0</a>.</p>',
	[ MessageKeys.REFERENCE_NOTE ]: 'Editing references will be possible on Wikidata after saving',
} as { [ key in MessageKeys ]: string };
/* eslint-enable max-len */

export default messages;
