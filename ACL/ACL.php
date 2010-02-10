<?php

/* 
 * Some definitions
 * entity: a user or group
 * acl: the complete entity->right list
 * right/bit: r, w, etc
 */

/* tag used on pages to define acls */
$wgACLTag = 'acl';

/* this defines acl inheritance, ie 'foo' also implies 'bar' */
$wgACLRightInheritance = array(
	'r' => array('h',	'w'),
	'h' => array('r',	'w'),
	'w' => array('r',	'h'),
	'e' => array('r',	'h', 'w', 'p'),
	'p' => array('r',	'w', 'e', 'h',	'p'),
	'd' => array('r',	'e', 'h', 'm', 'p', 'w'),
	'm' => array('r',	'e', 'h', 'm',	'w', 'p'),
	'a' => array('r', 'e', 'h', 'd', 'm', 'p', 'w')
);

/* these are all the bits that we will interpret */
$wgACLAllowedBits = array(
	'r', 'e', 'm', 'h', 'd', 'p', 'w', 'a'
);

/* same as above, but the negative bits */
$wgACLAllowedNegativeBits = array(
	'R', 'E', 'M', 'H', 'D', 'P', 'W', 'A'
);

$wgExtensionCredits['other'][] = array(
	'name' => "ACL",
	'description' => "Provides fine grained per-page access control lists based on usernames and groups.",
	'version' => "1.0",
	'author' => "Andy Cobaugh (phalenor@bx.psu.edu)",
	'url' => "http://bx.psu.edu/"
);

/* push ourselves onto the extension function stack */
$wgExtensionFunctions[] = 'efACLParserSetup';

/* control edit access */
$wgHooks['AlternateEdit'][] = 'efACLHookAlternateEdit';

/* control any other access */
$wgHooks['userCan'][] = 'efACLHookuserCan';

/* acl tab functionality */
include_once('ACLTab.php');

/* sets up the extension 
 * hooks in our parser hook for $wgACLTag
 */
function efACLParserSetup()
{
	global $wgParser, $wgACLTag;

	/* hook us into the parser if $wgACLTag is present */
	$wgParser->setHook($wgACLTag, 'efACLParserHook');
}

/* hook to be called when $wgParser is set up
 * also disables the cache
 */
function efACLParserHook($input, $args, &$parser)
{
	$parser->disableCache();
	// do stuff
}

/* control edit access. used sometimes in place of userCan */
function efACLHookAlternateEdit(&$editpage)
{
	return true;
}

/* control any other access */
function efACLHookuserCan(&$title, &$user, $action, &$result)
{
	#$page_rights = efACLCumulativeRights($title);

	$username = strtolower($user->mName);

	switch ($action)
	{
	case 'read':
		break;
	case 'edit':
		//stuff
		break;
	case 'move':
		//stuff
		break;
	case 'history':
		//stuff
		break;
	case 'delete':
		//stuff
		break;
	case 'move':
		//stuff
		break;
	case 'protect':
		//stuff
		break;
	case 'watch':
		//stuff
		break;
	case 'acl':
		//stuff
		break;
	}
	return true;
}

/* returns an array of entity=>bits for $article
 * Array
 * (
 * 	['entity'] => Array
 * 					(
 * 						[0] => 'r'
 * 						[1] => 'e'
 * 						...
 * 					)
 * 	...
 * )
 *
 * This returns both positive and negative acls, and takes into account empty bit fields
 */
function efACLExtractACL($title) {
	global $wgACLtag, $wgACLAllowedBits, $wgACLAllowedNegativeBits, $wgACLimplicitBits;
	
	$article = new Article($title, 0);
	$content = $article->getContent();

	$acl_string = "";
	$acl = array();

	/* scan $content for <acl>(.*)</acl> */
	if (preg_match_all("/<acl>(.*)<\/acl>/", $content, $acl_tag_matches, PREG_SET_ORDER)) {
		/* combine all <acl> tag matches into one string, separated by commas */
		foreach ($acl_tag_matches as $match) {
			$acl_string .= $match[1] . $wgACLdelim;
		}
		/* process each entry */
		foreach (split($wgACLdelim, $acl_string) as $entry) {
			if (!empty($entry)) {
				/* split this acl string entry to $entity and $bits */
				if (strpos($entry, $wgACLentryDelim)) {
					list($entity, $bits) = split($wgACLentryDelim, $entry);
				} else {
					$entity = $entry;
					$bits = "";
				}
				/* trim excess whitespace */
				$entity = trim($entity);
				$bits = trim($bits);
				
				/* not specifying any bits  */
				if (!isset($bits)) {
					$bits = $wgACLimplicitBits;
				}

				/* put only the allowed bits into $acl[$entity] */	
				$bits_array = str_split($bits);
				foreach ($bits_array as $bit) {
					if (!empty($bit) 
						&& ( (in_array($bit, $wgACLAllowedBits) || in_array($bit_flag, $wgACLAllowedNegativeBits)) )) 
					{
						$acl[$entity][] = $bit;
					}
				}
				/* handle the case where we only specified negative acls, and need to 
				 * include $wgACLimplicitBits
				 */
				foreach ($acl as $entity => $bits)
				{
					if (count(array_intersect($bits, $wgACLAllowedBits)) == 0)
					{
						$acl[$entity][] .= $wgACLimplicitBits;
					}
				}

			} /* end if */
		} /* end foreach */
	} /* end preg_match_all */

	/* reduce duplicate bits */
	$acl = efACLDeDuplicateBits($acl);

	return $acl;
}

/* collapse duplicate bits */
function efACLDeDuplicateBits($acl)
{
		foreach ($acl as $entity => $bits)
		{
			$acl[$entity] = array_unique($bits);
		}

		return $acl;
}

/* expand based on inheritance */
function efACLInheritance($acl)
{
	global $wgACLAllowedBits, $wgACLRightInheritance;

	foreach ($acl as $entity => $bits)
	{
		if (count($bits) > 0)
		{
			foreach ($bits as $bit)
			{
				if (in_array($bit, $wgACLAllowedBits))
				{
					$return_acl[$entity] = array_merge($return_acl[$entity], $wgACLRightInheritance[$bit]);
				}
			}
		}
	}

	return $acl;
}

/* subtract negative bits */
function efACLNegativeACL($acl)
{
	global $wgACLAllowedNegativeBits;

	foreach ($acl as $entity => $bits)
	{
		$negative_bits = array_intersect($bits, $wgACLAllowedNegativeBits);
		$positive_bits = array_diff($access, $wgACLAllowedNegativeBits);
		foreach ($negative_bits as $negative_bit)
		{
			$negative_bit = strtolower($negative_bit);
			$negative_bit_array = array("$negative_bit");
			$acl[$entity] = array_diff($positive_bits, $negative_bit_array);
		}
	}

	return $acl;
}

/* just combines some functions to get the effective acl
 * for a particular title
 */

function efACLEffectiveACL($title)
{
	//$acl = efACLExtractACL($title);
	$acl = efACLInheritance($acl);
	$acl = efACLNegativeACL($acl);
	$acl = efACLDeDuplicate($acl);

	return $acl;
}

/* return effective acls with entity-level precedence
 * any entities that exist in $lower that don't exist in 
 * $higher are passed through.
 */
function efACLPrecedence ($higher, $lower)
{
	foreach ($lower as $key => $value)
	{
		$higher[$key] = $value;
	}

	return $lower;
}

/* return effective acls by adding */
function efACLAdd($one, $two)
{
	$return_acl = array();

	foreach ($one as $key => $value)
	{
		$return_acl[$key][] = $value;
	}
	
	foreach ($two as $key => $value)
	{
		$return_acl[$key][] = $value;
	}

	$return_acl = efACLDeDuplicate($return_acl);
	return $return_acl;
}

function efACLCumulativeRights($title)
{
	$category_rights = array();

	$page_rights = efACLExtractRights($title);
	
	$category_tree = $title->getParentCategoryTree();
	if (count($category_tree) > 0)
	{
		$category_tree_flat = efACLFlattenCategoryTree($category_tree);
		foreach ($category_tree_flat as $category)
		{
			$category_title = Title::newFromText($category);
			$category_rights = efACLRightsAdditive($category_rights, efACLExtractRights($category_title));
		}
		$page_rights = efACLRightsPrecedence($page_rights, $category_rights);
	}

	$page_rights = efACLEffectiveRights($page_rights);

	return $page_rights;
}

/* this will recursively flatten a tree of categories returned by getParentCategoryTree() */
function efACLFlattenCategoryTree($category_tree)
{
	$keys = array();

	foreach($category_tree as $k => $v)
	{
		$keys[] = $k;
		if (is_array($category_tree[$k]) && (count($category_tree[$k]) > 0))
		{
			$keys = array_merge($keys, efACLFlattenCategoryTree($category_tree[$k]));
		}
		else
		{
			return array($k);
		}
	}
	return $keys;
}

