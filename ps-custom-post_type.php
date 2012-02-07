<?php
/*
Plugin Name: 002 PS Custom Post Type 
Plugin URI: http://wordpress.org/extend/plugins/002-ps-custom-post-type/
Description: Manager custom post type  setting ./config/ps-custom-post_type-config.php
Author: Wang Bin
Version: 1.0
Author URI: http://www.prime-strategy.co.jp/about/staff/oh/
*/

/**
 * Ps_Custom_Post_Type
 *
 * Main Ps_Custom_Post_Type Plugin Class
 *
 * @package ps-custom-post_type
 */
class Ps_Custom_Post_Type{
	
	/*
	*	Start Ps_Custom_Post_Type on plugins loaded
	*/
	function Ps_Custom_Post_Type(){
		$this->__construct();
		
	}

	/*
	 * initializing
	 */
	function __construct() {
		$this->init();
	}

	/*
	 * Takes care of loading up Ps_Custom_Post_Type
	 */
	function init(){
		define( 'DOCUMENTROOT' , $_SERVER['DOCUMENT_ROOT']);
		define( 'HOMEDIR' , dirname($_SERVER['DOCUMENT_ROOT']));
	    define( 'CUSTOM_POST_TYPE_PLUGIN' , dirname(__FILE__));
	    
	    include_once ( CUSTOM_POST_TYPE_PLUGIN . '/config/ps-custom-post_type-config.php' );
	    
		//翻訳に関しては、次のバンジョーを対応します。
		add_action( 'plugins_loaded'    , array( &$this, 'load_plugin_textdomain' ) );    
		
		//全部タクソノミーとカスタム投稿タイプを読み込み
		$this->PsMyConf = $taxonomy;
		
		add_action( 'init', array($this , 'add_page_taxonomy' ) );
	
		//アイキャッチ画像
		add_action( 'init', array($this , 'add_theme_support' ) );
			
		//カスタム投稿一覧にアイキャッチ画像の表示を追加する
		foreach ( $taxonomy as $key => $val  ){
			add_filter( 'manage_' . $val->post_type . '_posts_columns', array(&$this , 'add_posts_columns' ));
			add_action('manage_' . $val->post_type . '_posts_custom_column', array(&$this , 'scompt_custom_column'), 10, 2);
		}
	
		
		//順序を追加する
		add_action( 'init', array( &$this, 'give_my_post_edit' ));
		
		//日付の絞り込み検索の障害対応
		add_filter('posts_request', array(&$this, 'ps_search_where_error'));
	
		if ( $custom_post_tag ){
			$this->custom_post_tag = $custom_post_tag;
			//投稿タグの処理
			add_action( 'init', array( &$this, 'add_custom_post_tag'));
		}
	
		//メィデアのタクソノミーをカスタマイズ
		if ( $attachement->taxonomy ){
			$this->attachement = $attachement;
			add_action( 'init', array(&$this, 'add_attachementGenre' ));
			add_action( 'init', array(&$this, 'registerGenreAttachementLink' ));
			add_action( 'admin_menu', array(&$this, 'add_media_taxonomy_menu' ));
			//下記の処理について、プラグイン[ps-taxonomy-expander]を参照しました。
			add_filter( 'attachment_fields_to_edit'         , array( &$this, 'replace_attachement_taxonomy_input_to_check' ), 100, 2 );
		}
	
	}
	
	/** 
	 * ファンクション名：give_my_post_edit
	 * 機能概要: 投稿に順序を追加する
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function give_my_post_edit(){
		add_post_type_support( 'post', 'page-attributes' );
	}
	
	/** 
	 * ファンクション名：add_page_taxonomy
	 * 機能概要：個別専用投稿メニュー追加
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function add_page_taxonomy() {
		global $current_user,$current_blog;
	
		if ( $current_blog->blog_id ){
	    	$current_level_name = "wp_{$current_blog->blog_id}_user_level";
		}else{
			$current_level_name = "wp_user_level";
		}
	
	    $current_user_level = (int)$current_user->data->$current_level_name;
	
		foreach ( $this->PsMyConf as $key => $MyConf  ){
	
			//if ($MyConf->user_level >= $current_user_level){
				if ($MyConf->editor_show !== true){
					$this->Ps_register_post_type( $MyConf ,false);
				}else{
					$this->Ps_register_post_type( $MyConf );
				}
			//}		
		}
	}
	
	/** 
	 * ファンクション名：ps_search_where_error
	 * 機能概要：日付の絞り込み検索の障害対応
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function ps_search_where_error( $w ){
	
		if ( $_REQUEST['post_type'] == 'post' || $_REQUEST['post_type'] == 'attachment'){
			return $w;
		}
	
		if ( isset ($_REQUEST['post_type']) ){
	    	$w = str_replace( '\'post\'', "'" . $_REQUEST['post_type'] . "'", $w );
		    $w = preg_replace("/AND wp_term_taxonomy.term_id=\d{1,2}/", " " , $w);
		}
		return $w;
	}
	
	/** 
	 * ファンクション名：add_page_taxonomy
	 * 機能概要：カスタム投稿およびタクソノミーを構成する
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function Ps_register_post_type( $Conf , $showflg = true){
		$WPLANG = get_option('WPLANG' , true);
		//$WPLANG = 'ja';
		
		$name = $WPLANG == 'ja' ? $Conf->show_name : $Conf->show_name_eng;
			
		$labels = array(
			'name' => $name,
			'singular_name' => $name,
			'add_new' => __( 'Add New Post'),
			'add_new_item' => $WPLANG == 'ja' ? '新たに'.$Conf->show_name.'を追加' : 'Add New Link ' . $Conf->show_name_eng,
			'edit_item' => $WPLANG == 'ja' ? $Conf->show_name . 'を編集' : 'Edit ' . $Conf->show_name_eng,
			'new_item' =>  $WPLANG == 'ja' ? '新しい' . $Conf->show_name : 'New ' . $Conf->show_name_eng,
			'view_item' =>  $WPLANG == 'ja' ? 'プレビュー' : 'Preview',
			'search_items' => $WPLANG == 'ja' ? $Conf->show_name . 'で探す' : 'Search ' . $Conf->show_name_eng,
			'not_found' => $WPLANG == 'ja' ? $Conf->show_name . 'は登録されていません' : 'No '.$Conf->show_name_eng.' found.',
			'not_found_in_trash' => $WPLANG == 'ja' ? 'ゴミ箱にアイテムはありません' : 'Not found in trash.' ,
			'parent_item_colon' => ''
		);
	
		if ( $showflg === true && !$Conf->supports ){
			//20100725 delete 'custom-fields'　
			$supports = array('title','excerpt','editor','author','revisions' ,'thumbnail' , 'page-attributes' );
		}elseif ( $Conf->supports ){
			$supports = $Conf->supports;
		}else {
			$supports = array('title','excerpt','author','revisions','thumbnail','page-attributes');//'page-attributes' ページ属性
		}
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => 5,
			'supports' => $supports,
			'show_in_nav_menus' => true
		);
	
		if ( $Conf->menu_icon ){
			$args['menu_icon'] = get_bloginfo('template_url').$Conf->menu_icon; // メニュー メニューに表示されるアイコン画像
		}
	
		register_post_type($Conf->post_type,$args);
	
		if ( is_array($Conf->category_name) && count($Conf->category_name) ){
			foreach ( $Conf->category_name as $key => $name  ){
				$args = array(
					'label' => $name,
					'labels' => array(
						'name' => $name,
						'singular_name' => $name,
						'search_items' => $name . 'を検索',
						'popular_items' => '登録の多い' . $name,
						'all_items' => 'すべての' . $name,
						'parent_item' => '上位エリア' . $name,
						'edit_item' => $name . 'の編集',
						'update_item' => '更新',
						'add_new_item' => $name . 'の追加',
						'new_item_name' => '新' . $name,
					),
					'public' => true,
					'show_ui' => true,
					'hierarchical' => true,
					'show_tagcloud' => true
				);
				register_taxonomy($key, $Conf->post_type, $args);
			}
		}
	
		if ( is_array($Conf->tax_type) && count($Conf->tax_type) ){
			foreach ( $Conf->tax_type as $key => $name  ){
				$args = array(
					'label' => $name,
					'labels' => array(
						'name' => $name,
						'singular_name' => $name,
						'search_items' => $name . 'の検索',
						'popular_items' => '登録の多い' . $name,
						'all_items' => 'すべての'. $name,
						'parent_item' => '上位' . $name,
						'edit_item' => $name . 'の編集',
						'update_item' => '更新',
						'add_new_item' => $name . 'の追加',
						'new_item_name' => '新' . $name,
					),
					'public' => true,
					'show_ui' => true,
					'hierarchical' => true,
					'show_tagcloud' => true
				);
				
				//Ps_register_rename($args ,0,array( 'タイプ' => 'ショップタイプ'));
				register_taxonomy($key, $Conf->post_type, $args);
			}	
		}
	
	}
	
	/** 
	 * ファンクション名：add_custom_post_tag
	 * 機能概要：フラグの追加（投稿タグ）
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function add_custom_post_tag(){
		
		$name = $this->custom_post_tag->show_name;
		$args = array(
			'label' => $name,
			'labels' => array(
				'name' => $name,
				'singular_name' => $name,
				'search_items' => $name . 'を検索',
				'popular_items' => '登録の多い' . $name,
				'all_items' => 'すべての' . $name,
				'parent_item' => '上位' . $name ,
				'edit_item' => $name . 'を編集',
				'update_item' =>'更新',
				'add_new_item' => $name . 'を追加',
				'new_item_name' =>  $name . 'を新規追加',
			),
			'public' => true,
			'show_ui' => true,
			'hierarchical' => true,
			'show_tagcloud' => false,
			'_builtin' => false
		);
		register_taxonomy('post_tag', 'post', $args);
	}
	
	
	/*
	*メディアジャンルを追加
	*/
	/** 
	 * ファンクション名：add_attachementGenre
	 * 機能概要：メィデアのタクソノミーを設定する
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function add_attachementGenre(){
		
		$genre = $this->attachement;
	
		$labels = array(
		    'name' => $genre->show_name,
		    'singular_name' => $genre->show_name,
		    'search_items' => $genre->show_name.'で探す',
		    'all_items' => '全ての' . $genre->show_name,
		    'parent_item' => '親' . $genre->show_name,
		    'parent_item_colon' => '親の' . $genre->show_name,
		    'edit_item' => $genre->show_name . 'の編集',
		    'update_item' => $genre->show_name . 'を更新',
		    'add_new_item' => $genre->show_name . 'を追加',
		    'new_item_name' => '新規'.$genre->show_name.'名',
		    'aa' => $genre->show_name . 'を追加',
		);  
		 
		register_taxonomy(
		    $genre->taxonomy,
		    array( 'attachment' ),
		    array(
		        'hierarchical' => true,
		        'labels' => $labels,
		        'query_var' => true,
		        'rewrite' => array(
		            'slug' => $genre->taxonomy,
		            'hierarchical' => true,
		            'with_front' => false
		        ),
		    )
		);
		
	}
	
	
	/*
	 * メィデアジャンルをメニューに追加する
	 */
	/** 
	 * ファンクション名：add_media_taxonomy_menu
	 * 機能概要：メィデアのタクソノミーをメニューに追加する
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function add_media_taxonomy_menu( ) {
	    global $wp_taxonomies, $submenu;
	 
	    $media_taxonomies = array();
	    if ( $wp_taxonomies ) {
	        foreach ( $wp_taxonomies as $key => $obj ) {
	            if ( count( $obj->object_type ) == 1 && $obj->object_type[0] == 'attachment' && $obj->show_ui ) {
	                $media_taxonomies[$key] = $obj;
	            }
	        }
	    }
	
	    if ( $media_taxonomies ) {
	        $priority = 50;
	        foreach ( $media_taxonomies as $key => $media_taxonomy ) {
	
	            if ( current_user_can( $media_taxonomy->cap->manage_terms ) ) {
	
	                $submenu['upload.php'][$priority] = array( $media_taxonomy->labels->menu_name, 'upload_files', 'edit-tags.php?taxonomy=' . $key .'&post_type=attachment');
	                $priority += 5;
	
	            }
	        }
	
	    }
	}
	
	/*
	 * メディアジャンルよりメディア一覧
	 */
	/** 
	 * ファンクション名：registerGenreAttachementLink
	 * 機能概要 : attachment リックを設定
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function registerGenreAttachementLink(){
		$genre = $this->attachement;
		if ( $_GET[$genre->taxonomy] &&   ($_GET['post_type'] == 'attachment' || $_GET['post_type'] == 'post' ) ){
			if ( preg_match( '/^edit\.php\?'.$genre->taxonomy.'=[a-z]+&post_type=(attachment|post)$/', basename( $_SERVER['REQUEST_URI'] ) ) ) {
				$_SERVER['REQUEST_URI'] = preg_replace( '/edit\.php/' , 'upload.php',  $_SERVER['REQUEST_URI']  );
				wp_redirect( $_SERVER['REQUEST_URI'] );
				exit;
			}
			
		}
		
	}
	
	/** 
	 * ファンクション名：replace_attachement_taxonomy_input_to_check
	 * 機能概要：メィデア編集画面のメィデアカスタムタクソノミーをチェックボックスにする
	 * 「ps-taxonomy-expander」を多少参照しました。			 
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param  メィデアの項目
	 * @param  投稿(attachment)
	 * @return  htmlタグ
	 */
	function replace_attachement_taxonomy_input_to_check( $form_fields, $post ) {
	    if ( $form_fields ) {
	        foreach ( $form_fields as $taxonomy => $obj ) {
	            if ( isset( $obj['hierarchical'] ) && $obj['hierarchical'] ) {
	                $terms = get_terms( $taxonomy, array( 'get' => 'all' ) ); 
	                $taxonomy_tree = array();
	                $branches = array();
	                $term_id_arr = array();
	
	                foreach( $terms as $term ) {
	                    $term_id_arr[$term->term_id] = $term;
	                    if ( $term->parent == 0 ) {
	                        $taxonomy_tree[$term->term_id] = array();
	                    } else {
	                        $branches[$term->parent][$term->term_id] = array();
	                    }    
	                }    
	
	                if ( count( $branches ) ) {
	                    foreach( $branches as $foundation => $branch ) {
	                        foreach( $branches as $branche_key => $val ) {
	                            if ( array_key_exists( $foundation, $val ) ) {
	                                $branches[$branche_key][$foundation] = &$branches[$foundation];
	                                break 1;
	                            }    
	                        }    
	                    }    
	
	                    foreach ( $branches as $foundation => $branch ) {
	                        if ( isset( $taxonomy_tree[$foundation] ) ) {
	                            $taxonomy_tree[$foundation] = $branch;
	                        }    
	                    }    
	                }    
	
	                $html = $this->walker_media_taxonomy_html( $post->ID, $taxonomy, $term_id_arr, $taxonomy_tree );
	                if ( $terms ) {
	                    $form_fields[$taxonomy]['input'] = 'checkbox';
	                    $form_fields[$taxonomy]['checkbox'] = $html;
	                } else {                    $form_fields[$taxonomy]['input'] = 'html';
	                    $form_fields[$taxonomy]['html'] = sprintf( __( '%s is not registerd.', 'ps-taxonomy-expander' ), esc_html( $obj['labels']->singular_name ), esc_html( $obj['labels']->name ) );
	                }    
	            }    
	        }    
	    }
	    return $form_fields;
	}
	
	/** 
	 * ファンクション名：walker_media_taxonomy_html
	 * 機能概要：チェックボックスを作成する
	 * 「ps-taxonomy-expander」を多少参照しました。			 
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param 投稿ID
	 * @param タクソノミー
	 * @param 
	 * @param 
	 * @return  チェックボックスｈｔｍｌ
	 */
	function walker_media_taxonomy_html( $post_id, $taxonomy,  $term_id_arr, $taxonomy_tree, $html = '', $cnt = 0 ) {
		$this->single_taxonomies = get_option( 'single_taxonomies' ) ? get_option( 'single_taxonomies' ) : array();
		foreach ( $taxonomy_tree as $term_id => $arr ) {
			$checked = is_object_in_term( $post_id, $taxonomy, $term_id ) ? ' checked="checked"' : '';
			$type = in_array( $taxonomy, $this->single_taxonomies ) ? 'radio' : 'checkbox';
			$html .= str_repeat( 'a?”', count( get_ancestors( $term_id, $taxonomy ) ) );
			$html .= ' <input type="' . $type . '" id="attachments[' . $post_id . '][' . $taxonomy . ']-' . $cnt . '" name="attachments[' . $post_id . '][' . $taxonomy . '][]" value="' . esc_attr( $term_id_arr[$term_id]->name ) . '"' . $checked . ' /><label for="attachments[' . $post_id . '][' . $taxonomy . ']-' . $cnt . '">' . esc_html( $term_id_arr[$term_id]->name ) . "</label><br />\n";
			$cnt++;
			if ( count( $arr ) ) {
				$html = $this->walker_media_taxonomy_html( $post_id, $taxonomy, $term_id_arr, $arr, $html, &$cnt );
			}
		}
		return $html;
	}
	
	/** 
	 * ファンクション名：add_posts_columns
	 * 機能概要：アイキャッチ画像のくらむを設定する
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param クラム
	 * @return  クラム
	 */
	function add_posts_columns($columns) {
		global $wp_version;
	
		if (substr($wp_version, 0, 3) < '3.1' ){
	    	$columns['category'] = "カテゴリー";
		}
	    $columns['featured_image'] = "アイキャッチ";
		return $columns;
		
	}
	
	/** 
	 * ファンクション名：scompt_custom_column
	 * 機能概要：カスタム投稿一覧にアイキャッチ画像を表示させる
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function scompt_custom_column($column_name, $id) {
		global $post , $blog_id;
	
	    if( $column_name == 'featured_image' ) {
			$thum = get_the_post_thumbnail($post_id, array(50,50), 'thumbnail');
			echo $thum;
	    }
		if( $column_name == 'category' ) {
	    	$terms = get_the_terms( $id , $post->post_type . '-category' );
			//$path = get_blog_status( $blog_id, 'path' );
			foreach ($terms as $key => $term){
	    		echo '<a href="' . $path . '/wp-admin/edit.php?post_type='.$post->post_type.'&'.$post->post_type.'-category=' .$term->slug . '" title="'.$term->name.'">'. $term->name . '</a>';   		
			}
	    }
	    
	}
	
	/** 
	 * ファンクション名：add_theme_support
	 * 機能概要：アイキャッチ画像の設定
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function add_theme_support(){
		/*
		*アイキャッチ画像を設定(Main画像)
		*/
		add_theme_support( 'post-thumbnails' );
		
		set_post_thumbnail_size(115 , 75, true );
	
	}
	
	/** 
	 * ファンクション名：load_plugin_textdomain
	 * 機能概要：翻訳ファイルを追加（未対応）
	 * 作成：プライム・ストラテジー株式会社 王 濱
	 * 変更：
	 * @param なし
	 * @return  なし
	 */
	function load_plugin_textdomain( ){
		 load_plugin_textdomain( '002-ps-cuntom-type-languages', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

}//class end

$Ps_Custom_Post_Type = new Ps_Custom_Post_Type();

include_once ( dirname(__FILE__) . '/config/ps-custom-post_type-functions.php' );

?>
