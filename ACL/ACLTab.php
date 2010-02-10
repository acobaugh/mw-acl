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

	if ($action == 'acl')
	{
		$content = $wgArticle->getContent();
		$title = $wgArticle->getTitle();
		$username = strtolower($wgUser->getName());

		$groups = $wgUser->getEffectiveGroups();
		$groups_pretty = print_r($groups, true);

		$acl = efACLExtractACL($title);
		$acl = efACLEffectiveACL($acl);
		$acl_pretty = print_r($acl, true);

		$text = "* Username: $username\n";
		$text .= "* Groups:\n";
		$text .= "<pre>$groups_pretty</pre>";
		$text .= "* Rights from this page:\n";
		$text .="<pre>$acl_pretty</pre>\n";

		$category_tree = $title->getParentCategoryTree();
		$category_tree_flat = efACLFlattenCategoryTree($category_tree);

		foreach ($category_tree_flat as $category)
		{
			$text .="* Rights from [[:$category]]\n";
			
			$mytitle = Title::newFromText($category);
			$myarticle = new Article($mytitle, 0);
			$myacl = efACLExtractACL($mytitle);
			$myacl = efACLEffectiveACL($myacl);
			$acl_pretty = print_r($myacl, true);

			$text .="<pre>$acl_pretty</pre>\n";
		}

		$wgOut->addWikiText($text);
	}

	return false;
}

?>
