<?php

if (!defined( 'MEDIAWIKI' )) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

$wgExtensionCredits['other'][] = array(
	'name' => "ACL",
	'description' => "Provides fine grained per-page, per-namespace, and per-category access control lists based on usernames and groups.",
	'version' => "1.0",
	'author' => "Andy Cobaugh (phalenor@bx.psu.edu)",
	'url' => "http://github.com/phalenor/mw-acl"
);

/* 
 * Some definitions
 * entity: a user or group
 * acl: the complete entity->right list
 * right/bit: r, w, etc
 */

/* tag used on pages to define acls */
$wgACLTag = 'acl';

/*
 * the code assumes there could be duplicate
 * delimiters in series by accident
 */

/* used to delimit entity->right objects */
$wgACLDelimiter = ',';

/* NS:$wgACLNamespaceACLPage will hold acls for NS */
$wgACLNamespaceACLPage = 'ACL';

/* used to delimit entities from rights */
$wgACLEntityBitDelimiter = ' '; 

/* this defines acl inheritance, ie 'foo' also implies 'bar' */
$wgACLRightInheritance = array(
	'r' => array('h', 'w'),
	'h' => array('r', 'w'),
	'w' => array('r', 'h'),
	'e' => array('r', 'h', 'w', 'p'),
	'p' => array('r', 'w', 'e', 'h',	'p'),
	'd' => array('r', 'e', 'h', 'm', 'p', 'w'),
	'm' => array('r', 'e', 'h', 'm',	'w', 'p'),
	'a' => array('r', 'e', 'h', 'd', 'm', 'p', 'w')
);

$wgACLNames = array(
	'r' => 'read',
	'h' => 'history',
	'w' => 'watch',
	'e' => 'edit',
	'p' => 'protect',
	'd' => 'delete',
	'm' => 'move',
	'a' => 'all / administer'
);

/* which rights are implied if none are specified */
$wgACLImplicitBits = 're';

/* these are all the bits that we will interpret */
$wgACLAllowedBits = array_keys($wgACLRightInheritance);

/* same as above, but the negative bits */
$wgACLAllowedNegativeBits = array_keys(array_change_key_case($wgACLRightInheritance, CASE_UPPER));

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
	$acl = efACLCumulativeACL($title);
	$effective_acl[0] = array();

	/* returning true means other userCan hooks can still make decisions
	 * if there are no acls at all for this page, allow the rest of 
	 * the system to decide whether to allow or deny 
	 */
	if (count($acl) < 0) {
		return true;
	}
	$username = strtolower($user->getName());
	$groups = $user->getEffectiveGroups();
	
	/* add up acls for every group this user is a member of */
	foreach ($groups as $group) {
		if (in_array($group, array_keys($acl))) {
			$effective_acl[0] = array_merge($effective_acl[0], $acl[$group]);
		}
	}
	/* de-duplicate and apply negative acls */
	efACLNegativeACL(efACLDeDuplicateBits($effective_acl));

	/* now apply user-specific acls */
	if (in_array($username, array_keys($acl))) {
		$effective_acl = array_merge($effective_acl[0], $acl[$username]);
		efACLNegativeACL(efACLDeDuplicateBits($effective_acl));
	}
	

	/* if user has no bits to this page */
	if (count($effective_acl[0]) == 0) {
		$return = false;
		return false;
	}
	
	switch ($action) {
		case 'read':
			if (in_array('r', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			break;
		case 'edit':
			if (in_array('e', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			break;
		case 'move':
			if (in_array('m', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			//stuff
			break;
		case 'history':
			if (in_array('h', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			//stuff
			break;
		case 'delete':
			if (in_array('d', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			//stuff
			break;
		case 'move':
			if (in_array('m', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			//stuff
			break;
		case 'protect':
			if (in_array('p', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			//stuff
			break;
		case 'watch':
			if (in_array('w', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			//stuff
			break;
		case 'acl':
			if (in_array('r', $effective_acl[0])) {
				$return = true;
			} else {
				$return = false;
			}
			return false;
			break;
		default:
			$return = false;
			return false;
	}
	return true;
}

/* returns an array of entity=>bits for $title
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
	global $wgACLTag, $wgACLAllowedBits, $wgACLAllowedNegativeBits, $wgACLImplicitBits, $wgACLDelimiter, $wgACLEntityBitDelimiter;
	
	$article = new Article($title, 0);
	$content = $article->getContent();

	$acl_string = "";
	$acl = array();

	/* scan $content for <acl>(.*)</acl> */
#	$match_string = "/<$wgACLTag>(.*
	if (preg_match_all("/<$wgACLTag>(.*)<\/$wgACLTag>/", $content, $acl_tag_matches, PREG_SET_ORDER)) {
		/* combine all <acl> tag matches into one string, separated by commas */
		foreach ($acl_tag_matches as $match) {
			$acl_string .= $match[1] . $wgACLDelimiter;
		}
		/* process each entry */
		foreach (split($wgACLDelimiter, $acl_string) as $entry) {
			if (!empty($entry)) {
				/* split this acl string entry to $entity and $bits */
				if (strpos(trim($entry), $wgACLEntityBitDelimiter)) {
					$entry = eregi_replace("$wgACLEntityBitDelimiter+", "$wgACLEntityBitDelimiter", $entry);
					list($entity, $bits) = split($wgACLEntityBitDelimiter, trim($entry));
				} else {
					$entity = $entry;
					$bits = "";
				}
				/* trim excess whitespace */
				$entity = trim($entity);
				$bits = trim($bits);
				
				/* not specifying any bits implies all bits */
				if (!isset($bits)) {
					$bits = $wgACLImplicitBits;
				}

				/* put only the allowed bits into $acl[$entity] */	
				foreach (str_split($bits) as $bit) {
					if (!empty($bit) && (in_array($bit, $wgACLAllowedBits) || in_array($bit, $wgACLAllowedNegativeBits))) {
						$acl[$entity][] = $bit;
					}
				}
				/* handle the case where we only specified negative acls, and need to 
				 * include $wgACLimplicitBits
				 */
				foreach ($acl as $entity => $bits) {
					if (count(array_intersect($bits, $wgACLAllowedBits)) == 0) {
						$acl[$entity][] .= str_split($wgACLImplicitBits);
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
function efACLDeDuplicateBits($acl) {
	foreach ($acl as $entity => $bits) {
		if (is_array($bits)) {
			$acl[$entity] = array_unique($bits);
		}
	}
	return $acl;
}

/* expand based on inheritance */
function efACLInheritance($acl) {
	global $wgACLAllowedBits, $wgACLRightInheritance;

	$return_acl = array();

	foreach ($acl as $entity => $bits) {
		if (count($bits) > 0) {
			foreach ($bits as $bit)	{
				if (!array_key_exists($entity, $return_acl)) {
					$return_acl[$entity] = array();
				}
				if (array_key_exists($bit, $wgACLRightInheritance)) {
					$return_acl[$entity] = array_merge($return_acl[$entity], $wgACLRightInheritance[$bit]);
				}
			}
		}
		$return_acl[$entity] = array_merge($return_acl[$entity], $bits);
	}

	return $return_acl;
}

/* subtract negative bits */
function efACLNegativeACL($acl) {
	global $wgACLAllowedNegativeBits;

	/* loop over each entity->bit */
	foreach ($acl as $entity => $bits) {
		if (is_array($bits)) {
			/* find the negative bits */
			$negative_bits = array_intersect($bits, $wgACLAllowedNegativeBits);
			/* find the positive bits */
			$positive_bits = array_diff($bits, $wgACLAllowedNegativeBits);

			/* loop over each negative bit */
			foreach ($negative_bits as $negative_bit) {
				/* remove strtolower($negative_bit) from $positive_bits */
				$negative_bit = strtolower($negative_bit);
				$acl[$entity] = array_diff($positive_bits, array($negative_bit));
			}
		}
	}
	return $acl;
}

/* 
 * just combines some functions to get the effective acl
 * for a particular $title
 */

function efACLTitleACL($title)
{
	$acl = efACLExtractACL($title);
	$acl = efACLInheritance($acl);
//	$acl = efACLNegativeACL($acl);
	$acl = efACLDeDuplicateBits($acl);

	return $acl;
}

/* 
 * find the final acls for $title 
 * based on category, namespace, and page acls
 */
function efACLCumulativeACL($title)
{
	$acl = array();

	$title_acl = efACLTitleACL($title);
	$category_acl = efACLCategoryACL($title);
	$ns_acl = efACLNamespaceACL($title);

	/* hard-coded order in which we combine acls */
	$acl = efACLNegativeACL($category_acl);
	$acl = efACLNegativeACL(efACLAddACL($acl, $ns_acl));
	$acl = efACLAddACL($acl, $title_acl);

	return $acl;

}

/* 
 * returns cumulative acls from the categories of $title
 */
function efACLCategoryACL($title) {
	$category_acl = array();

	$category_tree = $title->getParentCategoryTree();
	
	if (count($category_tree) > 0) {
		$category_tree_flat = efACLFlattenCategoryTree($category_tree);
		foreach ($category_tree_flat as $category) {
			$category_title = Title::newFromText($category);
			$category_acl = efACLAddACL($category_acl, efACLTitleACL($category_title));
		}
	}
	return $category_acl;
}

/* 
 * return ACL for the NS that $title is in
 */
function efACLNamespaceACL($title) {
	global $wgACLNamespaceACLPage;

	$ns = $title->getNsText();
	if ($ns) {
		$ns_acl_title = Title::newFromText("$ns:$wgACLNamespaceACLPage");
		return efACLTitleACL($ns_acl_title);
	} else {
		return array();
	}
}


/* adds acls together, with deduplication */
function efACLAddACL($acl1, $acl2) {
	foreach ($acl1 as $entity => $bits) {
		if (array_key_exists($entity, $acl2)) {
			if (!is_array($acl2[$entity])) {
				$acl2[$entity] = array();
			}
			$acl2[$entity] = array_merge($acl2[$entity], $bits);
		}
	}
	return efACLDeDuplicateBits($acl2);
}

/* this will recursively flatten a tree of categories returned by getParentCategoryTree() */
function efACLFlattenCategoryTree($category_tree) {
	$keys = array();

	foreach($category_tree as $k => $v)	{
		array_push($keys, $k);
		if (is_array($category_tree[$k]) && (count($category_tree[$k]) > 0))	{
			$keys = array_merge($keys, efACLFlattenCategoryTree($category_tree[$k]));
		}
	}
	return $keys;
}

