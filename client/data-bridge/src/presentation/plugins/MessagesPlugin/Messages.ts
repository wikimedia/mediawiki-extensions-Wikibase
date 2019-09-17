import MessagesRepository from '@/definitions/data-access/MessagesRepository';
import MessageKeys from '@/definitions/MessageKeys';

/**
 * Usage (assuming this has been registered as a Vue plugin):
 *
 * `this.$messages.get( this.$messages.KEYS.BRIDGE_DIALOG_TITLE )`
 */
export default class Messages {

	public readonly KEYS = MessageKeys;

	private readonly messagesRepository: MessagesRepository;

	public constructor( messagesRepository: MessagesRepository ) {
		this.messagesRepository = messagesRepository;
	}

	public get( messageKey: string ): string {
		return this.messagesRepository.get( messageKey );
	}
}
