<?php
/*
 * Class for render form, get and save data
 */
class CP_Case_Management {

    function __construct(){
        //$nonce_save_members = wp_create_nonce('nonce_save_members');
        
        //add style $ scripts
        add_action( 'wp_enqueue_scripts', array($this, 'load_ss'));
        add_action( 'admin_enqueue_scripts', array($this, 'load_ss')); 

        //add forms
        add_action( 'edit_form_after_title', array($this, 'form_case_members_render') );
        add_action( 'post_submitbox_misc_actions', array($this, 'form_case_parameters_render') );
        add_action( 'post_submitbox_start', array($this, 'form_actions_case_parameters_render') );
        add_action( 'cp_entry_content_before', array($this, 'form_case_members_render_to_site'));
        add_action( 'case-sidebar', array($this, 'form_case_parameters_render_to_site'));

        //load and save data
        add_action( 'wp_ajax_query_persons', array($this, 'query_persons_callback') );
        add_action( 'wp_ajax_get_members', array($this, 'get_members_callback') );
        add_action( 'save_post', array($this, 'save_data_post'), 9);
        add_action( 'wp_ajax_save_data_cp_members', array($this, 'save_data_cp_members') );
        add_action( 'wp_ajax_get_member_from', array($this, 'get_member_from_callback') );
        add_action( 'wp_ajax_save_data_post', array($this, 'save_data_post') );
        
        
  
        
    }
	

    function form_case_members_render_to_site() {

        
        $this->form_case_members_render();

    }
	

    function form_case_parameters_render_to_site() {
        echo "<div class=\"case-sidebar\">";
        $this->form_case_parameters_render();
        echo "</div>";
    }
	
 	
    /**
    *  get form "Case Paramenters"
    *  
    *  @return html
    */     
    function form_case_parameters_render(){
            global $post; 

            //check post type
            if ((is_admin()) && !($post->post_type == 'cases')) return;

            $The_CP_Render_Fields = new CP_Render_Fields();

            echo "<div class=\"misc-pub-section\">";
            $The_CP_Render_Fields->field_case_category_render();
            $The_CP_Render_Fields->field_date_deadline();
            $The_CP_Render_Fields->field_result_render();
            $The_CP_Render_Fields->field_date_end_render();
            //$The_CP_Render_Fields->field_prioritet_render();
            
            echo "</div>";
    }

    /**
    *  get form "Case actions"
    *  
    *  @return html
    */ 
    function form_actions_case_parameters_render(){
        global $post; 
        if ((is_admin()) && !($post->post_type == 'cases')) return;

        $fields_render = new CP_Render_Fields;
        echo "<fieldset><legend>Действия:</legend><div id=\"form_actions_case_parameters\">";
        //$this->field_action_add();
        //$fields_render->field_action_delegate();
        //$fields_render->field_action_close();
        //$fields_render->field_action_accept();
        echo "</div></fieldset>";
    }

    function form_case_members_render(){
        global $post; 
        
        //check right post type
        if (is_admin() && !($post->post_type == 'cases')) return;

        $fields_render = new CP_Render_Fields;
        ?>	
                <div id="cp_case_managment_div" class="postbox">
                        <div id="cp_case_members_div">
                                <?php $fields_render->field_members_render() ?>
                                <?php $fields_render->field_member_responsible_render() ?>
                                <?php $fields_render->field_member_from_render() ?>
                        </div>
                </div>

        <?php
    }
        
    function get_data_from_life_cycle(){
        //temp disable 
        $life_cycle = lfc_get_life_cycle( 12, 'post' );
    }
        

	
    function query_persons_callback(){
            $args = array(
                'fields' => 'ids',
                's' => $_GET['q'],
                'paged' => $_GET['page'],
                'posts_per_page' => $_GET['page_limit'],
                'post_type' => 'persons'
                );

            $query = new WP_Query( $args );

            $elements = array();
            foreach ($query->posts as $post_id){
                $elements[] = array(
                    'id' => $post_id,
                    'title' => get_the_title($post_id)
                    );
            }

            $data[] = array(
                "total" => (int)$query->found_posts, 
                'elements' => $elements);
            //$data[] = $query;
            echo json_encode($data[0]);
            exit;
    }

    /*
     * Get member "From" for case
     * 
     * @return "json"
     */
    function get_member_from_callback(){
        $post_id = $_REQUEST['case_id'];
        $user_id = $_REQUEST['user_id'];
        $key = 'member_from-cp-posts-sql';

        //get Members IDs from Case metafield by key
        if (isset($post_id)) $ids = get_post_meta($post_id, $key);

        //Create array for save out data
        $out = array();

        if (count($ids) > 0){
            //get member From by data metafield
            foreach ($ids as $member_id){
                $out[] = array(
                    'id' => $member_id,
                    'title' => get_the_title( $member_id )
                );			
            }
            $out = $out[0];
        } else if(count($ids) == 0)  {
            //get person by current user id as default
            $member_id = get_person_by_user($user_id);
            $out[] = array(
                'id' => $member_id,
                'title' => get_the_title( $member_id )
            );    
            $out = $out[0];
        }


        echo json_encode($out);
        exit;     
    }

    /*
     * Get member field "Members" for case
     * 
     * @return "json"
     */
    function get_members_callback(){
        $ids = array();
        $elements = array();

        if (isset($_REQUEST['case_id']))
            $ids = get_post_meta( $_REQUEST['case_id'], 'members-cp-posts-sql');

        foreach ($ids as $member_id){
            $post_member = get_post($member_id);
            $elements[] = array(
                'id' => $member_id,
                'title' => $post_member->post_title
                );			
        }

        echo json_encode($elements);
        exit; 
    }




	


		
	function save_data_post(){
		global $post;
        //check right post type
        if (is_admin() && !($post->post_type == 'cases')) return;
        
		$post_id = $post->ID;
        
        /*
         * Save case result
         * field name: cp_case_result
         */
        if ($_REQUEST['cp_case_result'] != ''){
            $terms = $_REQUEST['cp_case_result'];
            $taxonomy = "results";
            $append = false;
            wp_set_post_terms( $post_id, $terms, $taxonomy, $append );
        }
        
        /*
         * Save case category
         * field name: cp_case_category
         */
        if ($_REQUEST['cp_case_category'] != ''){
            $terms = $_REQUEST['cp_case_category'];
            $taxonomy = "functions";
            $append = false;
            wp_set_post_terms( $post_id, $terms, $taxonomy, $append );
        }
        
        /*
         * Field "Members"
         */
		if ($_REQUEST['cp_case_members'] != '') {
			
            $key = 'members-cp-posts-sql';
            $data = trim( $_REQUEST['cp_case_members'] );
            
			delete_post_meta($post_id, $key);
            foreach (explode(',', $data) as $value ){
                add_post_meta( $post_id, $key, $value);
            }
		}
 
        /*
         * Field "From"
         */
        $key = 'member_from-cp-posts-sql';
        $data = trim( $_REQUEST['cp_member_from'] );
        delete_post_meta($post_id, $key);

        if ($_REQUEST['cp_member_from'] != '') {
			
            foreach (explode( ',', $data ) as $value ){
                add_post_meta( $post_id, $key, $value, true);
            }
        }

            
		if (isset($_REQUEST['cp_date_end'])) {
            $key = 'cp_date_end';

            if ($_REQUEST['cp_date_end'] === "") {
                $term = get_the_terms( $post_id, "results" );
                
                //delete only if not result
                if (!($term[0]->count > 0)) 
                    delete_post_meta($post_id, $key);
            } else {
               $timestamp = strtotime($_REQUEST['cp_date_end']);
               $value = date('Y-m-d H:i:s', $timestamp);
               update_post_meta( $post_id, $key, $value);                
            }
        }	
                
		if (isset($_REQUEST['cp_date_deadline'])) {
			$key = 'cp_date_deadline';
            $timestamp = strtotime($_REQUEST['cp_date_deadline']);
			
            if ($timestamp > 0) {
                if (date('H:i:s', $timestamp) == "00:00:00") $timestamp = $timestamp + 86399;
                $value = date('Y-m-d H:i:s', $timestamp);
    			update_post_meta( $post_id, $key, $value);
            }
		}
                
		if (isset($_REQUEST['cp_prioritet'])) {
			$key = 'cp_prioritet';
			$value = $_REQUEST['cp_prioritet'];
			update_post_meta( $post_id, $key, $value);
		}
	}
    
    /*
     * Load script and style
     */
	function load_ss(){
        
        //check right post type
		global $post;
        if (!($post->post_type == 'cases')) return;
        
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'jquery-masonry' );
        //admin_enqueue_scripts ('jquery-masonry');
        wp_enqueue_style(
            'cp-box-case-management',
            trailingslashit( plugin_dir_url(__FILE__) ) .'cp-box-case-management.css');

        wp_enqueue_script(
            'cp-box-case-management',
            trailingslashit( plugin_dir_url(__FILE__) ) .'cp-box-case-management.js');

/* 		
        wp_register_script( 'cp-box-case-management-client-side', 'cp-box-case-management-client-side.js');  
        wp_enqueue_script( 'cp-box-case-management-client-side' );  */ 
		}
}
$The_CP_Members = new CP_Case_Management();

/*
 * Class for render fields
 * 
 * 
 */
class CP_Render_Fields {

    function __construct(){
        //
    }
    
    /**
    *  get field for action "Close"
    *  
    *  @return html
    */  
    function field_action_close(){

          ?>
            <div>
                <a href="#close">Закрыть</a>
            </div>
        <?php          
    }
    
    /*
     * get field for action "Accept"
     * 
     * @return html
     */
    function field_action_accept() {
          ?>
            <div>
                <a href="#accepts">Принять</a>
            </div>
        <?php   
    }

    /**
    *  get field for action "Delegate"
    *  
    *  @return html
    */  
    function field_action_delegate(){

        ?>
            <div>
                <a href="#delegate">Делегировать</a>
            </div>
        <?php
    }
        
    /**
    *  get field for add other field
    *  
    *  @return html
    */ 
	function field_action_add (){
		?>
		<div class="cp_add_case_data">

			<select id="cp_add_case_data_select" onchange="cp_add_field(this);">
				<option selected="selected">Добавить поле</option>
				<option value="cp_date_end_div">Дата завершения</option>
				<option value="cp_prioritet_div">Приоритет</option>
			</select>
			<script>
				function cp_add_field(v){
					var id = v.options[v.selectedIndex].value; //get id DOM for display
					jQuery("#" + id).show();
					jQuery('select#cp_add_case_data_select').prop('selectedIndex',0);
				};
			</script>
		</div>
		<?php
	}
	
    function field_case_category_render(){
        global $post;
        $post_id = $post->ID;
        $taxonomy = 'functions';
        $terms = get_the_terms( $post_id, $taxonomy );

        //get first term from array
        if (is_array($terms)) $term = array_shift($terms);
        
        ?>
        <div id="cp_case_category_div">
            
            <label for="cp_case_category_select">Категория дела</label>
            <?php
            $case_category_id = '0';
            if (isset($term->term_id)){
                $case_category_id = $term->term_id;
            } elseif ($_REQUEST['case_category_id'] > 0) {
                $case_category_id = $_REQUEST['case_category_id'];
            } else $case_category_id = '0';

            wp_dropdown_categories( array(
                'name' => 'cp_case_category',
                'class' => 'cp_full_width',
                'id' => 'cp_case_category_select',
                'echo' => 1,
                'hide_empty' => 0, 
                'show_option_none' => 'Выберите категорию дела',
                'option_none_value' => '0',
                'selected' => $case_category_id,
                'hierarchical' => 1,
                'taxonomy' => 'functions'
            )) ; ?>
        </div>
        <?php
    }
    
    function field_date_end_render(){
        global $post;

        //convert date
        $timestamp = strtotime(get_post_meta($post->ID, "cp_date_end", true));
        if ($timestamp > 0) $value = date('Y-m-d\TH:i', $timestamp); // format: 2013-12-31T23:55

        ?>
        <div id="cp_date_end_div" <?php //echo $hide; ?>>
            <label for="cp_date_end_input">Дата завершения</label>
            <input type="datetime-local" id="cp_date_end_input" name="cp_date_end" class="cp_full_width cp_input_datepicker" value="<?php echo $value ?>"/>
        </div>
        <?php
    }
    
    function field_prioritet_render(){
        global $post;
        $post_id = $post->ID;

        $key='cp_prioritet';

        //chek field for show
        $value = get_post_meta($post_id, $key, true);
        //$hide = (($value == "") ? "style='display: none;'" : ""); //temporarily off this

        ?>
        <div id="cp_prioritet_div"  <?php //echo $hide; ?>>
            <label for="cp_prioritet_select">Приоритет</label><br/>
            <select id="cp_prioritet_select" name="cp_prioritet">
                <option <?php if($value=="") echo "selected='selected'" ?> >Выберите приоритет</option>
                <option <?php if($value=="1") echo "selected='selected'" ?> value="1">Критичный</option>
                <option <?php if($value=="2") echo "selected='selected'" ?> value="2">Высокий</option>
                <option <?php if($value=="3") echo "selected='selected'" ?> value="3">Нормальный</option>
                <option <?php if($value=="4") echo "selected='selected'" ?> value="4">Низкий</option>
                <option <?php if($value=="5") echo "selected='selected'" ?> value="5">Планируемый</option>
            </select>
        </div>
        <?php
    }
    
    function field_result_render() {
        global $post;
        $post_id = $post->ID;
        $taxonomy = 'results';
        $terms = get_the_terms( $post_id, $taxonomy );
        
        //get first term from array
        if (is_array($terms)) $term = array_shift($terms);
        
        
        if (isset($term->term_id)){
            $case_result_id = $term->term_id;
        } else $case_result_id = '0';
            
        echo "<div id=\"cp_field_result_div\">";
        echo "<label for=\"cp_field_result_select\">Результат</label>";
        wp_dropdown_categories( array(
            'name' => 'cp_case_result',
            'class' => 'cp_full_width',
            'id' => 'cp_field_result_select',
            'echo' => 1,
            'hide_empty' => 0, 
            'show_option_none' => 'Выберите результат',
            'option_none_value' => '0',
            'selected' => $case_result_id,
            'hierarchical' => 1,
            'taxonomy' => 'results'
        )) ;
        echo "</div>";
    }
        
    function field_member_responsible_render(){
        //global $post;

        ?>
            <div class="cp_field">
                            <p>
                                <label id="cp_case_responsible_label" for="cp_case_responsible_input" onclick="show_field_responsible(event);">Ответственный</label>
                                <input type="hidden" id="cp_case_responsible_input" name="cp_case_responsible" class="cp_select2_single" />
                            </p>
            </div>
            <script>
                            jQuery(document).ready(function($) {
                                //
                            });

                            function show_field_responsible(event){

                                //check one clicked element, and break fubction
                                if (jQuery(event.target).hasClass('clicked')) {return;}

                                //if this first click, then add class "clicked"
                                jQuery(event.target).addClass('clicked'); 

                                //set up select2 parameters
                                var placeholder = "";

                                jQuery("#cp_case_responsible_input").select2({
                                        placeholder: placeholder,
                                        width: '100%',
                                        minimumInputLength: 1,
                                        ajax: {
                                                url: "<?php echo admin_url('admin-ajax.php') ?>",
                                                dataType: 'json',
                                                quietMillis: 100,
                                                data: function (term, page) { // page is the one-based page number tracked by Select2
                                                        return {
                                                                action: 'query_persons',
                                                                page_limit: 10, // page size
                                                                page: page, // page number
                                                                //params: {contentType: "application/json;charset=utf-8"},
                                                                q: term //search term
                                                        };
                                                },
                                                results: function (data, page) {
                                                        //alert(data.total);
                                                        var more = (page * 10) < data.total; // whether or not there are more results available

                                                        // notice we return the value of more so Select2 knows if more results can be loaded
                                                        return {
                                                                results: data.elements,
                                                                more: more
                                                                };
                                                }
                                        },
                                        formatResult: elementFormatResult, // omitted for brevity, see the source of this page
                                        formatSelection: elementFormatSelection, // omitted for brevity, see the source of this page
                                        dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
                                        escapeMarkup: function (m) { return m; } // we do not want to escape markup since we are displaying html in results
                                });
                            };
                        </script>
        <?php

    }
    
    function field_members_render(){
        global $post;
        ?>
            <div class="cp_field">
                <p>
                    <label for="cp_case_members_input">Участники</label>
                    <input type="hidden" id="cp_case_members_input" name="cp_case_members" class="cp_select2" />
                </p>
            </div>
            <script type="text/javascript">
                function auto_save_data_members(){
                    //action: save_data_post
                }
                jQuery(document).ready(function($) {
                    $("#cp_case_members_input").select2({
                        placeholder: "",
                        formatInputTooShort: function (input, min) { return "Пожалуйста, введите " + (min - input.length) + " или более символов"; },
                        minimumInputLength: 1,
                        formatSearching: function () { return "Поиск..."; },
                        formatNoMatches: function () { return "Ничего не найдено"; },
                        width: '100%',
                        multiple: true,
                        ajax: {
                            url: "<?php echo admin_url('admin-ajax.php') ?>",
                            dataType: 'json',
                            quietMillis: 100,
                            data: function (term, page) { // page is the one-based page number tracked by Select2
                                return {
                                    action: 'query_persons',
                                    page_limit: 10, // page size
                                    page: page, // page number
                                    //params: {contentType: "application/json;charset=utf-8"},
                                    q: term //search term
                                };
                            },
                            results: function (data, page) {
                                //alert(data.total);
                                var more = (page * 10) < data.total; // whether or not there are more results available

                                // notice we return the value of more so Select2 knows if more results can be loaded
                                return {
                                    results: data.elements,
                                    more: more
                                    };
                            }
                        },

                        formatResult: elementFormatResult, // omitted for brevity, see the source of this page
                        formatSelection: elementFormatSelection, // omitted for brevity, see the source of this page
                        dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
                        escapeMarkup: function (m) { return m; } // we do not want to escape markup since we are displaying html in results
                    });
                    $.ajax({
                        data: ({
                            action: 'get_members',
                            dataType: 'json',
                            case_id: <?php echo $post->ID?>,
                        }),
                        url: "<?php echo admin_url('admin-ajax.php') ?>",
                        success: function(data) {
                                members = $.parseJSON(data);
                                $('#cp_case_members_input').select2('data',  members);
                            }
                    });
                });
            </script>
        <?php
    }
    
    function field_member_from_render(){
        global $post;
        ?>
            <div class="cp_field">
                    <p>
                            <label for="cp_member_from_input" title="Указываем инициатора дела (задачи, сообщения, приказа ...)">От кого</label>
                            <input type="hidden" id="cp_member_from_input" name="cp_member_from" class="cp_select2_single" />
                    </p>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    var placeholder = "";

                    $("#cp_member_from_input").select2({
                            placeholder: placeholder,
                            width: '100%',
                            allowClear: true,
                            minimumInputLength: 1,
                            ajax: {
                                    url: "<?php echo admin_url('admin-ajax.php') ?>",
                                    dataType: 'json',
                                    quietMillis: 100,
                                    data: function (term, page) { // page is the one-based page number tracked by Select2
                                            return {
                                                    action: 'query_persons',
                                                    page_limit: 10, // page size
                                                    page: page, // page number
                                                    //params: {contentType: "application/json;charset=utf-8"},
                                                    q: term //search term
                                            };
                                    },
                                    results: function (data, page) {
                                            //alert(data.total);
                                            var more = (page * 10) < data.total; // whether or not there are more results available

                                            // notice we return the value of more so Select2 knows if more results can be loaded
                                            return {
                                                    results: data.elements,
                                                    more: more
                                                    };
                                    }
                            },
                            formatResult: elementFormatResult, // omitted for brevity, see the source of this page
                            formatSelection: elementFormatSelection, // omitted for brevity, see the source of this page
                            dropdownCssClass: "bigdrop", // apply css that makes the dropdown taller
                            escapeMarkup: function (m) { return m; } // we do not want to escape markup since we are displaying html in results
                    });

                    $.ajax({
                        data: ({
                            action: 'get_member_from',
                            dataType: 'json',
                            user_id: <?php echo get_current_user_id() ?>,
                            case_id: <?php echo $post->ID ?>
                        }),
                        url: "<?php echo admin_url('admin-ajax.php') ?>",
                        success: function(data) {
                            data = $.parseJSON(data);
                            $('#cp_member_from_input').select2('data', data);
                        }
                    });
                });

            </script>
    <?php
    }
        

    
    function field_date_deadline(){
        global $post;

        //convert date
        $timestamp = strtotime(get_post_meta($post->ID, "cp_date_deadline", true));
        if ($timestamp > 0) $value = date('Y-m-d', $timestamp);
        

        ?>
        <div id="cp_field_date_deadline_div" >
                <label for="cp_field_date_deadline_input">Срок</label>
                <span id="cp_field_date_deadline_view"><?php echo $value?></span>
                <div id="cp_field_date_deadline_edit">
                    <div id="cp_field_date_deadline_edit_input">
                        <input type="date" id="cp_field_date_deadline_input" name="cp_date_deadline" class="cp_full_width cp_input_datepicker" value="<?php echo $value?>"/>
                    </div>  
                    <p>
                        <a href="#ok" class="button">OK</a>
                        <a href="#cancel">Отмена</a>
                    </p>
               </div>
        </div>
        <?php
    }
}

class CP_Automaton_Case {

    function __construct() {
             //auto hooks
        add_action("add_term_relationship", array($this, "auto_set_date_end"), 10, 2);
        add_action("deleted_term_relationships", array($this, "auto_del_date_end"), 10, 2);
        
        
    }
	function auto_set_date_end($object_id, $tt_id){
        //post
        $post_id = $object_id;
        $key = 'cp_date_end';
        $value = current_time("mysql");//date("Y-m-d H:i:s");
			
        
        //receipt of the term and on-condition
        $field="id";
        $taxonomy = "results";
        $term = get_term_by( $field, $tt_id, $taxonomy);

        $current_date_end = get_post_meta($post_id, $key, true);
        
        if ($term->taxonomy === "results" && !($current_date_end > 0)){
                update_post_meta( $post_id, $key, $value);
        }
    }
    
    function auto_del_date_end($object_id, $tt_id) {
        //
    }
}

$TheCP_Automaton_Case = new CP_Automaton_Case;
?>