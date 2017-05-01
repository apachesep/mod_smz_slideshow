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

/* Revision history:
 *
 * 3.5.0 - Jan 2015: Initial 3.5 minor release
 * 3.5.1 - Feb 2016: In loadImages() - Fixed URL (missing leading /) for link to self
 *
 */

defined('_JEXEC') or die;

class_exists('SMZImage') or require_once(__DIR__ . '/smzimage.class.php');

class SMZSlide extends stdClass {
	var $id = null;
	var $category = null;
	var $image = null;
	var $filename = null;
	var $title = null;
	var $description = null;
	var $link = null;
	var $url = null;
	var $params = null;


	public function __construct($params) {
		$this->params = $params;
	}


	public function loadArticle($id) {
		$article = JTable::getInstance("content");
		$article->load($id);
		$this->category = $article->get('catid');
		if(!class_exists('ContentHelperRoute'))
		{
			require_once(JPATH_SITE . '/components/com_content/helpers/route.php');
		}
		if ($article)
		{
			$this->title = $article->get('title');
			$image_source = $this->params->get('smz_slideshow_article_image_source', 1);
			$imageobj = json_decode($article->images);

			switch ($image_source)
			{
				case 1: //Intro Image
					$this->image = $imageobj->image_intro;
					break;
				case 2: //Full Image
					$this->image = $imageobj->image_fulltext;
					break;
				default: // First image in article
					$this->image = $this->getFirstImage($article->introtext . $article->fulltext);
			}

			$this->description = trim(preg_replace('/\s+/', ' ', $article->introtext));

			if ($this->params->get('smz_slideshow_prepare_content', 0))
			{
				JPluginHelper::importPlugin('content');
				$this->description = JHtml::_('content.prepare', $this->description, '', 'mod_smz_slideshow.content');
			}

			$this->description = htmlentities($this->description, ENT_COMPAT, "UTF-8", false);
			$this->link = JRoute::_(ContentHelperRoute::getArticleRoute($article->id, $article->catid));
			$this->id = $id;
			if ($this->params->get('smz_slideshow_image_link', 0))
			{
				$urls = json_decode($article->urls);
				switch ($this->params->get('smz_slideshow_article_image_link', 0))
				{
					case 1:
						$link = $this->getMainImage();
						break;
					case 2:
						$link = $this->link;
						break;
					case 3:
						$link = $urls->urla;
						break;
					case 4:
						$link = $urls->urlb;
						break;
					case 5:
						$link = $urls->urlc;
						break;
					default:
						$link = null;
				}
				if ($link != null)
				{
					$this->url = htmlspecialchars($link);
				}
				else
				{
					$this->url = null;
				}
			}
		}
	}


	public function loadImages($dir, $filename) {
		$this->filename = $filename;
		$this->title = $filename;
		$this->description = '';
		$this->image = $dir . '/' . $filename;
		$this->id = substr(md5($this->image), 0, 8);

		if ($this->params->get('smz_slideshow_image_link', 0) > 0)
		{
			$this->url = '/' . $this->image;
		}
		else
		{
			$this->url = null;
		}
	}


	function getFirstImage($str) {
		$str = strip_tags($str, '<img>');
		preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $str, $matches);
		if (isset($matches[1][0]))
		{
			return $image = $matches[1][0];
		}
		return '';
	}


	function getMainImage() {
		if (empty($this->image))
		{
			$this->image = JPATH_SITE . '/media/mod_smz_slideshow/images/no-image.jpg';
		}
		elseif (str_replace(array('http://', 'https://'), '', $this->image) != $this->image)
		{
			$imageArray = @getimagesize($this->image);
			if (!$imageArray[0])
			{
				$this->image = JPATH_SITE . '/media/mod_smz_slideshow/images/no-image.jpg';
			}
		}
		elseif (!file_exists($this->image))
		{
			$this->image = JPATH_SITE . '/media/mod_smz_slideshow/images/no-image.jpg';
		}

		$resize = $this->params->get('smz_slideshow_image_resize', 'fill');
		if ($resize == 'original')
		{
			return $this->image;
		}
		$width = $this->params->get('smz_slideshow_image_width');
		$height = $this->params->get('smz_slideshow_image_height');
		if (false === file_get_contents($this->image, 0, null, 0, 1))
		{
			$this->image = JPATH_SITE . '/media/mod_smz_slideshow/images/no-image.jpg';
		}
		$file = pathinfo($this->image);
		$background = $this->params->get('smz_slideshow_image_fit_bg','#000');
		$uniquename = substr(md5(implode('|', array($this->image, $resize, $background, $width, $height))), 0, 8) . '.' . $file['extension'];
		$cachefile = SMZ_SLIDESHOW_CACHE_FOLDER . '/' . $uniquename;
		if (!file_exists($cachefile))
		{
			$image = new SMZImage($this->image);
			switch ($resize)
			{
				case 'fill':
					$image->reFill($width, $height);
					break;
				case 'fit':
					$image->fit($width, $height, $background);
					break;
				case 'stretch':
					$image->resample($width, $height, false);
					break;
			}
			$image->save($cachefile);
		}
		return SMZ_SLIDESHOW_CACHE_URL . $uniquename;
	}

}
