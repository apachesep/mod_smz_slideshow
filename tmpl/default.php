<?php
/**
 * @package smz_slideshow
 * @version 3.5
 * @author Sergio Manzi
 * @link http://smz.it
 * @copyright Copyright (c) 2013 - 2015 Sergio Manzi. All Rights Reserved.
 * @license GNU General Public License version 3, or later
 *
 * The basic idea and part of this work is based on JoomlaMan JMSlideShow
 * @copyright Copyright (c) 2012 - 2013 JoomlaMan.com. All Rights Reserved.
 *
 */

defined('_JEXEC') or die;

$item_class = 'slide-item';
$image_class = 'slide-img';
$readmore_txt = JText::_('MOD_SMZ_SLIDESHOW_READMORE');
$prev_class = 'cycle-prev';
$next_class = 'cycle-next';


// Fix classes for MSIE
jimport('joomla.environment.browser');
$browser = JBrowser::getInstance();
$browserType = $browser->getBrowser();
$browserVersion = $browser->getMajor();
$browser_class = '';
if ($browserType == 'msie') {
	if ($browserVersion < 9) {
		$browser_class = ' msie-le8';
	} else {
		$browser_class = ' msie-ge9';
	}
}

// Pager
$pager_before = false;
$pager_overlay = false;
$pager_after = false;
$show_pager = $params->get('smz_slideshow_show_pager', 0);
if ($show_pager) {
	$pager_css = '';
	$pager_name = 'cycle-pager';
	$pager_class = 'cycle-pager';
	$pager_position = $params->get('smz_slideshow_pager_position', 'after');
	$pager_v_margin = $params->get('smz_slideshow_pager_v_margin', 0);
	$pager_h_margin = $params->get('smz_slideshow_pager_h_margin', 0);
	switch ($pager_position) {
		case 'bottomcenter' :
			$pager_overlay = true;
			$pager_css .= "bottom:{$pager_v_margin}px;";
			$pager_css .= 'width:100%;text-align:center;';
			$pager_after = true;
			break;
		case 'bottomleft' :
			$pager_overlay = true;
			$pager_css .= "bottom:{$pager_v_margin}px;";
			$pager_css .= "left:{$pager_h_margin}px;";
			break;
		case 'bottomright' :
			$pager_overlay = true;
			$pager_css .= "bottom:{$pager_v_margin}px;";
			$pager_css .= "right:{$pager_h_margin}px;";
			break;
		case 'topcenter' :
			$pager_overlay = true;
			$pager_css .= "top:{$pager_v_margin}px;";
			$pager_css .= 'width:100%;text-align:center;';
			$pager_after = true;
			break;
		case 'topleft' :
			$pager_overlay = true;
			$pager_css .= "top:{$pager_v_margin}px;";
			$pager_css .= "left:{$pager_h_margin}px;";
			break;
		case 'topright' :
			$pager_overlay = true;
			$pager_css .= "top:{$pager_v_margin}px;";
			$pager_css .= "right:{$pager_h_margin}px;";
			break;
		case 'abovecenter' :
			$pager_before = true;
			$pager_css .= "margin-bottom:{$pager_v_margin}px;";
			$pager_css .= 'width:100%;text-align:center;';
			break;
		case 'aboveleft' :
			$pager_before = true;
			$pager_css .= "margin-bottom:{$pager_v_margin}px;";
			$pager_css .= "margin-left:{$pager_h_margin}px;";
			break;
		case 'aboveright' :
			$pager_before = true;
			$pager_css .= "margin-bottom:{$pager_v_margin}px;";
			$pager_css .= "margin-right:{$pager_h_margin}px;";
			$pager_css .= 'text-align:right;';
			break;
		case 'belowcenter' :
			$pager_after = true;
			$pager_css .= "margin-top:{$pager_v_margin}px;";
			$pager_css .= 'width:100%;text-align:center;';
			break;
		case 'belowleft' :
			$pager_after = true;
			$pager_css .= "margin-top:{$pager_v_margin}px;";
			$pager_css .= "margin-left:{$pager_h_margin}px;";
			break;
		case 'belowright' :
			$pager_after = true;
			$pager_css .= "margin-top:{$pager_v_margin}px;";
			$pager_css .= "margin-right:{$pager_h_margin}px;";
			$pager_css .= 'text-align:right;';
			break;
	}
}
if ($pager_overlay) $pager_css .= 'position:absolute;';

// Overlay
$show_overlay = $params->get('smz_slideshow_show_overlay', 1) && ($params->get('slides_source', 0) > 0);  // No overlay if source == folder
$overlay_show_title = $params->get('smz_slideshow_overlay_show_title', 0);
$overlay_show_desc = $params->get('smz_slideshow_overlay_show_desc', 0);
$overlay_show_readmore = $params->get('smz_slideshow_overlay_show_readmore', 0);
if ($show_overlay) {
	$overlay_name = 'cycle-overlay';
	$overlay_class = 'cycle-overlay';
	if ($overlay_show_desc || $overlay_show_title || $overlay_show_readmore) {
		$overlay_position = $params->get('smz_slideshow_overlay_position', 'after');
		$overlay_v_margin = $params->get('smz_slideshow_overlay_v_margin', 0);
		$overlay_h_margin = $params->get('smz_slideshow_overlay_h_margin', 0);
		$overlay_hidden_phone = $params->get('smz_slideshow_overlay_hidden_phone', 0) == 0 ? '' : ' hidden-phone';
		$overlay_width = trim(trim($params->get('smz_slideshow_overlay_width', 100),"%"));
		if (!is_numeric($overlay_width) || $overlay_width < 0 || $overlay_width > 100) {
			$overlay_width = 100;
		}
		$overlay_css = 'position:absolute;';
		$overlay_css .= 'width:' . $overlay_width . '%;';
		switch ($overlay_position) {
			case 'bottomleft' :
				$overlay_css .= "bottom:{$overlay_v_margin}px;";
				$overlay_css .= "left:{$overlay_h_margin}px;";
			break;
			case 'bottomright' :
				$overlay_css .= "bottom:{$overlay_v_margin}px;";
				$overlay_css .= "right:{$overlay_h_margin}px;";
				$overlay_css .= 'text-align:right;';
			break;
			case 'topleft' :
				$overlay_css .= "top:{$overlay_v_margin}px;";
				$overlay_css .= "left:{$overlay_h_margin}px;";
			break;
			case 'topright' :
				$overlay_css .= "top:{$overlay_v_margin}px;";
				$overlay_css .= "right:{$overlay_h_margin}px;";
				$overlay_css .= 'text-align:right;';
			break;
		}
	}
}


// Caption
$caption_before = false;
$caption_after = false;
$show_caption = $params->get('smz_slideshow_show_caption', 1) && ($params->get('slides_source', 0) > 0);  // No caption if source == folder
$caption_show_title = $params->get('smz_slideshow_caption_show_title', 0);
$caption_show_desc = $params->get('smz_slideshow_caption_show_desc', 0);
$caption_show_readmore = $params->get('smz_slideshow_caption_show_readmore', 0);
$caption_link_to_article = $params->get('smz_slideshow_caption_link_to_article', 0);
if ($show_caption) {
	$caption_name = 'cycle-caption';
	$caption_class = 'cycle-caption';
	if ($caption_show_desc || $caption_show_title || $caption_show_readmore) {
		$caption_position = $params->get('smz_slideshow_caption_position', 'below');
		$caption_v_margin = $params->get('smz_slideshow_caption_v_margin', 0);
		$caption_h_margin = $params->get('smz_slideshow_caption_h_margin', 0);
		$caption_hidden_phone = $params->get('smz_slideshow_caption_hidden_phone', 0) == 0 ? '' : ' hidden-phone';
		$caption_width = trim(trim($params->get('smz_slideshow_caption_width', 100),"%"));
		if (!is_numeric($caption_width) || $caption_width < 0 || $caption_width > 100) {
			$caption_width = 100;
		}
		$caption_css = "width:{$caption_width}%;";
		switch ($caption_position) {
			case 'below' :
				$caption_after = true;
				$caption_css .= "margin-top:{$caption_v_margin}px;";
				$caption_css .= "margin-left:{$caption_h_margin}px;";
				break;
			case 'above' :
				$caption_before = true;
				$caption_css .= "margin-bottom:{$caption_v_margin}px;";
				$caption_css .= "margin-left:{$caption_h_margin}px;";
				break;
		}
	}
}

// The grouping attribute used for Fancybox
if ($link_target == 0)
{
	$fancybox_grouping = $params->get('fancybox_grouping', 'data-fancybox-group');
}

// Outer div
echo "<div id='{$slideshow_name}-container-{$module->id}' class='{$slideshow_name}{$moduleclass_sfx}{$browser_class}'";
echo " style='";
if ($responsive) {
	echo "width:100%;max-width:{$slideshow_width}px;";
} else {
	echo "width:{$slideshow_width}px;";
}
echo "{$slideshow_center}";
echo "'>";

// "Above" divs
if ($caption_before) echo "<div id='{$caption_name}-{$module->id}' class='{$caption_class}{$caption_hidden_phone}' style='{$caption_css}'></div>";
if ($pager_before) echo "<div style='width:100%;'><div id='{$pager_name}-{$module->id}' class='{$pager_class}{$controls_hidden_phone}' style='{$pager_css}'></div></div>";

// Main div
echo "<div id='{$slideshow_name}-{$module->id}'";
echo " class='cycle-slideshow'";

if (empty($slides))
{
	echo '>';
	echo JText::_('MOD_SMZ_SLIDESHOW_NO_SLIDES');
}
else
{
	if ($has_links) echo " data-cycle-slides=\"> a\"";
	echo " data-cycle-auto-height=\"calc\"";
	echo " data-cycle-center-horz=\"true\"";
	echo " data-cycle-center-vert=\"true\"";
	echo " data-cycle-fx=\"{$effect}\"";
	echo " data-cycle-speed=\"{$speed}\"";
	echo " data-cycle-swipe=\"{$allow_swipe}\"";
	echo " data-cycle-timeout=\"{$timeout}\"";
	if ($timeout != 0) echo " data-cycle-pause-on-hover=\"{$pause_onhover}\"";
	if ($show_pager) echo " data-cycle-pager=\"#{$pager_name}-{$module->id}\"";
	if ($show_caption) {
		echo " data-cycle-caption=\"#{$caption_name}-{$module->id}\"";
		echo ' data-cycle-caption-template="';
		if ($caption_link_to_article) echo "<a class='slideshow-caption-link' href={{readmore}}>";
		if ($caption_show_title) echo '<h4>{{title}}</h4>'; 
		if ($caption_show_desc) echo '{{description}}';
		if ($caption_link_to_article) echo '</a>';
		if ($caption_show_readmore) echo "<span class=&quot;slideshow-readmore&quot;><a href={{readmore}}>{$readmore_txt}</a></span>";
		echo '"';
	}
	if ($show_overlay) {
		echo " data-cycle-overlay=\"#{$overlay_name}-{$module->id}\"";
		echo ' data-cycle-overlay-template="<div>';
		if ($overlay_show_title) echo '<h4>{{title}}</h4>'; 
		if ($overlay_show_desc) echo '<p>{{description}}</p>';
		if ($overlay_show_readmore) echo "<a href={{readmore}}>{$readmore_txt}</a>";
		echo '</div>"';
	}
	echo '>';

	if ($show_nav_buttons) {
		echo "<div class='{$prev_class}{$controls_hidden_phone}'></div>";
		echo "<div class='{$next_class}{$controls_hidden_phone}'></div>";
	}

	// Slides
	foreach ($slides as $slide)
	{
		if ($has_links)
		{
			echo "<a href='{$slide->url}'";
			switch ($link_target)
			{
			case 0:
				$slide_title = mb_substr($slide->url,strrpos($slide->url,'/')+1); // just the file name
				$slide_title = substr($slide_title, 0, strlen($slide_title)- strlen(strrchr($slide_title, '.'))); // remove suffix
				$slide_title = preg_replace ('/[0-9-\s]*(.*)/', '$1', $slide_title); // remove first numbering sequence (nnnn - )
				$slide_title = str_replace('_', ' ', $slide_title); // replace underline with space
				echo " title='{$slide_title}' {$fancybox_grouping}='{$slideshow_name}-{$module->id}' class='fancybox'";
				break;
			case 1:
				break;
			case 2:
				echo ' target="_blank"';
			default:
				break;
			}
		}
		else
		{
			echo "<img alt='' src='{$slide->getMainImage()}'";
		}
		if ($show_caption || $show_overlay)
		{
			if ($overlay_show_title || $caption_show_title) echo " data-title=\"{$slide->title}\"";
			if ($overlay_show_desc || $caption_show_desc) echo " data-description=\"{$slide->description}\"";
			if ($overlay_show_readmore || $caption_show_readmore || $caption_link_to_article) echo " data-readmore=\"{$slide->link}\"";
		}
		echo '>';
		if ($has_links)
		{
			echo "<img alt='' src='{$slide->getMainImage()}'>";
			echo '</a>';
		}
	}
	
	// Overlay
	if ($show_overlay) echo "<div id='{$overlay_name}-{$module->id}' class='{$overlay_class}{$overlay_hidden_phone}' style='{$overlay_css}'></div>";
	if ($pager_overlay) echo "<div id='{$pager_name}-{$module->id}' class='{$pager_class}{$controls_hidden_phone}' style='{$pager_css}'></div>";
}

echo '</div>'; // Main div

// "Below" divs
if ($pager_after) echo "<div style='width:100%;'><div id='{$pager_name}-{$module->id}' class='{$pager_class}{$controls_hidden_phone}' style='{$pager_css}'></div></div>";
if ($caption_after) echo "<div id='{$caption_name}-{$module->id}' class='{$caption_class}{$caption_hidden_phone}' style='{$caption_css}'></div>";
echo '</div>'; // Outer div
