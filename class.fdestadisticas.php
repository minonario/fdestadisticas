<?php

class FDEstadisticas {

        private static $initiated = false;
        private static $is_sassy_social = false;
        private static $combos;
        private static $page_shortcode;
        
        const MAINDIR = FDESTADISTICAS__PLUGIN_DIR . '_tablas/TablasSaif';

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_hooks() {
		self::$initiated = true;
                self::$is_sassy_social = self::is_plugin_active( 'sassy-social-share/sassy-social-share.php' );

                add_rewrite_tag('%pais%', '([^&]+)');
                add_rewrite_tag('%formato%', '([^&]+)');
                add_rewrite_tag('%tipo%', '([^&]+)');
                add_rewrite_tag('%tabla%', '([^&]+)');
                
                self::$page_shortcode = get_option('ds8_tabla_page');
                $front_page = get_option('page_on_front');
                
                if(self::$page_shortcode!=$front_page){
                    add_rewrite_rule( "([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)/tabla/([a-z0-9-]+)[/]?$", 'index.php?&pagename=$matches[1]&pais=$matches[2]&formato=$matches[3]&tipo=$matches[4]&tabla=$matches[5]', 'top' );
                }else{
                    add_rewrite_rule( '([a-z0-9-]+)/([a-z0-9-]+)/([a-z0-9-]+)/tabla/([a-z0-9-]+)[/]?$', 'index.php?&page_id='.self::$page_shortcode.'&pais=$matches[1]&formato=$matches[2]&tipo=$matches[3]&tabla=$matches[4]', 'top' );
                }
                
                if( !get_option('plugin_permalinks_flushed') ) {
                    flush_rewrite_rules(false);
                    update_option('plugin_permalinks_flushed', 1);
                }
                
                //add_action('init', array('FDEstadisticas', 'custom_rewrite_rule'), 10, 0);
                add_filter('the_content', array('FDEstadisticas', 'fd_remove_shortcode_from_index'));
                add_action('wp_enqueue_scripts', array('FDEstadisticas', 'fd_tables_javascript'), 10);
                add_action('wp_ajax_fdtable_action', array('FDEstadisticas', 'ajax_render_init_table'));
                add_action('wp_ajax_nopriv_fdtable_action', array('FDEstadisticas', 'ajax_render_init_table'));
                //add_filter('template_include', array('FDEstadisticas', 'ds8_template' ) );
                //add_action( 'parse_request', array('FDEstadisticas', 'change_post_per_page_wpent' ) );
                add_action('template_redirect', array('FDEstadisticas', 'ds8_redirect') ); 
                //add_filter('query_vars', array('FDEstadisticas', 'ds8_register_query_var') );
                add_filter('redirect_canonical', array('FDEstadisticas', 'canonical'), 10, 2);
                add_shortcode( 'fdtable', array('FDEstadisticas', 'fdtable_shortcode_fn') );
	}
        
        public static function fd_remove_shortcode_from_index( $content ) {
            if ( get_the_ID() != self::$page_shortcode) {
              $content = preg_replace('/\[fdtable(.+?)?\](?:(.+?)?\[\/fdtable\])?/i', '', $content);
            }
            return $content;
        }
        
        public static function canonical($redirect_url, $requested_url) {
          return $requested_url;
        }
        
        public static function ds8_register_query_var($vars){
            $vars[] = 'pais';
            return $vars;
        }
        
        public static function ds8_redirect(){
            $id = get_the_ID();
            $pais = get_query_var( 'pais' );
            
            //if( get_query_var( 'pais' ) && $is_front) {
            if( self::$page_shortcode == $id && get_query_var( 'pais' ) ){
                $pais = get_query_var( 'pais' );
                if($pais) {
                    // FEATURE 27-08-2022 Check if exist table given the query vars
                    $dropdowns = self::generate_dropdowns();
                    if ($dropdowns === NULL){
                        global $wp_query;
                        $wp_query->set_404();
                        status_header(404);
                        return;
                    }
                    foreach($dropdowns as $key => $value){
                        $haystack = array_map(function($e) use($key){
                          $pattern = '/data-url="'. get_query_var($key).'"/i';
                          if ( preg_match($pattern, $e, $matches) ){
                            return "true";
                          }else{
                            return "false";
                          }
                        },$value);
                        if (array_search("true", $haystack) === FALSE){
                            global $wp_query;
                            $wp_query->set_404();
                            status_header(404);
                        }else{
                            self::$combos = $dropdowns;
                        }
                    }
                    return;
                }
                else {
                    global $wp_query;
                    $wp_query->set_404();
                    status_header(404);
                }
            }
              status_header(200);
        }

        public static function ds8_template($template) {
            if ( get_query_var( 'pais' ) == false || get_query_var( 'pais' ) == '' ) {
                return $template;
            }
            return $template;
        }
        
        public static function change_post_per_page_wpent( $query ) {
            if (  'venezuela' == $query->query_vars['pais'] ) {
            }
            return $query;
        }
        
        /**
	 * Check if plugin is active
	 *
	 * @since    1.0
	 */
	private static function is_plugin_active( $plugin_file ) {
		return in_array( $plugin_file, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

        public static function fd_tables_javascript(){
          
            $page_shortcode = get_option('ds8_tabla_page');
            if (self::$page_shortcode == get_option('page_on_front')){
              $slug = '';
            }else{
              $slug = basename(get_page_link($page_shortcode)).'/';
            }
            
            if ( get_queried_object_id() == $page_shortcode){
          
                wp_register_script('fdtable', plugin_dir_url( __FILE__ ) . '_inc/fdtable.js', array('jquery'), FDESTADISTICAS_VERSION);
                $localize_script_args = array(
                    'ajaxurl'         => admin_url('admin-ajax.php'),
                    'security'        => wp_create_nonce( 'fd_security_nonce' ),
                    'baseslug'        => $slug, 
                    'is_sassy_active' => self::$is_sassy_social
                );
                wp_localize_script('fdtable', 'fdtable', $localize_script_args );
                wp_enqueue_script('fdtable' );

                wp_enqueue_style('fdtable-css', plugin_dir_url( __FILE__ ) . '_inc/fdtable.css', array(), FDESTADISTICAS_VERSION);
                wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/v/dt/dt-1.12.1/datatables.min.css', array(), FDESTADISTICAS_VERSION);
                wp_enqueue_style('datatables-fixedheader-css', 'https://cdn.datatables.net/fixedheader/3.2.4/css/fixedHeader.dataTables.min.css', array(), FDESTADISTICAS_VERSION);
                wp_enqueue_style('datatables-fixedcolumn-css', 'https://cdn.datatables.net/fixedcolumns/4.1.0/css/fixedColumns.dataTables.min.css', array(), FDESTADISTICAS_VERSION);

                wp_enqueue_script('datatables', 'https://cdn.datatables.net/v/dt/dt-1.12.1/datatables.min.js', array('jquery'), '3.3.5', true);
                wp_enqueue_script('datatables-fixedheader', 'https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js', array('jquery'), '3.3.5', true);
                wp_enqueue_script('datatables-fixedcolumn', 'https://cdn.datatables.net/fixedcolumns/4.1.0/js/dataTables.fixedColumns.min.js', array('jquery'), '3.3.5', true);
                wp_enqueue_script('moment', '"https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"', array('jquery'), '3.3.5', true);
                wp_enqueue_script('datatables-sort', plugin_dir_url( __FILE__ ) . '_inc/sort.js', array('jquery'), '3.3.5', true);
            }
        }

        public static function ajax_render_init_table() {

                if (!check_ajax_referer('fd_security_nonce', 'security')) {
                  wp_send_json_error('Invalid security token sent.');
                  wp_die();
                }

                $dropdowns = array('fdpais' => '', 'fdformato' => '', 'fdtipo' => '');
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                  extract($_POST);
                }else{
                  extract($_GET);
                }
                
                $filters = array('fdpais' => $pais, 'fdformato' => $formato, 'fdtipo' => $tipo, 'fdindicador' => $html);
                $order = array('fdpais', 'fdformato', 'fdtipo', 'fdindicador' );

                array_walk($dropdowns, function(&$value,$key) use ($selection) {
                  ($key == $selection ? $value = $selection : '');
                });

                if ( isset($selection) && $selection != 'fdindicador') {
                  $result = array(); $inside = false; $next = array(); $count = 0;
                  foreach ($dropdowns as $key => $value) {
                      if (empty($value) && !$inside){
                        $next[$key] = $filters[$key];
                      }

                      if ($selection == $key) { $inside = true; $next[$key] = $filters[$key]; }
                      if ($inside) {
                        $tmp = self::get_dropdown($selection, $key, $filters, $next);
                        $result[] = array('id' => $order[$count + 1], 'combo' => $tmp[0], 'selected' => ltrim($tmp[1]));
                        $next[$order[$count + 1]] = $tmp[1];
                      }
                      $count++;
                  }
                }

                if ( isset($selection) && $selection != 'fdindicador') {
                  $main_dir = FDESTADISTICAS__PLUGIN_DIR . '_tablas/TablasSaif/'.$next['fdpais'].'/'.$next['fdformato'].'/'.$next['fdtipo'].'/'.ltrim($next['fdindicador']).'.html';
                }else{
                  $main_dir = FDESTADISTICAS__PLUGIN_DIR . '_tablas/TablasSaif/'.$pais.'/'.$formato.'/'.$tipo.'/'.$html.'.html';
                }

                $tabla = stripslashes(file_get_contents($main_dir));
  
                $data = array('combos' => $result, 'data' => $tabla);
                wp_send_json($data);
        }

        public static function get_dropdown($selection, $key, $filter, $next){

            $main_dir = FDESTADISTICAS__PLUGIN_DIR . '_tablas/TablasSaif';

            switch($key){
              case 'fdpais' :  $dir = $main_dir.'/'.$filter['fdpais'].'/'; break;
              case 'fdformato' : $dir = $main_dir.'/'.$filter['fdpais'].'/'.$next['fdformato'].'/'; break;
              case 'fdtipo' : $dir = $main_dir.'/'.$filter['fdpais'].'/'.$next['fdformato'].'/'.$next['fdtipo'].'/'; break;
            }

            $combo_formato = array();
            $p_selected = ''; $count = 0; $aux_selectd = '';

            $file = @fopen($dir."Conexion.txt", "r");
              if ($file) {
                  while (($line = fgets($file)) !== false) {
                      if (!empty(trim($line))){
                          $data = explode(';', trim($line));
                          if((int)$data[4] == 1) {
                                  $combo_formato[] = array('datafoo' => $data[2], 'value' => ltrim($data[1]), 'title' => $data[0], 'dataurl' => ltrim($data[3]) );
                                  $p_selected = $data[1];
                          }
                          else {
                                  $combo_formato[] = array('datafoo' => $data[2], 'value' => ltrim($data[1]), 'title' => $data[0] , 'dataurl' => ltrim($data[3]) );
                          }
                          if ($count == 0){
                            $aux_selectd = $data[1];
                          }
                          $count++;
                      }
                  }
                  if (!feof($file)) {
                          //echo "Error: fallo inesperado\n";
                  }
                  fclose($file);
              }
            return array ($combo_formato, ( !empty($p_selected) ? $p_selected : $aux_selectd ) );
        }
        
        public static function generate_dropdowns(){
            try {
                $main_list = array('pais' => get_query_var('pais') ,'formato' => get_query_var('formato'),'tipo' => get_query_var('tipo'),'tabla' => get_query_var('tabla'));
                $from_url = array_filter($main_list, function($v, $value){
                    return !empty($v);
                }, ARRAY_FILTER_USE_BOTH);
                $exist_query_var = sizeof($from_url) > 0 ? true : false ;
                $keys = array('title','value', 'title2', 'url', 'default');
                $attributes = array();
                $combos = array();

                $directory = array(self::MAINDIR);
                foreach($main_list as $type => $value) {
                    $attributes = array(); $matches = array();
                    $path = implode('/',$directory);
                    $file = @fopen($path."/Conexion.txt", "r");
                    // file parsed to array
                    while ($line = fgets($file)) {
                      if (!empty(trim($line))){
                        $data = array_diff(explode(";", trim($line)),array(""));
                        $attributes[] = $data;
                      }
                    }
                    fclose($file);
                    if ( $type == "tabla") { 
                        $keys = array_filter($keys, function ($tp) {
                                        return 'url' !== $tp;
                                }
                        );
                    }
                    // add keys to values from conexion file
                    $array_mapped = array_map(function ($lock) use ($keys) {
                      if ( sizeof($keys) !== sizeof($lock) ) {
                        throw new Exception("missing match url");
                      }
                      return array_combine($keys, $lock);
                    }, $attributes);
                    
                    // build dropdown
                    $dropdowns= array_map(function ($data) use ($exist_query_var, $type, $value){
                        if ($exist_query_var){
                          $pre_value = array_key_exists('url',$data) ? $data["url"] : $data['value'];
                          $selection = strtolower(ltrim($pre_value)) === $value ? "selected" : "" ;
                        }else{
                          $selection = ($data['default'] == 1 ? "selected" : "");
                        }
                        return '<option data-title2="'.$data['title2'].'" data-foo="'.$data['title'].'" value="'.ltrim($data['value']).'" data-url="'.ltrim(array_key_exists('url',$data) ? $data["url"] : $data['value']).'"  '.$selection.'>' .$data['title']. '</option>';
                    }, $array_mapped);

                    // check and get if exists default selection
                    $found = preg_grep("/selected/i",$dropdowns);
                    $first =  current($found);
                    if ( preg_match('/value="(.*?)"/i', $first, $matches) ){
                      array_push($directory,$matches[1]);
                    }else {
                      $dropdowns[0] = preg_replace('/(<option\b[^><]*)>/i', '$1 selected>', $dropdowns[0]);
                    }
                    $combos[$type] = $dropdowns;
                }
                return $combos;
            } catch (Exception $ex) {
                return null;
            }
        }
        
        public static function fdtable_shortcode_fn( $attributes ) {

            $col_class = 'wpsp-col-4'; $combo_formato = '';
            $main_dir = FDESTADISTICAS__PLUGIN_DIR . '_tablas/TablasSaif';
            $html = '<div class="main-fd">'
                    . '<div class="loading visible loader oculto">
              <div role="status" class="spinner-border center-block loadingp">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>';
            
            // FEATURE 
            $dropdowns = self::$combos;
            if ($dropdowns === null){
              $dropdowns = self::generate_dropdowns();
            }
            
            
            if (is_array($dropdowns)){
              foreach($dropdowns as $dropdown => &$key){

                switch($dropdown) {
                  case 'pais' :
                      array_unshift($key,'<div class="first-three-rows"><div class="'.$col_class.'"><select class="fdtable-ajax" id="fdpais" name="fdpais">');
                      array_push($key, '</select></div>');
                      break;
                  case 'formato' :
                      array_unshift($key,'<div class="'.$col_class.'"><select class="fdtable-ajax" id="fdformato" name="fdformato">');
                      array_push($key, '</select></div>');
                      break;
                  case 'tipo' :
                      array_unshift($key,'<div class="'.$col_class.'"><select class="fdtable-ajax" id="fdtipo" name="fdtipo">');
                      array_push($key, '</select></div></div>');
                      break;
                  case 'tabla' :
                      array_unshift($key,'<div class="last-combo-row"><select class="fdtable-ajax" id="fdindicador" name="fdindicador">');
                      array_push($key, '</select></div>');
                }

              }
            
              $combo_formato = implode(' ', array_map(function ($entry) {
                return implode(' ',$entry);
              }, $dropdowns));
            }

            $html .= $combo_formato;
            $html .= '</div>';
            $html .= '<div class="tablatitulo"></div>';
            $html .= '<div id="fdtable" class="render_table display nowrap wp-container-7"></div>';
            $html .= '<div class="wp-container-7 fdinterna">
                    <div class="fdfuente"><strong>Fuente:</strong> <a class="fdsource" href="https://glscope.com/saif">Sistema Automatizado de Información Financiera (S.A.I.F)</a></div>
                    <p><strong>R.:</strong> Ranking</p>
                    <p><strong>P.M.:</strong> Participación de Mercado</p>
                    <p><strong>N.D.:</strong> No disponible</p>
                    </div>';
            
            if (!self::$is_sassy_social){
              $html .= '<div class="wp-container-7">'
                      . '<ul class="social-list">'
                      . '<li><a class="ot-tweet social-ot" href="https://twitter.com/intent/tweet?" data-via="finanzasdigital" target="_blank">Tweet</a></li>'
                      . '<li><a class="ot-face social-ot" href="https://www.facebook.com/sharer/sharer.php?" title="Facebook" rel="nofollow noopener" target="_blank">Facebook</a></li>'
                      . '<li><a class="ot-link social-ot" href="http://www.linkedin.com/shareArticle?mini=true&url=" data-url="">Linkedin</a></li>'
                      . '</ul>'
                      . '</div>';
            }

            return $html;
        }

        public static function view( $name, array $args = array() ) {
                $args = apply_filters( 'fdestadisticas_view_arguments', $args, $name );

                foreach ( $args AS $key => $val ) {
                        $$key = $val;
                }

                load_plugin_textdomain( 'fdestadisticas' );

                $file = FDESTADISTICAS__PLUGIN_DIR . 'views/'. $name . '.php';

                include( $file );
	}
        
        public static function plugin_deactivation( ) {
            flush_rewrite_rules();
        }

        /**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation() {
		if ( version_compare( $GLOBALS['wp_version'], FDESTADISTICAS__MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'fdestadisticas' );

			$message = '<strong>'.sprintf(esc_html__( 'FD Estadisticas %s requires WordPress %s or higher.' , 'fdestadisticas'), FDESTADISTICAS_VERSION, FDESTADISTICAS__MINIMUM_WP_VERSION ).'</strong> '.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version, or <a href="%2$s">downgrade to version 2.4 of the Akismet plugin</a>.', 'fdestadisticas'), 'https://codex.wordpress.org/Upgrading_WordPress', 'https://wordpress.org/extend/plugins/fdestadisticas/download/');

			FDEstadisticas::bail_on_activation( $message );
		} elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
			add_option( 'Activated_FDEstadisticas', true );
		}
	}

        private static function bail_on_activation( $message, $deactivate = true ) {
?>
<!doctype html>
<html>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<style>
* {
	text-align: center;
	margin: 0;
	padding: 0;
	font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
}
p {
	margin-top: 1em;
	font-size: 18px;
}
</style>
</head>
<body>
<p><?php echo esc_html( $message ); ?></p>
</body>
</html>
<?php
		if ( $deactivate ) {
			$plugins = get_option( 'active_plugins' );
			$fdestadisticas = plugin_basename( FDESTADISTICAS__PLUGIN_DIR . 'fdestadisticas.php' );
			$update  = false;
			foreach ( $plugins as $i => $plugin ) {
				if ( $plugin === $fdestadisticas ) {
					$plugins[$i] = false;
					$update = true;
				}
			}

			if ( $update ) {
				update_option( 'active_plugins', array_filter( $plugins ) );
			}
		}
		exit;
	}

}