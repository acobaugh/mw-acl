<?php

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
	global $wgOut, $wgUser;

	if ($action == 'acl') {
		$username = strtolower($wgUser->getName());
		$title = $wgArticle->getTitle();
		$ns = $title->getNamespace();

		$groups = $wgUser->getEffectiveGroups();
		$groups_pretty = print_r($groups, true);

		$page_acl = print_r(efACLTitleACL($title), true);
		$category_acl = print_r(efACLCategoryACL($title), true);
		$ns_acl = print_r(efACLNamespaceACL($title), true);

		$text = "* Username: $username\n";
		$text .= "* Groups:\n";
		$text .= "<pre>$groups_pretty</pre>\n";
		$text .= "* ACLs from this page:\n";
		$text .="<pre>$page_acl</pre>\n";
		$text .="* ACLs from categories:\n";
		$text .="<pre>$category_acl</pre>\n";
		$text .="* ACLs from namespace $ns :\n";
		$text .="<pre>$ns_acl</pre>\n";
		$wgOut->addWikiText($text);
	}

	return false;
}

?>
