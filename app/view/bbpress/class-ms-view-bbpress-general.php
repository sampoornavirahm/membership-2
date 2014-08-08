<?php

class MS_View_Bbpress_General extends MS_View {

	protected $fields = array();
	
	protected $title;
	
	protected $data;
	
	public function render_rule_tab() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Integration_Bbpress::RULE_TYPE_BBPRESS );
		$list_table = new MS_Helper_List_Table_Rule_Bbpress( $rule );
		$list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'BBPress ', MS_TEXT_DOMAIN ) . $this->title; ?></h3>
				<div class="settings-description">
					<?php _e( 'Select the forum settings below that you would like to give access to as part of this membership.', MS_TEXT_DOMAIN ); ?>
				</div>
				<hr />							
				<?php $list_table->views(); ?>
				<form action="" method="post">
					<?php $list_table->display(); ?>
				</form>
			</div>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
}