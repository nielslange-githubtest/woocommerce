/**
 * External dependencies
 */
import { Card, CardBody, Button } from '@wordpress/components';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentGatewaysStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import '../settings-payments-body.scss';
import { GatewaySettingsForm } from '~/settings-payments/components/gateway-settings-form';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';

/**
 * Component for managing Cheque payment gateway settings.
 */
export const SettingsPaymentsCheque = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );
	const { chequeSettings, isLoading } = useSelect(
		( select ) => ( {
			chequeSettings:
				select( paymentGatewaysStore ).getPaymentGateway( 'cheque' ),
			isLoading: ! select( paymentGatewaysStore ).hasFinishedResolution(
				'getPaymentGateway',
				[ 'cheque' ]
			),
		} ),
		[]
	);

	const { updatePaymentGateway, invalidateResolutionForStoreSelector } =
		useDispatch( paymentGatewaysStore );

	const [ updatedValues, setUpdatedValues ] =
		useState< Record< string, string | boolean | string[] > >();

	const saveSettings = () => {
		if ( ! chequeSettings ) {
			return;
		}

		const settings: Record< string, string > = {
			title: String(
				updatedValues?.title ?? chequeSettings.settings.title.value
			),
			instructions: String(
				updatedValues?.instructions ??
					chequeSettings.settings.instructions.value
			),
		};

		updatePaymentGateway( 'cheque', {
			enabled:
				typeof updatedValues?.enabled === 'boolean'
					? updatedValues.enabled
					: chequeSettings.enabled,
			description: String(
				updatedValues?.description ?? chequeSettings.description
			),
			settings,
		} )
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentGateway' );
				createSuccessNotice(
					__( 'Settings updated successfully', 'woocommerce' )
				);
			} )
			.catch( () => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
			} );
	};

	if ( isLoading || ! chequeSettings ) {
		return <p>Loading settings...</p>;
	}

	return (
		<PaymentSettingsLayout>
			<GatewaySettingsForm
				onChange={ ( values ) => {
					setUpdatedValues( values );
				} }
				sections={ [
					{
						id: 'general',
						title: __( 'General', 'woocommerce' ),
						description: __(
							'Choose how you want to present cheque payments to your customers during checkout.',
							'woocommerce'
						),
						fields: [
							{
								id: 'enabled',
								label: __(
									'Enable check payments',
									'woocommerce'
								),
								type: 'checkbox',
								description: __(
									'Enable or disable this payment method.',
									'woocommerce'
								),
								value: chequeSettings.enabled,
							},
							{
								id: 'title',
								label: __( 'Title', 'woocommerce' ),
								type: 'text',
								description: __(
									'This controls the title which the user sees during checkout.',
									'woocommerce'
								),
								placeholder: __(
									'Check payments',
									'woocommerce'
								),
								value: chequeSettings.settings.title.value,
							},
							{
								id: 'description',
								label: __( 'Description', 'woocommerce' ),
								type: 'textarea',
								description: __(
									'Payment method description that the customer will see on your checkout.',
									'woocommerce'
								),
								value: chequeSettings.description,
							},
							{
								id: 'instructions',
								label: __( 'Instructions', 'woocommerce' ),
								type: 'textarea',
								description: __(
									'Instructions that will be added to the thank you page and emails.',
									'woocommerce'
								),
								value: chequeSettings.settings.instructions
									.value,
							},
						],
					},
				] }
			/>
			<Card className={ 'payment-settings-card__wrapper ' }>
				<CardBody className={ 'form__actions' }>
					<Button variant={ 'primary' } onClick={ saveSettings }>
						{ __( 'Save changes', 'woocommerce' ) }
					</Button>
				</CardBody>
			</Card>
		</PaymentSettingsLayout>
	);
};

export default SettingsPaymentsCheque;
