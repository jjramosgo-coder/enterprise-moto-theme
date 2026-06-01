<?php
/**
 * Block: enterprise/tip-box
 * Bloques de consejo, nota, atención o peligro para posts.
 */
defined( 'ABSPATH' ) || exit;

function enterprise_render_tip_box_block( $attributes, $content ) {
    $type    = isset( $attributes['tipType'] )  ? $attributes['tipType']  : 'consejo';
    $text    = isset( $attributes['tipText'] )  ? $attributes['tipText']  : '';
    $icon    = isset( $attributes['tipIcon'] )  ? $attributes['tipIcon']  : 'auto';
    $title   = isset( $attributes['tipTitle'] ) ? $attributes['tipTitle'] : '';

    $valid_types = array( 'consejo', 'nota', 'atencion', 'peligro' );
    if ( ! in_array( $type, $valid_types, true ) ) $type = 'consejo';

    if ( empty( trim( $text ) ) ) return '';

    // Icono automático por tipo
    $auto_icons = array(
        'consejo'  => 'ti-bulb',
        'nota'     => 'ti-info-circle',
        'atencion' => 'ti-alert-triangle',
        'peligro'  => 'ti-skull',
    );

    $icon_class = ( $icon === 'auto' || empty( $icon ) )
        ? $auto_icons[ $type ]
        : sanitize_html_class( $icon );

    // Etiquetas por defecto
    $default_titles = array(
        'consejo'  => __( 'Consejo',  'enterprise-moto' ),
        'nota'     => __( 'Nota',     'enterprise-moto' ),
        'atencion' => __( 'Atención', 'enterprise-moto' ),
        'peligro'  => __( 'Peligro',  'enterprise-moto' ),
    );

    $display_title = ! empty( $title ) ? $title : $default_titles[ $type ];

    $out  = '<div class="ent-tip ent-tip--' . esc_attr( $type ) . '" role="note">';
    $out .= '<div class="ent-tip__label">';
    $out .= '<i class="ti ' . esc_attr( $icon_class ) . ' ent-tip__icon" aria-hidden="true"></i>';
    $out .= '<span>' . esc_html( $display_title ) . '</span>';
    $out .= '</div>';
    $out .= '<div class="ent-tip__body">' . wp_kses_post( wpautop( $text ) ) . '</div>';
    $out .= '</div>';

    return $out;
}
