<?php
function cases_display_childs() {

		if ( is_single() && get_post_type() == 'cases' ) {
			if ( function_exists( 'datatable_generator' ) ) {
			
				$cp_components_url = plugin_dir_url(__FILE__).'casepress/cp-components/';
		wp_enqueue_script('datatable', $cp_components_url.'cp-datatable/assets/dt.js', array('jquery'));
		wp_enqueue_script('datatable.tt', $cp_components_url.'cp-datatable/assets/dt.tableTools.js', array('datatable'));
		wp_enqueue_script('datatable.rg', $cp_components_url.'cp-datatable/assets/dt.rowGrouping.js', array('datatable'));
		wp_enqueue_script('datatable.tg', $cp_components_url.'cp-datatable/assets/dt.treeGrid.js', array('datatable'));
		wp_enqueue_script('datatable.init', $cp_components_url.'cp-datatable/assets/init.js', array('datatable'));
		wp_enqueue_style('datatable', $cp_components_url.'cp-datatable/assets/theme.css');
		
				global $post;
				$childs = get_children( array(
					'post_parent' => $post->ID,
					'post_type' => 'cases',
					'numberposts' => -1,
					'post_status' => 'publish'
					) );
				$box_class = ( count( $childs ) > 0 ) ? 'cases-box-open' : 'cases-box-closed';
				$sub_task_link = admin_url( 'post-new.php?post_type=cases&csposter&csposter_parent_id=' . get_the_ID() );
				$call_bank_link = '/wp-admin/post-new.php?post_type=cases&csposter&csposter_function=123';
				?>
				<div class="cases-box cases-box-childs-mod <?php echo $box_class; ?>">
					<div class="cases-box-header">
						<h3>
							<a href="#" class="cases-box-toggle">Досье  </a>
							<? /*echo count($childs)*/?>
							<a href="#childs" name="childs" class="cases-box-anchor">#</a>
							<img id="check_ajax_load2" src="/wp-content/plugins/cases-metabox-checks/ajax-loading.gif" style="padding-left: 20px; display: none; ">
						</h3>
						<div class="cases-box-actions">
							<a href="<?php echo $sub_task_link; ?>" class="fancybox-iframe btn btn-mini">Добавить подзадачу</a>
							<?php
							// Это договор по ипотеке, нужно вывести ссылку "Заявка в банк"
							if ( has_term( 114, 'functions' ) ) {
								?>
								<a href="<?php echo $call_bank_link; ?>" class="fancybox-iframe btn btn-mini">Прием сотрудника</a>
								<?php
							}
							?>
						</div>
					</div>
					<div class="cases-box-content" id="cases_dossie">
						<?php //$childss=datatable_generator( array( 'src' => 'global', 'tree' => 'ID:post_parent', 'view' => 'id:dt_case_childs' ) ); ?>
						
					</div>
					<script type='text/javascript'>						
						jQuery(".cases-box-childs-mod").addClass('<?php echo $box_class;?>');
						jQuery(".cases-box-childs-mod .cases-box-toggle").append(' (<?php echo count($childs);?>)');
						jQuery(document).ready(function(){							
							jQuery.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									action: 'get_case_dossier_datatable',
									current_id: <?php echo $post->ID; ?>
								},
								success: function(data) {
									// Put new HTML into container
									jQuery('#cases_dossie').html(data);
								//	alert(data);
								},
								complete: function() {
									
								},
								dataType: 'html'
							});
							jQuery('#check_ajax_load2').bind({
								ajaxStart: function() 
								{ 
									jQuery(this).show();												
								},
								ajaxStop: function() 
								{ 
									jQuery(this).hide(); 
								}
							
							});							
						});
					</script>
				</div>
				
				<!-- Action priority: 60 -->
				<?php
			}
			get_template_part( 'template', 'acf-form' );
		}
	}

	add_action( 'roots_entry_content_after', 'cases_display_childs', 60 );
	function get_case_dossier_datatable(){
	
	
		if (isset($_POST['current_id'])) $postid = $_POST['current_id'];
		if ( function_exists( 'datatable_generator' ) )
		datatable_generator( array('parent'=>$postid,'class'=>'tax-all','tax'=>''));
		//echo '<div style="width: 50px; height: 50px; color: red;">atata</div>';
		die();
	}
	add_action('wp_ajax_get_case_dossier_datatable','get_case_dossier_datatable');
	
?>