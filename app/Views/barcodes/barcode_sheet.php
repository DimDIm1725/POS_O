<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<?php
/**
 * @var array $barcode_config
 * @var array $items
 */
?>

<html xmlns="http://www.w3.org/1999/xhtml" lang="<?= $this->request->getLocale() ?>">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?= lang('Items.generate_barcodes') ?></title>
	<link rel="stylesheet" rev="stylesheet" href="<?= base_url() ?>css/barcode_font.css" />
</head>

<body class=<?= "font_".$this->barcode_lib->get_font_name($barcode_config['barcode_font']) ?>
      style="font-size:<?= $barcode_config['barcode_font_size'] ?>px">
	<table cellspacing=<?= $barcode_config['barcode_page_cellspacing'] ?> width='<?= $barcode_config['barcode_page_width']."%" ?>' >
		<tr>
			<?php
			$count = 0;
			foreach($items as $item)
			{
				if ($count % $barcode_config['barcode_num_in_row'] == 0 and $count != 0)
				{
					echo '</tr><tr>';
				}
				echo '<td>' . $this->barcode_lib->display_barcode($item, $barcode_config) . '</td>';
				++$count;
			}
			?>
		</tr>
	</table>
</body>

</html>
