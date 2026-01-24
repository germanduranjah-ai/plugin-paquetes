<?php
if ( ! defined('ABSPATH') ) exit;

final class UDPAQUETES_CPT {

    const POST_TYPE = 'ud_paquete';
    const POST_TYPE_RESERVA = 'ud_reserva';
    const TAX_DESTINO = 'ud_destino';

    public static function register() {

        // Paquetes CPT
        $labels_paquetes = [
            'name'               => 'Paquetes',
            'singular_name'      => 'Paquete',
            'add_new'            => 'Añadir nuevo',
            'add_new_item'       => 'Añadir nuevo paquete',
            'edit_item'          => 'Editar paquete',
            'new_item'           => 'Nuevo paquete',
            'view_item'          => 'Ver paquete',
            'search_items'       => 'Buscar paquetes',
            'not_found'          => 'No se encontraron paquetes',
            'not_found_in_trash' => 'No hay paquetes en la papelera',
            'menu_name'          => 'Paquetes',
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels_paquetes,
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'paquetes'],
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-palmtree',
        ]);

        // Reservas CPT
        $labels_reservas = [
            'name'               => 'Reservas',
            'singular_name'      => 'Reserva',
            'add_new'            => 'Añadir nueva',
            'add_new_item'       => 'Añadir nueva reserva',
            'edit_item'          => 'Editar reserva',
            'new_item'           => 'Nueva reserva',
            'view_item'          => 'Ver reserva',
            'search_items'       => 'Buscar reservas',
            'not_found'          => 'No se encontraron reservas',
            'not_found_in_trash' => 'No hay reservas en la papelera',
            'menu_name'          => 'Reservas',
        ];

        register_post_type(self::POST_TYPE_RESERVA, [
            'labels' => $labels_reservas,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::POST_TYPE,
            'supports' => ['title'],
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap' => true,
        ]);

        register_taxonomy(self::TAX_DESTINO, [self::POST_TYPE], [
            'label' => 'Destinos',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'destino'],
        ]);
    }
}

