<?php


class WPS_Routing {

    /**
     * The array of templates that this plugin tracks.
     */
    protected $config;


    public function initRouting($routes){

        add_action('init', function () use($routes) {

            foreach ($routes as $name=>$url){

                $regex = str_replace('.', '\.', $url);
                add_rewrite_rule('^'.$regex.'$', 'index.php?wps_custom_route='.$name, 'top');
            }
        });

        add_filter('query_vars', function ($vars) {
            $vars[] = 'wps_custom_route';
            return $vars;
        });

        add_action('template_redirect', function () use($routes) {

            $requested_route = get_query_var('wps_custom_route');

            if (!$requested_route) return;

            if (array_key_exists($requested_route, $routes)) {

                do_action('custom_route_'.$requested_route);
                exit;
            }
        });
    }


    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    public function __construct() {

        global $_config;

        $this->config = $_config;

        $routes = $this->config->get('custom_routes', []);

        if( !empty($routes) )
            $this->initRouting($routes);
    }
}