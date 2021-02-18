import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import { MwMessages } from '@/@types/mediawiki/MwWindow';

export default class MwMessagesRepository implements MessagesRepository {
	private readonly mwMessages: MwMessages;

	public constructor( mwMessages: MwMessages ) {
		this.mwMessages = mwMessages;
	}

	public get( messageKey: string, ...params: readonly ( string|HTMLElement )[] ): string {
		return this.mwMessages( messageKey, ...params ).parse();
	}
	/**
	 * Messages which don't contain HTML don't need to get parsed.
	 * Parsing a message which contains quotes for example but no HTML is rendered
	 * incorrectly in the UI because the quotes get encoded.
	 */
	public getText( messageKey: string, ...params: readonly string[] ): string {
		return this.mwMessages( messageKey, ...params ).text();
	}
}
