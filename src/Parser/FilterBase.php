<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

abstract class FilterBase
{
	/**
	* 
	*
	* @return void
	*/
	public static function getFilter()
	{
		return array(
			'callback' => array(get_called_class(), 'filter'),
			'params'   => array('attrValue' => null)
		);
	}
}