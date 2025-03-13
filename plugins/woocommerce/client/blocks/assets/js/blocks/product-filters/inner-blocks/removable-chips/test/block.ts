/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { fireEvent, screen, act } from '@testing-library/react';
import { BlockAttributes, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	initializeEditor,
	selectBlock,
} from '../../../../../../../tests/integration/helpers/integration-test-editor';

/**
 * Block dependencies
 */
import '../../active-filters';
import '../';

async function setup( attributes: BlockAttributes ) {
	return initializeEditor( [
		{
			name: 'woocommerce/product-filter-active',
			innerBlocks: [
				createBlock(
					'woocommerce/product-filter-removable-chips',
					attributes
				),
			],
		},
	] );
}

describe( 'Removable Chips block', () => {
	describe( 'Layout settings', () => {
		test( 'can change orientation between horizontal and vertical', async () => {
			await setup( {} );

			const chipsBlock = screen.getByLabelText( /Block: Chips/i );

			// Verify initial horizontal orientation
			expect( chipsBlock ).toHaveClass( 'is-horizontal' );
			expect( chipsBlock ).not.toHaveClass( 'is-vertical' );

			await selectBlock( /Block: Chips/i );

			// Click vertical orientation button
			await act( async () => {
				fireEvent.click(
					screen.getByRole( 'button', { name: /Vertical/i } )
				);
			} );

			expect( chipsBlock ).toHaveClass( 'is-vertical' );
			expect( chipsBlock ).not.toHaveClass( 'is-horizontal' );
		} );

		test( 'can change justification settings', async () => {
			await setup( {} );

			const chipsBlock = screen.getByLabelText( /Block: Chips/i );

			await selectBlock( /Block: Chips/i );

			// Test space between justification
			fireEvent.click( screen.getByLabelText( /Space between items/i ) );
			expect( chipsBlock ).toHaveClass(
				'is-content-justification-space-between'
			);

			// Test right justification
			fireEvent.click( screen.getByLabelText( /Justify items right/i ) );
			expect( chipsBlock ).toHaveClass(
				'is-content-justification-right'
			);

			// Test center justification
			fireEvent.click( screen.getByLabelText( /Justify items center/i ) );
			expect( chipsBlock ).toHaveClass(
				'is-content-justification-center'
			);

			// Test left justification
			fireEvent.click( screen.getByLabelText( /Justify items left/i ) );
			expect( chipsBlock ).toHaveClass( 'is-content-justification-left' );
		} );
	} );

	describe.skip( 'Color settings', () => {
		test( 'can change chip colors', async () => {
			await setup( {} );

			await selectBlock( /Block: Chips/i );

			const colorSettings = screen.getByRole( 'button', {
				name: /Color/i,
			} );

			if ( colorSettings.getAttribute( 'aria-expanded' ) !== 'true' ) {
				fireEvent.click( colorSettings );
			}

			const chipsBlock = screen.getByLabelText( /Block: Chips/i );

			// Test text color change
			const textColorButton = screen.getByLabelText( /Chip Text/i );
			fireEvent.click( textColorButton );
			expect( chipsBlock ).toHaveStyle( {
				'--wp--custom--chip-text': expect.any( String ),
			} );

			// Test border color change
			const borderColorButton = screen.getByLabelText( /Chip Border/i );
			fireEvent.click( borderColorButton );
			expect( chipsBlock ).toHaveStyle( {
				'--wp--custom--chip-border': expect.any( String ),
			} );

			// Test background color change
			const backgroundColorButton =
				screen.getByLabelText( /Chip Background/i );
			fireEvent.click( backgroundColorButton );
			expect( chipsBlock ).toHaveStyle( {
				'--wp--custom--chip-background': expect.any( String ),
			} );
		} );
	} );
} );
