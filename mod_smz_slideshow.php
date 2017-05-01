<?php
/**
 * @package smz_slideshow
 * @version 3.6
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

// Set-up the cache folder
defined('SMZ_SLIDESHOW_CACHE_FOLDER') or define('SMZ_SLIDESHOW_CACHE_FOLDER', JPATH_SITE . '/cache/smz_slideshow');
defined('SMZ_SLIDESHOW_CACHE_URL') or define('SMZ_SLIDESHOW_CACHE_URL', JUri::base(true) . '/cache/smz_slideshow/');
file_exists(SMZ_SLIDESHOW_CACHE_FOLDER) or @mkdir(SMZ_SLIDESHOW_CACHE_FOLDER, 0755) or die(JText::_('MOD_SMZ_SLIDESHOW_NO_CACHE'));
if(!file_exists(SMZ_SLIDESHOW_CACHE_FOLDER . '/index.html'))
{
	$fh = fopen(SMZ_SLIDESHOW_CACHE_FOLDER . '/index.html', 'w+')or die(JText::_('MOD_SMZ_SLIDESHOW_NO_CACHE'));
	fwrite($fh, '<!DOCTYPE html><title></title>');
	fclose($fh);
}

// Get parameters
$slideshow_name = 'smz-slideshow';
$moduleclass_sfx = $params->get('moduleclass_sfx');
$effect = $params->get('smz_slideshow_transition', 'none');
$speed = $effect == 'none' ? 1 : max($params->get('smz_slideshow_transition_speed', 500), 1);
$responsive = $params->get('smz_slideshow_responsive', 1);
$slideshow_width = $params->get('smz_slideshow_width', 1);
$timeout = $params->get('smz_slideshow_auto', 1) == 1 ? $params->get('smz_slideshow_timeout', 0) : 0;
$pause_onhover = $params->get('smz_slideshow_pause_onhover', 0) == 0 ? 'false' : 'true';
$show_nav_buttons = $params->get('smz_slideshow_show_nav_buttons', 0);
$allow_swipe = $params->get('smz_slideshow_allow_swipe', 0) == 0 ? 'false' : 'true';
$controls_hidden_phone = $params->get('smz_slideshow_controls_hidden_phone', 0) == 0 ? '' : ' hidden-phone';
$slideshow_center = $params->get('smz_slideshow_center', 0) == 0 ? '' : 'margin: 0 auto;';
$layout = $params->get('layout', 'default');
$slidesource = $params->get('slides_source', 0);
$has_links = $params->get('smz_slideshow_image_link', 0);
$link_target = $params->get('smz_slideshow_image_link_target', 0);

// Load CSS
JHtml::stylesheet('mod_smz_slideshow/mod_smz_slideshow.css', array(), true);
$temp = explode(':', $layout);
$layoutname = ($temp[1]) ? $temp[1] : 'default';
if ($layoutname != 'default')
{
	JHtml::stylesheet('mod_smz_slideshow/mod_smz_slideshow_' . $layoutname . '.css', array(), true);
}

// Load jQuery
JHtml::_('jquery.framework');

// Load the main JS
JHtml::script('mod_smz_slideshow/jquery.cycle2.min.js', false, true, false );
JHtml::script('mod_smz_slideshow/jquery.cycle2.swipe.min.js', false, true, false );
JHtml::script('mod_smz_slideshow/jquery.cycle2.center.min.js', false, true, false );
JHtml::script('mod_smz_slideshow/ios6fix.min.js', false, true, false );

class_exists('SMZSlide') or require_once(__DIR__ . '/classes/slide.php');

require_once(__DIR__ . '/helper.php');

$params->set('moduleID', $module->id);

$slides = modSMZSlideShowHelper::getSlides($params);

require JModuleHelper::getLayoutPath('mod_smz_slideshow', $layout);
