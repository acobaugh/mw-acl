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
		$titleText = $title->getEscapedText();

		$wgOut->setPageTitle("ACLs for $titleText");

		$groups = $wgUser->getEffectiveGroups();
		sort($groups);

		$page_acl = efACLTitleACL($title);
		$category_acl = efACLCategoryACL($title);
		$ns_acl = efACLNamespaceACL($title);

		$text = "== User ==\n";
		$text .= "* Username: $username\n";
		$text .= "* Groups:\n";
		$text .="<pre>";
		$text .= implode(', ', $groups);
		$text .="</pre>\n";

		$text .= "== Page ACLs ==\n";
		$text .= "ACLs from this page:\n";
		$text .= efACLWikiTextACL($page_acl, 1);

		$text .= "== Namespace ACLs ==\n";
		$text .="ACLs from namespace $ns :\n";
		$text .= efACLWikiTextACL($ns_acl, 1);

		$text .= "== Category ACLs ==\n";
		$text .="ACLs from categories:\n";
		$text .= efACLWikiTextACL($category_acl, 1);
		
		$wgOut->addWikiText($text);
	}

	return false;
}

function efACLWikiTextACL($acl, $indent) {
	$text = '';
	$prefix = '';

	if ($indent > 0) {
		for ($i = 0; $i < $indent; $i++) {
			$prefix .= '*';
		}
	}

	foreach ($acl as $entity => $value) {
		$text .= $prefix . $entity  . ' : ' . implode($value) . "\n";
	}
	
	return $text;
}
		
?>
