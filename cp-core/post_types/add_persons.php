<?php
	function core_get_person_by_user($user_id){
		global $wpdb;
		$person_id=$wpdb->get_var("SELECT meta_value FROM wp_usermeta where meta_key='id_person' and user_id='".$user_id."'");
		if (!isset($person_id)) $person_id=0;
		return $person_id;
	} 
	
add_action('init', 'register_persons_posttype');	
function register_persons_posttype() {
	$labels = array(
		'name' 				=> 'Персоны',
		'singular_name'		=> 'Персона',
		'add_new' 			=> 'Добавить',
		'add_new_item' 		=> 'Добавить Персону',
		'edit_item' 		=> 'Редактировать Персону',
		'new_item' 			=> 'Новая Персона',
		'view_item' 		=> 'Просмотр Персону',
		'search_items' 		=> 'Поиск Персоны',
		'not_found' 		=> 'Персона не найдена',
		'not_found_in_trash'=> 'В Корзине Персона не найдена',
		'parent_item_colon' => ''
	);
	
	$taxonomies = array();
	
	$supports = array(
		'title',
		'editor',
//		'author',
//		'thumbnail',
//		'excerpt',
//		'custom-fields',
//		'comments',
//		'revisions',
//		'post-formats',
//		'page-attributes'
	);
			
	$args = array(
		'labels' 			=> $labels,
		'singular_label' 	=> 'Персона',
		'public' 			=> true,
		'show_ui' 			=> ((current_user_can('manage_options')) OR (has_term('kadrovic', 'persons_post',core_get_person_by_user(get_current_user_id())))) ? true : false,
		'publicly_queryable'=> true,
		'query_var'			=> true,
		'capability_type' 	=> 'post',	
		'has_archive' 		=> true,
		'hierarchical' 		=> true,
		'rewrite' 			=> array('slug' => 'persons', 'with_front' => false ),
		'supports' 			=> $supports,
		'menu_position' 	=> 5,
		'taxonomies'		=> $taxonomies
	 );
	register_post_type('persons',$args);
}

?>