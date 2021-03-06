<?php
/*
   COPYRIGHT 2009 Michael J Goonawardena

   This file is part of ConSof Alternate Login.

    ConSof Alternate Login is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    ConSof Alternate Login is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with ConSof Alternate Login.  If not, see <http://www.gnu.org/licenses/>.*/
if (!defined('IN_PHPBB'))
{
   exit;
}

if(!defined('AL_FACEBOOK_LOGIN'))
{
	define('AL_FACEBOOK_LOGIN', 0);
}
if(!defined('AL_OPENID_LOGIN'))
{
	define('AL_OPENID_LOGIN', 2);
}
if(!defined('AL_OPENID_PROFILE'))
{
	define('AL_OPENID_PROFILE', 3);
}
if(!defined('AL_GOOGLE_LOGIN'))
{
	define('AL_GOOGLE_LOGIN', 4);
}
if(!defined('AL_GOOGLE_PROFILE'))
{
	define('AL_GOOGLE_PROFILE', 5);
}
if(!defined('AL_WINDOWSLIVE_LOGIN'))
{
	define('AL_WINDOWSLIVE_LOGIN', 8);
}
if(!defined('AL_WINDOWSLIVE_PROFILE'))
{
	define('AL_WINDOWSLIVE_PROFILE', 9);
}

if(!defined('AL_FB_SYNC_PROFILE'))
{
	define('AL_FB_SYNC_PROFILE', 1);
}
if(!defined('AL_FB_SYNC_AVATAR'))
{
	define('AL_FB_SYNC_AVATAR', 11);
}
if(!defined('AL_FB_SYNC_STATUS'))
{
	define('AL_FB_SYNC_STATUS', 12);
}

if(!defined('AL_HIDE_POST_LOGON'))
{
	define('AL_HIDE_POST_LOGON', 10);	// Does the user want to be asked if they want to see the 'hide online' and autologin screen after verification?
}

if(!defined('AL_USER_OPTION_COUNT'))
{
	define('AL_USER_OPTION_COUNT', 13);
}

if(!defined('WL_COOKIE'))
{
	define('WL_COOKIE', 'webauthtoken');
}
if(!defined('PCOOKIE'))
{
	define('PCOOKIE', time() + (10 * 365 * 24 * 60 * 60));
}

if(!defined('HTTP_GET'))
{
	define('HTTP_GET', 0);
}

if(!defined('HTTP_POST'))
{
	define('HTTP_POST', 1);
}



if(!function_exists('get_wl_tokens'))
{
   function get_wl_tokens($cid, $authorization_code, $refresh_token)
   {
      global $config, $user;
      
      $url = "https://oauth.live.com/token?";
      $url .= "client_id={$config['al_wl_client_id']}";
      $url .= "&redirect_uri=" . urlencode($config['al_wl_callback']);
      $url .= "&client_secret={$config['al_wl_secret']}";
      if($authorization_code)
      {
         $url .= "&code={$authorization_code}";
         $url .= "&grant_type=authorization_code";
      }
      elseif($refresh_token)
      {
         $url .= "&refresh_token={$refresh_token}";
         $url .= "&grant_type=refresh_token";
      }
      else
      {
         trigger_error($user->lang['MISSING_AUTH_OR_REFRESH']);
      }
      
      $ch = curl_init();
      
      
      curl_setopt($ch, CURLOPT_URL, $url);
      
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
      
      $data = curl_exec($ch);
      
      if(curl_errno($ch))
      {
         add_log('critical', 'WL_GET_ACCESS_TOKEN_ERROR', $user->data['user_id'], 'WL_GET_ACCESS_TOKEN_ERROR', curl_error($ch));
         trigger_error($user->lang['ERROR_RETRIEVING_TOKENS']);
      }
   
      curl_close($ch);
      
      $data_decoded = json_decode($data);
      
      
      if(isset($data_decoded->error))
      {
         trigger_error($data_decoded->error);
      }
      
      return $data_decoded;
   }
}

if(!function_exists('get_wl_rest_request'))
{
   function get_wl_rest_request($access_token, $path, $method = HTTP_GET, $headers = NULL, $method_data = NULL)
   {
      $url = "https://apis.live.net/v5.0/{$path}/?access_token={$access_token}";
      
      $ch = curl_init();
      
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      
      if($method == HTTP_GET)
      {
         curl_setopt($ch, CURLOPT_HTTPGET, true);
      }
      elseif($method == HTTP_POST)
      {
         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $method_data);
      }
      else
      {
         trigger_error($user->lang['ERROR_UNDETERMINED_HTTP_METHOD']);
      }
      
      $data = curl_exec($ch);
      
      if(curl_errno($ch))
      {
         add_log('critical', 'WL_CURL_REQUEST_ERROR', $user->data['user_id'], 'WL_CURL_REQUEST_ERROR', curl_error($ch));
         return false;
      }
   
      curl_close($ch);
      
      return json_decode($data);
   }
}

if(!function_exists('get_fb_page_token'))
{
	function get_fb_page_token($access_token)
	{
		try
		{
			global $user, $config;
			
			parse_str($access_token, $access_token);
			$url = "https://graph.facebook.com/" . $user->data['al_fb_id'] . "/accounts?access_token=" . $access_token['access_token'];
			
			
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			  
			 // $access_token = array();
			  
			$accounts = json_decode(curl_exec($ch), true);
			
			if(isset($accounts['error']))
			{
				add_log('critical', $accounts['error']['message']);
				return false;
			}
			
			foreach($accounts['data'] as $a)
			{
				if($a['id'] == $config['al_fb_page_id'])
				{
					set_config('al_fb_page_token', $a['access_token']);
				}
			}
			
			
			  
			curl_close($ch);
		}
		catch(Exception $ex)
		{
			add_log('critical', $ex->getMessage());
			return false;
		}
		
	}
}

if(!function_exists('post_to_fb_user_wall'))
{
   function post_to_fb_user_wall($data, $users = null)
   { 
		global $user, $fb_session;
		
		try
		{
			if($users == null)
			{
				$request = new FacebookRequest($fb_session, 'POST', '/' . $user->data['al_fb_id'] . '/feed', $data);
				
				$request->execute()->getGraphObject();
				
				$id = $request->getProperty('id');
				
				if($id == null)
				{
				   add_log('critical', $user->data['user_id'], json_encode($response->asArray()));
				   return false;
				}
				
				return true;
			}
			else
			{
				foreach($users as $u)
				{
					$result = $facebook->api('/' . $u . '/feed', 'POST', $data);
				
					if(isset($result['error']))
					{
					   add_log('critical', $user->data['user_id'], $result['error']['message']);
					   return false;
					}
				
					
				}
				
				return true;
			}
		}
		catch(FacebookRequestException $ex)
		{
			add_log('critical', $user->data['user_id'], "Facebook Request Exception occured, code: " . $e->getCode() . " with message: " . $e->getMessage());
			return false;
		}
   }
}

if(!function_exists('update_fb_user_status'))
{
   function update_fb_user_status($data, $fb_id)
   { 
		global $user, $fb_session;
		try
		{
			$request = new FacebookRequest($fb_session, 'POST', '/' . $fb_id . '/feed', $data);
			
			$response = $request->execute();
			
			return true;
		}
		catch(FacebookRequestException $ex)
		{
			add_log('critical', $user->data['user_id'], "Facebook Request Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
			trigger_error("Facebook Request Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
		   	return false;
		}
   }
}

if(!function_exists('post_to_fb_page'))
{
   function post_to_fb_page($data)
   { 
		global $config, $user, $fb_session;
		
		try
		{
			
			$data['access_token']		= $config['al_fb_page_token'];
			
			$url = 'https://graph.facebook.com/' . $config['al_fb_page_id'] . '/feed';
				
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			  
			  
			$ret = curl_exec($ch);
			//echo $ret;
			$extended_token = substr($ret, strpos($ret, '=') + 1);
			
			
			if(isset($accounts['error']))
			{
				add_log('critical', $accounts['error']['message']);
				return false;
			}
			
			curl_close($ch);
			
			
			return true;
		}
		catch(Exception $ex)
		{
			add_log('critical', $user->data['user_id'], "Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
			trigger_error("Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
		}
   }
}

if(!function_exists('publish_post_to_fb_page'))
{
	function publish_post_to_fb_page($data)
	{
		global $user, $db;
		
		$sql_array = array(
			'SELECT'		=> 'username',
			'FROM'			=> array(USERS_TABLE => 'u'),
			'WHERE'			=> 'user_id=' . $data['poster_id'],
		);
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		
		
		$result = $db->sql_query($sql);
		
		$username = $db->sql_fetchrow($result);
		$post_data = array(
			'message'		=> sprintf($user->lang['FB_TEMPLATE_POST_PUBLISHED'], $username, $data['topic_title']),
			'link'			=> generate_board_url() . '/viewtopic.php?f=' . $data['forum_id'] . '&t=' . $data['topic_id'] . '#p' . $data['post_id'],
			'name'			=> $data['topic_title'],
		);
		
		return post_to_fb_page($post_data);
	}
}

if(!function_exists('publish_topic_to_fb_page'))
{
	function publish_topic_to_fb_page($data)
	{
		global $user, $db;
		
		$sql_array = array(
			'SELECT'		=> 'username',
			'FROM'			=> array(USERS_TABLE => 'u'),
			'WHERE'			=> 'user_id=' . $data['poster_id'],
		);
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		
		$result = $db->sql_query($sql);
		
		$username = $db->sql_fetchrow($result);
		
		$post_data = array(
			'message'		=> vsprintf($user->lang['FB_TOPIC_PAGE_TITLE'], array($username['username'], $data['topic_title'])),
			'link'			=> generate_board_url() . '/viewtopic.php?f=' . $data['forum_id'] . '&t=' . $data['topic_id'] . '#p' . $data['post_id'],
			'name'			=> $data['topic_title'],
		);
		
		return post_to_fb_page($post_data);
	}
}

if(!function_exists('publish_post_to_fb_user'))
{
	function publish_post_to_fb_user($data)
	{
		try
		{
			global $user, $fb_session;
			$access_token = $fb_id = $name = null;
			
			
			
			if(isset($data['post_fb']))
			{
				
				$post_fb = json_decode($data['post_fb']);
				$fb_id = $post_fb->fb_id;
				$access_token = $post_fb->access_token;
				$name = $post_fb->username;
			}
			else
			{
				
				$access_token = $user->data['al_fb_access_token'];
				$name = $user->data['username'];
				$fb_id = $user->data['al_fb_id'];
			}
			if(!$fb_session)
			{
				$fb_session = new FacebookSession($access_token);
				if(!$fb_session->validate())
				{
					$fb_session = null;
				}
			}
			$request = new FacebookRequest($fb_session, 'GET', '/' . $fb_id);
			$response = $request->execute();
			$fb_user = $response->getGraphObject(GraphUser::className());
			$fb_id = $fb_user->getProperty('id');
			
			$post_data = array(
				'message'		=> vsprintf($user->lang['FB_USER_POST_TO_FEED_TITLE'], array($name, $data['topic_title'])),
				'link'			=> generate_board_url() . '/viewtopic.php?f=' . $data['forum_id'] . '&t=' . $data['topic_id'] . '#p' . $data['post_id'],
				'name'			=> $data['topic_title'],
			);
			return update_fb_user_status($post_data, $fb_id);
		}
		catch(FacebookRequestException $ex)
		{
			add_log('critical', $user->data['user_id'], "Facebook Request Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
			trigger_error("Facebook Request Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
		}
		catch(FacebookSDKException $ex)
		{
			add_log('critical', $user->data['user_id'], "Facebook SDK Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
			trigger_error("Facebook SDK Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
		}
	}
}

if(!function_exists('get_fb_extended_tokens'))
{
	function get_fb_extended_tokens($access_token)
	{
		global $config;
		
		try
		{
			$url = 'https://graph.facebook.com/oauth/access_token?grant_type=fb_exchange_token&client_id=' . $config['al_fb_id'] . '&client_secret=' . $config['al_fb_secret'] . '&fb_exchange_token=' . $access_token;
			
			
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			  
			  
			$ret = curl_exec($ch);
			//echo $ret;
			$extended_token = substr($ret, strpos($ret, '=') + 1);
			
			
			if(isset($accounts['error']))
			{
				add_log('critical', $accounts['error']['message']);
				return false;
			}
			
			$url = 'https://graph.facebook.com/me/accounts?access_token=' . $extended_token;
			//echo $url;
			curl_setopt($ch, CURLOPT_URL, $url);
			
			/*foreach($accounts['data'] as $a)
			{
				if($a['id'] == $config['al_fb_page_id'])
				{
					set_config('al_fb_page_token', $a['access_token']);
				}
			}*/
			
			$ret = curl_exec($ch);
			
			curl_close($ch);
			
			return $ret;
		
		}
		catch(Exception $ex)
		{
			add_log('critical', $user->data['user_id'], "Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
			trigger_error("Exception occured on line:" . $ex->getLine() . ", code: " . $ex->getCode() . " with message: " . $ex->getMessage());
		}
	}
}


/**
* Get user avatar filename - Adapted from functions_display:get_user_avatar()
*
* @param string $avatar Users assigned avatar name
* @param int $avatar_type Type of avatar
* @param string $avatar_width Width of users avatar
* @param string $avatar_height Height of users avatar
* @param string $alt Optional language string for alt tag within image, can be a language key or text
* @param bool $ignore_config Ignores the config-setting, to be still able to view the avatar in the UCP
*
* @return string Avatar image
*/
if(!function_exists('get_user_avatar_filename'))
{
	function get_user_avatar_filename($avatar, $avatar_type)
	{
		global $user, $config, $phpbb_root_path, $phpEx;
	
		if (empty($avatar) || !$avatar_type || !$config['allow_avatar'])
		{
			return '';
		}
		
		$avatar_img = '';
	
		switch ($avatar_type)
		{
			case AVATAR_UPLOAD:
				
				$avatar_img = generate_board_url() . substr($phpbb_root_path . "download/file.$phpEx?avatar=", 1);
			break;
	
			case AVATAR_GALLERY:
				
				$avatar_img = generate_board_url() . substr($phpbb_root_path . $config['avatar_gallery_path'] . '/', 1);
			break;
	
			case AVATAR_REMOTE:
				
			break;
		}
	
		$avatar_img .= $avatar;
		return str_replace(' ', '%20', $avatar_img);
	}
}


if(!function_exists('php_self'))
{
   function php_self($dropqs=true) {
   $url = sprintf('%s://%s%s',
     empty($_SERVER['HTTPS']) ? (@$_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http') : 'http',
     $_SERVER['SERVER_NAME'],
     $_SERVER['REQUEST_URI']
   );
   
   $parts = parse_url($url);
   
   $port = $_SERVER['SERVER_PORT'];
   $scheme = $parts['scheme'];
   $host = $parts['host'];
   $path = @$parts['path'];
   $qs   = @$parts['query'];
   
   $port or $port = ($scheme == 'https') ? '443' : '80';
   
   if (($scheme == 'https' && $port != '443')
      || ($scheme == 'http' && $port != '80')) {
     $host = "$host:$port";
   }
   $url = "$scheme://$host$path";
   if ( ! $dropqs)
     return "{$url}?{$qs}";
   else
     return $url;
   }
}
?>