<?php
/**
 * Registra todos los actions y filters para el plugin
 *
 * @package    Plugin_Paquetes
 * @subpackage Plugin_Paquetes/includes
 */

class Plugin_Paquetes_Loader {

    /**
     * Array de actions registrados con WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions
     */
    protected $actions;

    /**
     * Array de filters registrados con WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters
     */
    protected $filters;

    /**
     * Array de shortcodes registrados con WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $shortcodes
     */
    protected $shortcodes;

    /**
     * Inicializa las colecciones de actions, filters y shortcodes
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }

    /**
     * Agrega un nuevo action
     *
     * @since    1.0.0
     * @param    string               $hook
     * @param    object               $component
     * @param    string               $callback
     * @param    int                  $priority
     * @param    int                  $accepted_args
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Agrega un nuevo filter
     *
     * @since    1.0.0
     * @param    string               $hook
     * @param    object               $component
     * @param    string               $callback
     * @param    int                  $priority
     * @param    int                  $accepted_args
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Agrega un nuevo shortcode
     *
     * @since    1.0.0
     * @param    string               $tag
     * @param    object               $component
     * @param    string               $callback
     */
    public function add_shortcode( $tag, $component, $callback ) {
        $this->shortcodes = $this->add( $this->shortcodes, $tag, $component, $callback );
    }

    /**
     * Utilidad para agregar hooks al array de colecciones
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks
     * @param    string               $hook
     * @param    object               $component
     * @param    string               $callback
     * @param    int                  $priority
     * @param    int                  $accepted_args
     * @return   array
     */
    private function add( $hooks, $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Registra los filters, actions y shortcodes con WordPress
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->shortcodes as $hook ) {
            add_shortcode( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }
    }
}
