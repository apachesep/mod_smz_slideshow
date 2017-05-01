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

class modSMZSlideShowHelper {

	static function getSlides($params) {
		$slidesource = $params->get('slides_source', 0);
		if ($slidesource == 0)
		{
			return self::getSlidesFromFolder($params);
		}
		else
		{
			return self::getSlidesFromContent($params);
		}
	}

	static function getSlidesFromFolder($params){
		$slides = array();
		$dir = trim($params->get('smz_slideshow_folder_image', 'images'), " \t\n\r\0\x0B\\/");
		$limit = $params->get('smz_slideshow_count', 0);
		if (is_dir(JPATH_SITE . '/' . $dir))
		{
			$imagesDir = JPATH_SITE . '/' . $dir .'/';
			$images = glob($imagesDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
			if (!empty($images))
			{
				foreach ($images as $i=>$image)
				{
					if ($limit > 0 && $limit <= $i)
					{
						break;
					}
					$tmp = pathinfo($image);
					$slide = new SMZSlide($params);
					$slide->loadImages($dir, $tmp['basename']);
					$slides[] = $slide;
				}

				// Get images info
				$handle = @fopen($imagesDir  . '/' . trim($params->get('smz_slideshow_info_file', 'info.txt')), 'r');
				if ($handle)
				{
					$titles = array();
					$descriptions = array();
					$ids = array();
					
					while (($ln = self::readline($handle, true, true)) !== false)
					{
						$ln_array = explode($params->get('smz_slideshow_info_file_separator', ','), $ln);
						if (array_key_exists(0, $ln_array) && array_key_exists(1, $ln_array))
						{
							foreach ($ln_array as $i => $v)
							{
								$v = trim(strip_tags($v), " \t\n\r\0\x0B\"");
								$ln_array[$i] = $v;
							}
							if (!empty($ln_array[1]))
							{
								$titles[$ln_array[0]] = $ln_array[1];
							}
							if (!empty($ln_array[2]))
							{
								$descriptions[$ln_array[0]] = $ln_array[2];
							}
							if (!empty($ln_array[3]))
							{
								$ids[$ln_array[0]] = $ln_array[3];
							}
						}
					}
					fclose($handle);

					foreach($slides as $slide)
					{
						if (array_key_exists($slide->filename, $titles))
						{
							$slide->title = htmlentities($titles[$slide->filename], ENT_QUOTES);
						}
						if (array_key_exists($slide->filename, $descriptions))
						{
							$slide->description = htmlentities($descriptions[$slide->filename], ENT_QUOTES);
						}
						if (array_key_exists($slide->filename, $ids))
						{
							$slide->id = htmlentities($ids[$slide->filename], ENT_QUOTES);
						}
					}
				}
				// End Get images info
				
			}
		}
		return $slides;
	}

	// Read a "clean" line from file
	static function readline($handle, $trim=false, $strip_tags=false) {
		$ln = false;

		if ($handle)
		{
			if (($ln = fgets($handle)) === false)
			{
				return false;
			}

			if (!mb_detect_encoding($ln, 'UTF-8', true))
			{
				$ln = utf8_encode($ln);
			}

			if ($strip_tags)
			{
				$ln = trim(strip_tags($ln));
			}

			if ($trim)
			{
				$ln = trim($ln);
			}
		}

		return $ln;
	}

	static function getSlidesFromContent($params) {
		$slides = array();
		$slidesource = $params->get('slides_source', 1);
		$limit = $params->get('smz_slideshow_count', 0);
		$orderby = $params->get('smz_slideshow_orderby',1);
		$ordering = $params->get('smz_slideshow_ordering','ASC');
		
		switch ($orderby)
		{
			case 1:
				$order = 'c.ordering';
				break;
			case 2:
				$order = 'c.title';
				break;
			case 3:
				$order = 'c.created';
				break;
			case 4:
				$order = 'c.modified';
				break;
			case 5:
				$order = 'c.id';
				break;
			default:
				$order = 'c.id';
		}
		$order .= ' '.$ordering;

		switch ($slidesource)
		{
			case 1: // Article by category
				$select = "id";
				$table = "#__content";
				$leftjoin = null;
				$selector = $params->get('smz_slideshow_categories', array());
				$selector_field = "c.catid";
				$condition = "c.state > 0";
				$featured = ($params->get('smz_slideshow_featuredonly', '1') == 1) ? "c.featured = 1" : null;
				if ($params->get('smz_slideshow_categories_children', '1') == 1)
				{
					$selector = self::GetCategoriesWithChildren($selector);
				}
				$SlideLoadingFunc = "loadArticle";
				break;
			case 2: // Article by ID
				$select = "id";
				$table = "#__content";
				$leftjoin = null;
				$selector = $params->get('smz_slideshow_article_ids', '');
				$selector_field = "c.id";
				$condition = "c.state > 0";
				$featured = null;
				$SlideLoadingFunc = "loadArticle";
				break;
		}

		if (is_array($selector))
		{
			$selector = implode(',', $selector);
		}

		$selector = str_replace(' ', '', $selector);

		if (empty($selector))
		{
			return $slides;
		}
		
		if (!is_null($table))
		{
			if (is_numeric($selector))
			{
				$selector = $selector_field . " = " . $selector;
			}
			else
			{
				$selector = $selector_field . " IN(" . $selector . ")";
			}
		
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select("c.".$select);
			$query->from($table." AS c");

			if (is_array($leftjoin)) {
				foreach ($leftjoin as $join) {
					$query->join('LEFT', $join);
				}
			}

			if (!is_null($condition))
			{
				$query->where($condition);
			}

			if (!is_null($featured))
			{
				$query->where($featured);
			}

			if (!is_null($selector))
			{
				$query->where($selector);
			}

			if (!is_null($order))
			{
				$query->order($order);
			}

			if ($limit > 0)
			{
				$db->setQuery($query, 0, $limit);
			}
			else
			{
				$db->setQuery($query);
			}
	
			$rows = $db->loadObjectList();
	
			if (empty($rows))
			{
				return $slides;
			}
	
			foreach ($rows as $row)
			{
				$slide = new SMZSlide($params);
				$slide->$SlideLoadingFunc($row->$select);
				if ($slide->image)
				{
					$slides[] = $slide;
				}
			}
		}
		else
		{
			$selector = explode(',', $selector);
			foreach ($selector as $iselect)
			{
				$slide = new SMZSlide($params);
				$slide->$SlideLoadingFunc($select);
				if ($slide->image)
				{
					$slides[] = $slide;
				}
			}
		}

		return $slides;

	}


	static function getTemplate(){
		$db=JFactory::getDBO();
		$query=$db->getQuery(true);
		$query->select('*');
		$query->from('#__template_styles');
		$query->where('home=1');
		$query->where('client_id=0');
		$db->setQuery($query);
		return $db->loadObject()->template;
	}


	static function GetCategoriesWithChildren($categories) {
		$results = array();
		$db = JFactory::getDbo();
		foreach ($categories as $baseCategory)
		{
			$query = $db->getQuery(true);
			$query->select('c.path');
			$query->from('#__categories AS c');
			$query->where('c.published > 0');
			$query->where('c.id = ' . $baseCategory);
			$db->setQuery($query);
			$fathersList = $db->loadObjectList();
			foreach ($fathersList as $father)
			{
				$results[] = $baseCategory;
				$query = $db->getQuery(true);
				$query->select('c.id');
				$query->from('#__categories AS c');
				$query->where('c.published > 0');
				$query->where('c.path LIKE \'' . $father->path . '/%\'');
				$db->setQuery($query);
				$children = $db->loadObjectList();
				foreach ($children as $category)
				{
					$results[] = $category->id;
				}
			}
		}
		return $results;
	}

}
