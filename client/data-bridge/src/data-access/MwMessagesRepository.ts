import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import { MwMessages } from '@/@types/mediawiki/MwWindow';

export default class MwMessagesRepository implements MessagesRepository {
	private readonly mwMessages: MwMessages;

	public constructor( mwMessages: MwMessages ) {
		this.mwMessages = mwMessages;
	}

	public get( messageKey: string ): string {
		return this.mwMessages( messageKey ).text();
	}
}
