<?php
// If you would like to edit this file, copy it to your current theme's directory and edit it there.
// This plugin will always look in your theme's directory first, before using this default template.

global $themedLoginInstance;
$template = $themedLoginInstance->current_instance;

?>
<div class="tml tml-user-panel" id="themed-login<?php $template->the_instance(); ?>">
	<?php
	if ($template->options['show_gravatar']) {
		?>
		<div class="tml-user-avatar"><?php $template->the_user_avatar(); ?></div>
		<?php
	}

	$template->the_user_links();

	do_action('tml_user_panel'); ?>
</div>
