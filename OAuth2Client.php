<?php
/**
 * OAuth2Client.php
 * Based on TwitterLogin by David Raison, which is based on the guideline published by Dave Challis at http://blogs.ecs.soton.ac.uk/webteam/2010/04/13/254/
 * @license: LGPL (GNU Lesser General Public License) http://www.gnu.org/licenses/lgpl.html
 *
 * @file OAuth2Client.php
 * @ingroup OAuth2Client
 *
 * @author Joost de Keijzer
 * @author Nischay Nahata for Schine GmbH
 *
 * Uses the OAuth2 library https://github.com/thephpleague/oauth2-client
 *
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is a MediaWiki extension, and must be run from within MediaWiki.' );
}
class OAuth2ClientHooks {
	/**
	 * Adds the "login with OAuth2" link to the user menu (top right) for anonymous users,
	 * and removes the classic login / account creation links.
	 *
	 * Replaces the PersonalUrls hook, removed in MediaWiki 1.43.
	 *
	 * @param SkinTemplate $skin
	 * @param array &$links
	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $skin, array &$links ) {
		global $wgOAuth2Client;

		if ( $skin->getUser()->isRegistered() ) {
			return true;
		}

		# Due to bug 32276, if a user does not have read permissions,
		# $this->getTitle() will just give Special:Badtitle, which is
		# not especially useful as a returnto parameter. Use the title
		# from the request instead, if there was one.
		$page = Title::newFromURL( $skin->getRequest()->getVal( 'title', '' ) );

		$service_name = isset( $wgOAuth2Client['configuration']['service_name'] ) && 0 < strlen( $wgOAuth2Client['configuration']['service_name'] ) ? $wgOAuth2Client['configuration']['service_name'] : 'OAuth2';
		if( isset( $wgOAuth2Client['configuration']['service_login_link_text'] ) && 0 < strlen( $wgOAuth2Client['configuration']['service_login_link_text'] ) ) {
			$service_login_link_text = $wgOAuth2Client['configuration']['service_login_link_text'];
		} else {
			$service_login_link_text = wfMessage('oauth2client-header-link-text', $service_name)->text();
		}

		$inExt = ( null == $page || ('OAuth2Client' == substr( $page->getText(), 0, 12) ) || strstr($page->getText(), 'Logout') );
		$oauth_login = [
			'text' => $service_login_link_text,
			'active' => false,
		];
		$redirect = SpecialPage::getTitleFor( 'OAuth2Client', 'redirect' );
		if( $inExt ) {
			$oauth_login['href'] = $redirect->getLocalURL();
		} else {
			$oauth_login['href'] = $redirect->getLocalURL( [ 'returnto' => $page->getPrefixedText() ] );
		}

		$userMenu = $links['user-menu'] ?? [];
		//BEGIN GJANSSENS
		unset( $userMenu['login'], $userMenu['login-private'], $userMenu['createaccount'] );
		//END GJANSSENS
		$links['user-menu'] = [ 'anon_oauth_login' => $oauth_login ] + $userMenu;

		return true;
	}

}
