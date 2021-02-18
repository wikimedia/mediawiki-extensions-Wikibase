import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import MessageKeys from '@/definitions/MessageKeys';

/**
 * Usage (assuming this has been registered as a Vue plugin):
 *
 * `this.$messages.get( this.$messages.KEYS.BRIDGE_DIALOG_TITLE )`
 * `this.$messages.getText( this.$messages.KEYS.REFERENCES_HEADING )`
 */
export default class Messages {

	public readonly KEYS = MessageKeys;

	private readonly messagesRepository: MessagesRepository;

	public constructor( messagesRepository: MessagesRepository ) {
		this.messagesRepository = messagesRepository;
	}

	public get( messageKey: string, ...params: readonly ( string|HTMLElement )[] ): string {
		return this.messagesRepository.get( messageKey, ...params );
	}

	public getText( messageKey: string, ...params: readonly string[] ): string {
		return this.messagesRepository.getText( messageKey, ...params );
	}
}
