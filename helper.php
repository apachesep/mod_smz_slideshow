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
			}
		}

		// Get images info
		$filename = $imagesDir  . '/' . trim($params->get('smz_slideshow_info_file', 'info.csv'));
		$basename = substr($filename, 0, strlen($filename) - strlen(strrchr($filename, '.')));
		$suffix = strstr($filename, '.');
		$lang1 = '.' . JFactory::getLanguage()->getTag();
		$lang2 = strstr($lang1, '-', true);
		$info = array();
		$title_field = trim($params->get('smz_slideshow_info_title_field', 'Title'));
		$description_field = trim($params->get('smz_slideshow_info_description_field', 'Description'));
		$id_field = trim($params->get('smz_slideshow_info_id_field', 'ID'));

		$handle = @fopen($basename . $lang1 . $suffix, 'r');
		if ($handle === false)
		{
			$handle = @fopen($basename . $lang2 . $suffix, 'r');
		}
		if ($handle === false)
		{
			$handle = @fopen($filename, 'r');
		}

		if ($handle)
		{
			// Read the first line as the $heading array (list of tags)
			if (is_array($heading = fgetcsv($handle)))
			{
				unset($heading[0]); // The first element ALWAYS is the filename

				// And trim the tag names
				$heading = array_map('trim', $heading);

				// Read the rest of the csv and build the $info array
				while (is_array($temp = fgetcsv($handle)))
				{
					$temp = array_map('trim', $temp);
					$key = $temp[0];
					unset($temp[0]);
					$info[$key] = $temp;
				}
				fclose($handle);

				// Now for each slide let's see if we have $info element
				foreach ($slides as $slide)
				{
					if (array_key_exists($slide->filename, $info))
					{
						// If we do, we move set the appropriate info to the slide object
						foreach ($heading as $key => $tag)
						{
							if (array_key_exists($key, $info[$slide->filename]))
							{
								$value = $info[$slide->filename][$key];
							}
							else
							{
								$value = '';
							}

							if ($tag == $title_field)
							{
								$slide->title = htmlentities($value, ENT_QUOTES);
							}
							else if ($tag == $description_field)
							{
								$slide->description = htmlentities($value, ENT_QUOTES);
							}
						}
					}
				}
			}
		}
		// End Get images info

		return $slides;
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
