/**
 * Internal dependencies
 */
import { createMessage } from '../message';

describe( 'createMessage', () => {
	const defaultOptions = {
		reportName: 'Test Report',
		username: 'test-user',
		isFailure: true,
		eventName: 'pull_request',
		sha: '1234567890abcdef',
		commitMessage: 'Test commit message',
		prTitle: 'Test PR Title',
		prNumber: '123',
		actor: 'test-actor',
		triggeringActor: 'trigger-actor',
		runId: '456',
		runAttempt: '1',
		serverUrl: 'https://github.com',
		repository: 'test/repo',
		refType: 'branch',
		refName: 'main',
	};

	it( 'should create a message for pull request event', async () => {
		const result = await createMessage( defaultOptions );

		expect( result ).toMatchObject( {
			text: ':x:	_*Test Report*_ failed for pull request *#123*',
			mainMsgBlocks: expect.arrayContaining( [
				{
					type: 'section',
					text: {
						type: 'mrkdwn',
						text: ':x:	_*Test Report*_ failed for pull request *#123*',
					},
				},
				{
					type: 'context',
					elements: expect.arrayContaining( [
						{
							type: 'plain_text',
							text: 'Title: Test PR Title',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Actor: test-actor',
							emoji: false,
						},
						{
							type: 'plain_text',
							text: 'Run: 456/1, triggered by trigger-actor',
							emoji: false,
						},
					] ),
				},
				{
					type: 'actions',
					elements: expect.arrayContaining( [
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'Run',
							},
							url: 'https://github.com/test/repo/actions/runs/456/',
						},
						{
							type: 'button',
							text: {
								type: 'plain_text',
								text: 'PR #123',
							},
							url: 'https://github.com/test/repo/pull/123',
						},
					] ),
				},
			] ),
		} );
	} );

	it( 'should create a message for push event', async () => {
		const pushOptions = {
			...defaultOptions,
			eventName: 'push',
		};

		const result = await createMessage( pushOptions );

		expect( result ).toMatchObject( {
			text: ':x:	_*Test Report*_ failed on branch _*main*_ (push)',
			mainMsgBlocks: expect.arrayContaining( [
				{
					type: 'section',
					text: {
						type: 'mrkdwn',
						text: ':x:	_*Test Report*_ failed on branch _*main*_ (push)',
					},
				},
				{
					type: 'context',
					elements: expect.arrayContaining( [
						{
							type: 'plain_text',
							text: 'Commit: 12345678 Test commit message',
							emoji: false,
						},
					] ),
				},
			] ),
		} );
	} );

	it( 'should truncate long commit messages', async () => {
		const longMessageOptions = {
			...defaultOptions,
			eventName: 'push',
			commitMessage:
				'This is a very long commit message that should be truncated at some point because it is too long',
		};

		const result = await createMessage( longMessageOptions );
		const contextBlock = result.mainMsgBlocks.find(
			( block ) => block.type === 'context'
		);
		const commitElement = contextBlock.elements.find( ( element ) =>
			element.text.startsWith( 'Commit:' )
		);

		expect( commitElement.text ).toMatch(
			/Commit: [a-f0-9]{8} This is a very long commit message that should be trun.../
		);
	} );

	it( 'should create a message for repository_dispatch event', async () => {
		const dispatchOptions = {
			...defaultOptions,
			eventName: 'repository_dispatch',
		};

		const result = await createMessage( dispatchOptions );

		expect( result.text ).toBe(
			':x:	_*Test Report*_ failed for event _*repository_dispatch*_'
		);
	} );

	it( 'should handle missing reportName', async () => {
		const noReportOptions = {
			...defaultOptions,
			reportName: '',
		};

		const result = await createMessage( noReportOptions );

		expect( result.text ).toMatch( /^:x:	Failure for/ );
	} );

	it( 'should include run attempt in URL when requested', async () => {
		const result = await createMessage( defaultOptions );
		const actionsBlock = result.mainMsgBlocks.find(
			( block ) => block.type === 'actions'
		);
		const runButton = actionsBlock.elements.find(
			( element ) => element.text.text === 'Run'
		);

		expect( runButton.url ).not.toContain( 'attempts' );

		const withAttemptUrl = runButton.url.replace( '/', '/attempts/1' );
		expect( withAttemptUrl ).toBe(
			'https://github.com/test/repo/actions/runs/456/attempts/1'
		);
	} );
} );
