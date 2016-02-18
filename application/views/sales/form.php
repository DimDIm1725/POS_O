<div id="edit_sale_wrapper">
	<div id="required_fields_message"><?php echo $this->lang->line('common_fields_required_message'); ?></div>
	<ul id="error_message_box" class="error_message_box"></ul>
	
	<fieldset id="sale_basic_info">
		<?php echo form_open("sales/save/".$sale_info['sale_id'], array('id'=>'sales_edit_form')); ?>
			<legend><?php echo $this->lang->line("sales_basic_information"); ?></legend>
			
			<div class="field_row clearfix">
				<?php echo form_label($this->lang->line('sales_receipt_number').':', 'customer'); ?>
				<div class='form_field'>
					<?php echo anchor('sales/receipt/'.$sale_info['sale_id'], $this->lang->line('sales_receipt_number') .$sale_info['sale_id'], array('target' => '_blank'));?>
				</div>
			</div>
			
			<div class="field_row clearfix">
				<?php echo form_label($this->lang->line('sales_date').':', 'date'); ?>
				<div class='form_field'>
					<?php echo form_input(array('name'=>'date','value'=>date($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), strtotime($sale_info['sale_time'])), 'id'=>'datetime', 'readonly'=>'true'));?>
				</div>
			</div>
			
			<div class="field_row clearfix">
				<?php echo form_label($this->lang->line('sales_invoice_number').':', 'invoice_number'); ?>
				<div class='form_field'>
					<?php if (isset($sale_info["invoice_number"]) && !empty($sale_info["invoice_number"]) && 
						isset($sale_info['customer_id']) && isset($sale_info['email']) && !empty($sale_info['email'])): ?>
						<?php echo form_input(array('name'=>'invoice_number', 'size'=>10, 'value'=>$sale_info['invoice_number'], 'id'=>'invoice_number'));?>
						<a id="send_invoice" href="javascript:void(0);"><?=$this->lang->line('sales_send_invoice')?></a>
					<?php else: ?>
						<?php echo form_input(array('name'=>'invoice_number', 'value'=>$sale_info['invoice_number'], 'id'=>'invoice_number'));?>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="field_row clearfix">
				<?php echo form_label($this->lang->line('sales_customer').':', 'customer'); ?>
				<div class='form_field'>
					<?php echo form_input(array('name' => 'customer_id', 'value' => $selected_customer, 'id' => 'customer_id'));?>
				</div>
			</div>
			
			<div class="field_row clearfix">
				<?php echo form_label($this->lang->line('sales_employee').':', 'employee'); ?>
				<div class='form_field'>
					<?php echo form_dropdown('employee_id', $employees, $sale_info['employee_id'], 'id="employee_id"');?>
				</div>
			</div>
			
			<div class="field_row clearfix">
				<?php echo form_label($this->lang->line('sales_comment').':', 'comment'); ?>
				<div class='form_field'>
					<?php echo form_textarea(array('name'=>'comment', 'value'=>$sale_info['comment'], 'rows'=>'4', 'cols'=>'23', 'id'=>'comment'));?>
				</div>
			</div>
			
			<?php echo form_submit(array(
				'name' => 'submit',
				'value' => $this->lang->line('common_submit'),
				'class' => 'btn btn-primary btn-sm pull-right')
			);
			?>
		<?php echo form_close(); ?>
		
		<?php echo form_open("sales/delete/".$sale_info['sale_id'], array('id'=>'sales_delete_form')); ?>
			<?php echo form_hidden('sale_id', $sale_info['sale_id']);?>
			<?php echo form_submit(array(
				'name'=>'submit',
				'value'=>$this->lang->line('sales_delete_entire_sale'),
				'class'=>'btn btn-danger btn-sm pull-right',
				'style'=>'margin-right: 10px;')
			);
			?>
		<?php echo form_close(); ?>
	</fieldset>
</div>

<script type="text/javascript" language="javascript">
$(document).ready(function()
{	
	<?php if (isset($sale_info['email'])): ?>
		$("#send_invoice").click(function(event) {
			if (confirm("<?php echo $this->lang->line('sales_invoice_confirm') . ' ' . $sale_info['email'] ?>")) {
				$.get('<?=site_url() . "/sales/send_invoice/" . $sale_info['sale_id']?>',
						function(response) {
							dialog_support.hide();
							post_form_submit(response);
						}, "json"
				);	
			}
		});
	<?php endif; ?>
	
	$.validator.addMethod("invoice_number", function(value, element)
	{
		return JSON.parse($.ajax(
		{
			  type: 'POST',
			  url: '<?php echo site_url($controller_name . "/check_invoice_number")?>',
			  data: {'sale_id' : <?php echo $sale_info['sale_id']; ?>, 'invoice_number' : $(element).val() },
			  success: function(response)
			  {
				  success=response.success;
			  },
			  async:false,
			  dataType: 'json'
        }).responseText).success;
    }, '<?php echo $this->lang->line("sales_invoice_number_duplicate"); ?>');

	// set the locale language for the datetime picker
	$.fn.datetimepicker.dates['<?php echo $this->config->item("language"); ?>'] = {
		days: ["<?php echo $this->lang->line("common_days_sunday"); ?>",
				"<?php echo $this->lang->line("common_days_monday"); ?>",
				"<?php echo $this->lang->line("common_days_tueday"); ?>",
				"<?php echo $this->lang->line("common_days_wednesday"); ?>",
				"<?php echo $this->lang->line("common_days_thursday"); ?>",
				"<?php echo $this->lang->line("common_days_friday"); ?>",
				"<?php echo $this->lang->line("common_days_saturday"); ?>",
				"<?php echo $this->lang->line("common_days_sunday"); ?>"],
		daysShort: ["<?php echo $this->lang->line("common_daysshort_sunday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_monday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_tueday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_wednesday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_thursday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_friday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_saturday"); ?>",
				"<?php echo $this->lang->line("common_daysshort_sunday"); ?>"],
		daysMin: ["<?php echo $this->lang->line("common_daysmin_sunday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_monday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_tueday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_wednesday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_thursday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_friday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_saturday"); ?>",
				"<?php echo $this->lang->line("common_daysmin_sunday"); ?>"],
		months: ["<?php echo $this->lang->line("common_months_january"); ?>",
				"<?php echo $this->lang->line("common_months_february"); ?>",
				"<?php echo $this->lang->line("common_months_march"); ?>",
				"<?php echo $this->lang->line("common_months_april"); ?>",
				"<?php echo $this->lang->line("common_months_may"); ?>",
				"<?php echo $this->lang->line("common_months_june"); ?>",
				"<?php echo $this->lang->line("common_months_july"); ?>",
				"<?php echo $this->lang->line("common_months_august"); ?>",
				"<?php echo $this->lang->line("common_months_september"); ?>",
				"<?php echo $this->lang->line("common_months_october"); ?>",				
				"<?php echo $this->lang->line("common_months_november"); ?>",
				"<?php echo $this->lang->line("common_months_december"); ?>"],
		monthsShort: ["<?php echo $this->lang->line("common_monthsshort_january"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_february"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_march"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_april"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_may"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_june"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_july"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_august"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_september"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_october"); ?>",				
				"<?php echo $this->lang->line("common_monthsshort_november"); ?>",
				"<?php echo $this->lang->line("common_monthsshort_december"); ?>"],
		today: "<?php echo $this->lang->line("common_today"); ?>",
		<?php
			$t = $this->config->item('timeformat');
			$m = $t[strlen($t)-1];
			if( $m == 'a')
			{ 
		?>
				meridiem: ["am", "pm"],
		<?php 
			}
			else
			{
		?>
				meridiem: ["AM", "PM"],
		<?php 
			}
		?>
		weekStart: <?php echo $this->lang->line("common_weekstart"); ?>,
	};
	
	$('#datetime').datetimepicker({
		format: "<?php echo dateformat_bootstrap($this->config->item("dateformat")) . ' ' . dateformat_bootstrap($this->config->item("timeformat"));?>",
		startDate: "<?php echo date($this->config->item('dateformat') . ' ' . $this->config->item('timeformat'), mktime(0, 0, 0, 1, 1, 2010));?>",
		<?php
			$t = $this->config->item('timeformat');
			$m = $t[strlen($t)-1];
			if( $m == 'a' || $m == 'A' )
			{ 
		?>
				showMeridian: true,
		<?php 
			}
			else
			{
		?>
				showMeridian: false,
		<?php 
			}
		?>
		minuteStep: 1,
		autoclose: true,
		todayBtn: true,
		todayHighlight: true,
		bootcssVer: 3,
		language: "<?php echo $this->config->item('language'); ?>"
	});

	var format_item = function(row)
	{
    	var result = [row[0], "|", row[1]].join("");
    	// if more than one occurence
    	if (row[2] > 1 && row[3] && row[3].toString().trim()) {
			// display zip code
    		result += ' - ' + row[3];
    	}
		return result;
	};

	var autocompleter = $("#customer_id").autocomplete('<?php echo site_url("sales/customer_search"); ?>', 
	{
		minChars: 0,
		delay: 15, 
		max: 100,
		cacheLength: 1,
		formatItem: format_item,
		formatResult : format_item
	});

	// declare submitHandler as an object.. will be reused
	var submit_form = function(selected_customer) 
	{ 
		$(this).ajaxSubmit(
		{
			success: function(response)
			{
				dialog_support.hide();
				post_form_submit(response);
			},
			error: function(jqXHR, textStatus, errorThrown) 
			{
				selected_customer && autocompleter.val(selected_customer);
				post_form_submit({message: errorThrown});
			},
			dataType: 'json'
		});
	};

	$('#sales_edit_form').validate(
	{
		submitHandler : function(form)
		{
			var selected_customer = autocompleter.val();
			var selected_customer_id = selected_customer.replace(/(\w)\|.*/, "$1");
			selected_customer_id && autocompleter.val(selected_customer_id);
			submit_form.call(form, selected_customer);
		},
		errorLabelContainer: "#error_message_box",
		wrapper: "li",
		rules: 
		{
			invoice_number:
			{
				invoice_number: true
			}
		},
		messages: 
		{

		}
	});

	$('#sales_delete_form').submit(function() 
	{
		if (confirm('<?php echo $this->lang->line("sales_delete_confirmation"); ?>'))
		{
			var id = $("input[name='sale_id']").val();
			$(this).ajaxSubmit({
				success: function(response)
				{
					dialog_support.hide();
					set_feedback(response.message, 'alert alert-dismissible alert-success', false);
					var $element = get_table_row(id).parent().parent();
					$element.find("td").animate({backgroundColor:"green"},1200,"linear")
					.end().animate({opacity:0},1200,"linear",function()
					{
						$element.next().remove();
						$(this).remove();
						//Re-init sortable table as we removed a row
						update_sortable_table();
					});
				},
				error: function(jqXHR, textStatus, errorThrown) {
					set_feedback(textStatus, 'alert alert-dismissible alert-danger', true);
				},
				dataType:'json'
			});
		}
		return false;
	});
});
</script>
