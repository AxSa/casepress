<?php


function register_cases_report_posttype(){
  $labels = array(
    'name'=>'Отчеты',
    'singular_name'=>'Отчет',
    'add_new'=>'Добавить',
    'add_new_item'=>'Добавить отчет',
    'edit_item'=>'Редактировать отчет',
    'new_item'=>'Новый отчет',
    'view_item'=>'Просмотр отчета',
    'search_items'=>'Поиск отчета',
    'not_found'=>'Отчет не найден',
    'parent_item_colon'=>''
  );

  register_post_type('report', array(
    'label'=>$labels['singular_name'],
    'labels'=>$labels,
    'public'=>true,
    'hierarchical'=>true,
	'rewrite' => array('slug' => 'reports', 'with_front' => false ),
    'supports'=>array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'page-attributes'),
    'taxonomies'=>array(),
    'query_var'=>true,
    'menu_position'=>10,
  ));
} add_action('init', 'register_cases_report_posttype');


?>