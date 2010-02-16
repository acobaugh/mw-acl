<?php

if (!defined( 'MEDIAWIKI' )) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

$wgExtensionFunctions[] = 'efACLContentTabSetup';

/* add hooks, general setup */
function efACLContentTabSetup()
{
	global $wgHooks, $wgMessageCache;

	$wgMessageCache->addMessage('acl', 'acl');

	$wgHooks['SkinTemplateContentActions'][] = 'efACLHookContentTab';
	$wgHooks['UnknownAction'][] = 'efACLDisplayTab';
}

/* hook that adds us as a ContentAction (tab) */
function efACLHookContentTab(&$content_actions)
{
	global $wgRequest, $wgTitle;

	$action = $wgRequest->getText('action');
	if ($wgTitle->getNamespace() != NS_SPECIAL)
	{
		$content_actions['acl'] = array(
			'class' => $action == 'acl' ? 'selected' : false,
			'text' => wfMsg('acl'),
			'href' => $wgTitle->getLocalUrl('action=acl')
		);
	}

	return true;
}

/* display the tab */
function efACLDisplayTab($action, &$wgArticle)
{
	global $wgOut, $wgUser, $wgACLNames;

	if ($action == 'acl') {
		
		$username = strtolower($wgUser->getName());
		$title = $wgArticle->getTitle();
		$ns_text = $title->getNsText();
		$ns = $title->getNamespace();
		$titleText = $title->getEscapedText();

		$wgOut->setPageTitle("ACLs for $titleText");

		$groups = $wgUser->getEffectiveGroups();
		sort($groups);

		$page_acl = efACLTitleACL($title);
		$category_acl = efACLCategoryACL($title);
		$ns_acl = efACLNamespaceACL($title);

		$text = "<div style=\"float:right; clear:right;margin-left: 1em; background-color:#f3f3ff; border:1px solid; padding-left: 0.5em; padding-right: 0.5em\">\n";
		$text .= "'''Key'''\n";
		foreach ($wgACLNames as $k => $v) {
			$text .= "* ''$k'' = $v\n";
		}
		$text .= "</div>\n";

		$text .= "== User ==\n";
		$text .= "* Username: $username\n";
		$text .= "* Groups: ";
		$text .= implode(', ', $groups);
		$text .= "\n";

		$text .= "== Effective ACLs ==\n";
		$text .= efACLWikiTextACL(efACLCumulativeACL($title), 1);
		$text .= "== Page ACLs ==\n";
		$text .= "ACLs from this page:\n";
		$text .= efACLWikiTextACL($page_acl, 1);

		$text .= "== Namespace ACLs ==\n";
		if ($ns == NS_MAIN) {
			$ns_text = 'Main';
		}
		$text .="ACLs from namespace '''$ns_text''' :\n\n";
		$text .= efACLWikiTextACL($ns_acl, 1);

		$text .= "== Category ACLs ==\n";
		$text .="Total ACLs from all categories:\n\n";
		$text .= efACLWikiTextACL($category_acl, 1);
		
		$category_tree = $title->getParentCategoryTree();
		if (count($category_tree) > 0) {
			$category_tree_flat = efACLFlattenCategoryTree($category_tree);
			foreach ($category_tree_flat as $category) {
				$category_title = Title::newFromText($category);
				$text .= "=== $category ===\n";
				$text .= efACLWikiTextACL(efACLTitleACL($category_title));
			}
		}
		
		$wgOut->addWikiText($text);
	}

	return false;
}

function efACLWikiTextACL($acl, $indent = 0) {
	$text = '';
	$prefix = '';

	if (count($acl) > 0) {
		if ($indent > 0) {
			for ($i = 0; $i < $indent; $i++) {
				$prefix .= '*';
			}
		}

		foreach ($acl as $entity => $value) {
			$text .= $prefix . $entity  . ' : ' . implode($value) . "\n";
		}
	} else {
		$text .= $prefix . "(none)\n";
	}
	
	return $text;
}
		
?>
