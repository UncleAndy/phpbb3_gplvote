<?php
/**
*
* @package mytest
* @copyright (c) 2015 Andrey Velikoredchanin
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
*
*/

namespace gplvote\signdoc\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
/**
* Assign functions defined in this class to event listeners in the core
*
* @return array
* @static
* @access public
*/
	static public function getSubscribedEvents()
	{
		return array();
	}
}
