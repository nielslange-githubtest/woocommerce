/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CheckboxControl,
	TextControl,
	TextareaControl,
	Button,
} from '@wordpress/components';
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';

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

	const [ enabled, setEnabled ] = useState( false );
	const [ title, setTitle ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ instructions, setInstructions ] = useState( '' );

	useEffect( () => {
		if ( chequeSettings ) {
			setEnabled( chequeSettings.enabled || false );
			setTitle(
				chequeSettings.settings?.title?.value || 'Cheque payments'
			);
			setDescription( chequeSettings.description || '' );
			setInstructions(
				chequeSettings.settings?.instructions?.value || ''
			);
		}
	}, [ chequeSettings ] );

	const saveSettings = () => {
		updatePaymentGateway( 'cheque', {
			enabled,
			description,
			settings: {
				title,
				instructions,
			},
		} )
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentGateway' );
				createSuccessNotice(
					__( 'Settings updated successfully', 'woocommerce' )
				);
			} )
			.catch( ( error ) => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
			} );
	};

	if ( isLoading ) {
		return <p>Loading settings...</p>;
	}

	return (
		<PaymentSettingsLayout>
			<PaymentSettingsSection
				id={ 'cheque-settings' }
				title={ __( 'Enable and customise', 'woocommerce' ) }
				description={ __(
					'Choose how you want to present cheque payments to your customers during checkout.',
					'woocommerce'
				) }
			>
				<Card className="payment-settings-card__wrapper cheque-settings__wrapper">
					<CardBody className="bacs-settings__body">
						<div className={ 'form-field' }>
							<CheckboxControl
								onChange={ ( value ) => setEnabled( value ) }
								checked={ enabled }
								// @ts-expect-error The label prop can be a string, however, the final consumer of this prop accepts ReactNode.
								label={
									<span>
										{ __(
											'Enable cheque payments',
											'woocommerce'
										) }
									</span>
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<TextControl
								label={ __( 'Title', 'woocommerce' ) }
								help={ __(
									'This controls the title which the user sees during checkout.',
									'woocommerce'
								) }
								value={ title }
								onChange={ ( value ) => setTitle( value ) }
							/>
						</div>

						<div className={ 'form-field' }>
							<TextareaControl
								label={ __( 'Description', 'woocommerce' ) }
								help={ __(
									'Payment method description that the customer will see on your checkout.',
									'woocommerce'
								) }
								value={ description }
								onChange={ ( value ) =>
									setDescription( value )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<TextareaControl
								label={ __( 'Instructions', 'woocommerce' ) }
								help={ __(
									'Instructions that will be added to the thank you page and emails.',
									'woocommerce'
								) }
								value={ instructions }
								onChange={ ( value ) =>
									setInstructions( value )
								}
							/>
						</div>
					</CardBody>
				</Card>
			</PaymentSettingsSection>
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
