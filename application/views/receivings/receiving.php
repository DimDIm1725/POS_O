<?php $this->load->view("partial/header"); ?>

<div id="page_title"><?php echo $this->lang->line('recvs_register'); ?></div>

<?php
if (isset($error))
{
	echo "<div class='alert alert-dismissible alert-danger'>".$error."</div>";
}
?>

<div id="register_wrapper">
	<?php echo form_open("receivings/change_mode", array('id'=>'mode_form', 'class'=>'form-horizontal panel panel-default')); ?>
		<div class="panel-body form-group">
			<ul>
				<li class="float_left">
					<label class="control-label"><?php echo $this->lang->line('recvs_mode'); ?></label>
				</li>
				<li class="float_left">
					<?php echo form_dropdown('mode', $modes, $mode, array('onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
				</li>

				<?php 
				if ($show_stock_locations)
				{
				?>
					<li class="float_left">
						<label class="control-label"><?php echo $this->lang->line('recvs_stock_source'); ?></label>
					</li>
					<li class="float_left">
						<?php echo form_dropdown('stock_source', $stock_locations, $stock_source, array('onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
					</li>
					
					<?php
					if($mode=='requisition')
					{
					?>
						<li class="float_left">
							<label class="control-label"><?php echo $this->lang->line('recvs_stock_destination'); ?></label>
						</li>
						<li class="float_left">
							<?php echo form_dropdown('stock_destination', $stock_locations, $stock_destination, array('onchange'=>"$('#mode_form').submit();", 'class'=>'selectpicker show-menu-arrow', 'data-style'=>'btn-default btn-sm', 'data-width'=>'fit')); ?>
						</li>
				<?php
					}
				}
				?>
			</ul>
		</div>
	<?php echo form_close(); ?>

	<?php echo form_open("receivings/add", array('id'=>'add_item_form', 'class'=>'form-horizontal panel panel-default')); ?>
		<div class="panel-body form-group">
			<ul>
				<li class="float_left">
					<label for="item", class='control-label'>
						<?php
						if($mode=='receive' or $mode=='requisition')
						{
							echo $this->lang->line('recvs_find_or_scan_item');
						}
						else
						{
							echo $this->lang->line('recvs_find_or_scan_item_or_receipt');
						}
						?>			
					</label>
				</li>
				
				<li class="float_left">
					<?php echo form_input(array('name'=>'item', 'id'=>'item', 'class'=>'form-control input-sm', 'size'=>'50', 'tabindex'=>'1')); ?>
				</li>

				<li class="float_right">
					<?php echo anchor("items/view/-1", $this->lang->line('sales_new_item'), 
							array('class'=>'btn btn-info btn-sm modal-dlg modal-btn-new modal-btn-submit', 'id'=>'new_item_button', 'title'=>$this->lang->line('sales_new_item'))); ?>
				</li>
			</ul>
		</div>
	<?php echo form_close(); ?>
	
<!-- Receiving Items List -->

	<table class="sales_table_100" id="register">
		<thead>
			<tr>
				<th style="width:10%;"><?php echo $this->lang->line('common_delete'); ?></th>
				<th style="width:35%;"><?php echo $this->lang->line('recvs_item_name'); ?></th>
				<th style="width:10%;"><?php echo $this->lang->line('recvs_cost'); ?></th>
				<th style="width:10%;"><?php echo $this->lang->line('recvs_quantity'); ?></th>
				<th style="width:5%;"></th>
				<th style="width:10%;"><?php echo $this->lang->line('recvs_discount'); ?></th>
				<th style="width:10%;"><?php echo $this->lang->line('recvs_total'); ?></th>
				<th style="width:10%;"><?php echo $this->lang->line('recvs_edit'); ?></th>
			</tr>
		</thead>

		<tbody id="cart_contents">
			<?php
			if(count($cart)==0)
			{
			?>
				<tr>
					<td colspan='8'><div class='alert alert-dismissible alert-info'><?php echo $this->lang->line('sales_no_items_in_cart'); ?></div></td>
				</tr>
			<?php
			}
			else
			{
				foreach(array_reverse($cart, true) as $line=>$item)
				{
					echo form_open("receivings/edit_item/$line", array('class'=>'form-horizontal'));	
			?>
						<tr>
							<td><?php echo anchor("receivings/delete_item/$line", '<span class="glyphicon glyphicon-trash"></span>');?></td>
							<td style="align: center;"><?php echo base64_decode($item['name']); ?><br /> [<?php echo $item['in_stock']; ?> in <?php echo $item['stock_name']; ?>]
								<?php echo form_hidden('location', $item['item_location']); ?></td>

							<?php if ($items_module_allowed && $mode !='requisition')
							{
							?>
								<td><?php echo form_input(array('name'=>'price', 'class'=>'form-control input-sm', 'value'=>$item['price']));?></td>
							<?php
							}
							else
							{
							?>
								<td><?php echo $item['price']; ?></td>
								<?php echo form_hidden('price',$item['price']); ?>
							<?php
							}
							?>
							
							<td>
							<?php
								echo form_input(array('name'=>'quantity', 'class'=>'form-control input-sm', 'value'=>$item['quantity']));
								if ($item['receiving_quantity'] > 1) 
								{
							?>
									</td>
									<td>x <?php echo $item['receiving_quantity']; ?></td>	
							<?php 
								}
								else
								{
							?>
									</td>
									<td></td>
							<?php 
								}
							?>
						
							<?php       
								if ($items_module_allowed && $mode!='requisition')
								{
							?>
									<td><?php echo form_input(array('name'=>'discount', 'class'=>'form-control input-sm', 'value'=>$item['discount']));?></td>
							<?php
								}
								else
								{
							?>
									 <td><?php echo $item['discount']; ?></td>
									 <?php echo form_hidden('discount',$item['discount']); ?>
							<?php
								}
							?>
							<td><?php echo to_currency($item['price']*$item['quantity']-$item['price']*$item['quantity']*$item['discount']/100); ?></td>
							<td><?php echo form_submit(array('name'=>'edit_item', 'value'=>$this->lang->line('sales_edit_item'), 'class'=>'btn btn-default btn-xs'));?></td>
						</tr>
						<tr>
							<?php 
							if($item['allow_alt_description']==1)
							{
							?>
								<td style="color: #2F4F4F;"><?php echo $this->lang->line('sales_description_abbrv').':';?></td>
							<?php 
							} 
							?>
							<td colspan='2' style="text-align: left;">
						
							<?php
								if($item['allow_alt_description']==1)
								{
									echo form_input(array('name'=>'description', 'class'=>'form-control input-sm', 'value'=>base64_decode($item['description'])));
								}
								else
								{
									if (base64_decode($item['description'])!='')
									{
										echo base64_decode($item['description']);
										echo form_hidden('description',base64_decode($item['description']));
									}
									else
									{
										echo $this->lang->line('sales_no_description');
										echo form_hidden('description','');
									}
								}
							?>
							</td>
							<td colspan='6'></td>
						</tr>
					<?php
					echo form_close();
				}
			}
			?>
		</tbody>
	</table>
</div>

<!-- Overall Receiving -->

<div id="overall_sale">
	<?php
	if(isset($supplier))
	{
		echo $this->lang->line("recvs_supplier").': <b>'.$supplier. '</b><br />';
		echo anchor("receivings/delete_supplier",'['.$this->lang->line('common_delete').' '.$this->lang->line('suppliers_supplier').']');
	}
	else
	{
		echo form_open("receivings/select_supplier", array('id'=>'select_supplier_form'));
	?>
			<label id="supplier_label" for="supplier"><?php echo $this->lang->line('recvs_select_supplier'); ?></label>
			<?php echo form_input(array('name'=>'supplier', 'id'=>'supplier', 'size'=>'30', 'value'=>$this->lang->line('recvs_start_typing_supplier_name')));?>
		<?php echo form_close(); ?>
		
		<div style="margin-top:5px; text-align:center;">
			<h3 style="margin: 5px 0 5px 0"><?php echo $this->lang->line('common_or'); ?></h3>

			<?php 
			echo anchor("suppliers/view/-1/width:400", $this->lang->line('recvs_new_supplier'), 
						array('class'=>'btn btn-info btn-sm modal-dlg modal-btn-submit none', 'id'=>'new_supplier_button', 'title'=>$this->lang->line('recvs_new_supplier')));
			?>
		</div>

		<div class="clearfix">&nbsp;</div>
	<?php
	}
	?>
	
    <?php
	if($mode != 'requisition')
	{
    ?>
		<div id='sale_details'>
			<div class="float_left" style='width:55%;'><?php echo $this->lang->line('sales_total'); ?>:</div>
			<div class="float_left" style="width:45%;font-weight:bold;"><?php echo to_currency($total); ?></div>
		</div>
	<?php 
	}
	?>

	<?php
	if(count($cart) > 0)
	{
		if($mode == 'requisition')
		{
	?>
		    <div style='border-top:2px solid #000;' />
		    <div id="finish_sale">
		        <?php echo form_open("receivings/requisition_complete", array('id'=>'finish_receiving_form')); ?>
					<br />
					<label id="comment_label" for="comment"><?php echo $this->lang->line('common_comments'); ?>:</label>
					<?php echo form_textarea(array('name'=>'comment','id'=>'comment','value'=>$comment,'rows'=>'4','cols'=>'23'));?>
					<br /><br />
					
					<div class="btn btn-sm btn-success pull-right" id='finish_receiving_button' style='margin-top:5px;'>
						<?php echo $this->lang->line('recvs_complete_receiving') ?>
					</div>
				<?php echo form_close(); ?> 
				
				<?php echo form_open("receivings/cancel_receiving", array('id'=>'cancel_receiving_form')); ?>
					<div class="btn btn-sm btn-danger pull-left" id='cancel_receiving_button' style='margin-top:5px;'>
						<?php echo $this->lang->line('recvs_cancel_receiving')?>
					</div>
				<?php echo form_close(); ?>
		     </div>
	<?php
		}
		else
		{
	?>
		<div id="finish_sale">
			<?php echo form_open("receivings/complete", array('id'=>'finish_receiving_form')); ?>
				<br />
				<label id="comment_label" for="comment"><?php echo $this->lang->line('common_comments'); ?>:</label>
				<?php echo form_textarea(array('name'=>'comment','id'=>'comment','value'=>$comment,'rows'=>'4','cols'=>'23'));?>
				<br /><br />
				<table class="sales_table_100">
				<tr>
					<td>
						<?php echo $this->lang->line('recvs_print_after_sale'); ?>
					</td>
					<td>
						<?php if ($print_after_sale)
						{
							echo form_checkbox(array('name'=>'recv_print_after_sale','id'=>'recv_print_after_sale','checked'=>'checked'));
						}
						else
						{
							echo form_checkbox(array('name'=>'recv_print_after_sale','id'=>'recv_print_after_sale'));
						}
						?>
					</td>
				</tr>
				<?php
				if ($mode == "receive") 
				{
				?>
					<tr>
						<td>
							<?php echo $this->lang->line('recvs_invoice_enable'); ?>
						</td>
						<td>
						<?php
						if ($invoice_number_enabled)
						{
							echo form_checkbox(array('name'=>'recv_invoice_enable','id'=>'recv_invoice_enable','size'=>10,'checked'=>'checked'));
						}
						else
						{
							echo form_checkbox(array('name'=>'recv_invoice_enable','id'=>'recv_invoice_enable','size'=>10));
						}
						?>
						</td>
					</tr>
					<tr>
						<td>
							<?php echo $this->lang->line('recvs_invoice_number').':   ';?>
						</td>
						<td>
							<?php echo form_input(array('name'=>'recv_invoice_number','id'=>'recv_invoice_number','value'=>$invoice_number,'size'=>10));?>
						</td>
					</tr>
				<?php 
				}
				?>
				<tr>
					<td>
					<?php
						echo $this->lang->line('sales_payment').':   ';?>
					</td>
					<td>
					<?php
						echo form_dropdown('payment_type',$payment_options);?>
					</td>
				</tr>

				<tr>
					<td>
					<?php
						echo $this->lang->line('sales_amount_tendered').':   ';?>
					</td>
					<td>
					<?php
						echo form_input(array('name'=>'amount_tendered','value'=>'','size'=>'10'));
					?>
					</td>
				</tr>

				</table>
				<br />
				<div class='btn btn-sm btn-success pull-right' id='finish_receiving_button' style='margin-top:5px;'>
					<?php echo $this->lang->line('recvs_complete_receiving') ?>
				</div>
			<?php echo form_close(); ?>

			<?php echo form_open("receivings/cancel_receiving", array('id'=>'cancel_receiving_form')); ?>
					<div class='btn btn-sm btn-danger pull-left' id='cancel_receiving_button' style='margin-top:5px;'>
						<?php echo $this->lang->line('recvs_cancel_receiving')?>
					</div>
			<?php echo form_close(); ?>
		</div>
	<?php
		}
	}
	?>
</div>
<div class="clearfix" style="margin-bottom:30px;">&nbsp;</div>

<script type="text/javascript" language="javascript">
$(document).ready(function()
{
    $("#item").autocomplete(
    {
		source: '<?php echo site_url("receivings/item_search"); ?>',
    	minChars:0,
       	delay:10,
       	autoFocus: false,
		select:	function (a, ui) {
			$(this).val(ui.item.value);
			$("#add_item_form").submit();
		}
    });

    $('#item').focus();

	$('#item').blur(function()
    {
    	$(this).attr('value',"<?php echo $this->lang->line('sales_start_typing_item_name'); ?>");
    });

	$('#comment').keyup(function() 
	{
		$.post('<?php echo site_url("receivings/set_comment");?>', {comment: $('#comment').val()});
	});

	$('#recv_invoice_number').keyup(function() 
	{
		$.post('<?php echo site_url("receivings/set_invoice_number");?>', {recv_invoice_number: $('#recv_invoice_number').val()});
	});

	$("#recv_print_after_sale").change(function()
	{
		$.post('<?php echo site_url("receivings/set_print_after_sale");?>', {recv_print_after_sale: $(this).is(":checked")});
	});

	var enable_invoice_number = function() 
	{
		var enabled = $("#recv_invoice_enable").is(":checked");
		$("#recv_invoice_number").prop("disabled", !enabled).parents('tr').show();
		return enabled;
	}

	enable_invoice_number();

	$("#recv_invoice_enable").change(function() {
		var enabled = enable_invoice_number();
		$.post('<?php echo site_url("receivings/set_invoice_number_enabled");?>', {recv_invoice_number_enabled: enabled});
		
	});

	$('#item,#supplier').click(function()
    {
    	$(this).attr('value','');
    });

    $("#supplier").autocomplete(
    {
		source: '<?php echo site_url("suppliers/suggest"); ?>',
    	minChars:0,
    	delay:10,
		select: function (a, ui) {
			$(this).val(ui.item.value);
			$("#select_supplier_form").submit();
		}
    });

    $('#supplier').blur(function()
    {
    	$(this).attr('value',"<?php echo $this->lang->line('recvs_start_typing_supplier_name'); ?>");
    });

    $("#finish_receiving_button").click(function()
    {
    	if (confirm('<?php echo $this->lang->line("recvs_confirm_finish_receiving"); ?>'))
    	{
    		$('#finish_receiving_form').submit();
    	}
    });

    $("#cancel_receiving_button").click(function()
    {
    	if (confirm('<?php echo $this->lang->line("recvs_confirm_cancel_receiving"); ?>'))
    	{
    		$('#cancel_receiving_form').submit();
    	}
    });


});

function post_item_form_submit(response, stay_open)
{
	if(response.success)
	{
		$("#item").attr("value",response.item_id);
		if (stay_open)
		{
			$("#add_item_form").ajaxSubmit();
		}
		else
		{
			$("#add_item_form").submit();
		}
	}
}

function post_person_form_submit(response)
{
	if(response.success)
	{
		$("#supplier").attr("value",response.person_id);
		$("#select_supplier_form").submit();
	}
}

</script>
<?php $this->load->view("partial/footer"); ?>
