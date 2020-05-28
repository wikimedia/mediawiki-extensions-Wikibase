export default interface MessagesRepository {
	get( messageKey: string, ...params: readonly ( string| HTMLElement )[] ): string;
}
