<?php
/*
*    Email:chengciming@126.com
*    
*    时间：2011-08
*
*    说明：邮件类
*    
*    使用：  如：send_mails();
*/
class Helper_Email{
/**
 * 邮件发送 （调用函数）
 *
 * @param: $name[string]        接收人姓名
 * @param: $email[string]       接收人邮件地址
 * @param: $subject[string]     邮件标题
 * @param: $content[string]     邮件内容
 * @param: $send_array[array]   发送方的帐号等配置array('host'=>'服务器','port'=>'服务器端口','user'=>'帐号','pass'=>'密码')  如果等于空则使用mail函数发送邮件
 * @param: $from_name[string]   邮件发送人显示的名称
 * @param: $from_service[string]   邮件发送人显示的服务器
 * @param: $from_mail[string]   邮件发送人显示的Email地址
 * @param: $type[int]           0 普通邮件， 1 HTML邮件
 * @param: $notification[bool]  true 要求回执， false 不用回执
 *
 * @return boolean
 */
static function send_mails($name, $email, $subject, $content, $send_array, $from_name, $from_service = '', $from_mail = '', $type = 1, $notification=false)
{
	$from_service = $from_service!='' ? $from_service : $send_array['host'];
	$from_mail = $from_mail!='' ? $from_mail : $send_array['user'];

	$charset   = 'utf-8';
	/**
	 * 使用mail函数发送邮件
	 */
	if ($send_array == '' && function_exists('mail'))
	{
		/* 邮件的头部信息 */
		$content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
		$headers = array();
		$headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $from_mail . '>';
		$headers[] = $content_type . '; format=flowed';
		if ($notification)
		{
			$headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $from_mail . '>';
		}

		$res = @mail($email, '=?' . $charset . '?B?' . base64_encode($subject) . '?=', $content, implode("\r\n", $headers));

		if (!$res)
		{
			//邮件发送失败，请与网站管理员联系！   错误信息

			return false;
		}
		else
		{
			return true;
		}
	}
	/**
	 * 使用smtp服务发送邮件
	 */
	else
	{
		/* 邮件的头部信息 */
		$content_type = ($type == 0) ?
		'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
		$content   =  base64_encode($content);

		$headers = array();
		$headers[] = 'Date: ' . gmdate('D, j M Y H:i:s') . ' +0000';
		$headers[] = 'To: "' . '=?' . $charset . '?B?' . base64_encode($name) . '?=' . '" <' . $email. '>';
		$headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $from_mail . '>';
		$headers[] = 'Subject: ' . '=?' . $charset . '?B?' . base64_encode($subject) . '?=';
		$headers[] = $content_type . '; format=flowed';
		$headers[] = 'Content-Transfer-Encoding: base64';
		$headers[] = 'Content-Disposition: inline';
		if ($notification)
		{
			$headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $from_mail . '>';
		}

		/* 获得邮件服务器的参数设置 */
		$params['host'] = $send_array['host'];
		$params['port'] = $send_array['port'];
		$params['user'] = $send_array['user'];
		$params['pass'] = $send_array['pass'];

		if (empty($params['host']) || empty($params['port']))
		{
			// 如果没有设置主机和端口直接返回 false
			//邮件服务器设置信息不完整      错误信息
			return false;
		}
		else
		{
			// 发送邮件
			if (!function_exists('fsockopen'))
			{
				//如果fsockopen被禁用，直接返回
				//fsockopen函数被禁用      错误信息
				return false;
			}

			static $smtp;

			$send_params['recipients'] = $email;
			$send_params['headers']    = $headers;
			$send_params['from']       = $from_mail;
			$send_params['body']       = $content;


			$smtp = new email($params);

			if ($smtp->connect() && $smtp->send($send_params))
			{
				return true;
			}
			else
			{

				$err_msg = $smtp->error_msg();
				if (empty($err_msg))
				{
					//'Unknown Error';   找不到错误信息
				}
				else
				{
					if (strpos($err_msg, 'Failed to connect to server') !== false)
					{
						//无法连接到邮件服务器:$params['port'];      错误信息
					}
					else if (strpos($err_msg, 'AUTH command failed') !== false)
					{
						//邮件服务器验证帐号或密码不正确;       错误信息
					}
					elseif (strpos($err_msg, 'bad sequence of commands') !== false)
					{
						//服务器拒绝发送该邮件;      错误信息
					}
					else
					{
						//$err_msg;   错误信息
					}
				}

				return false;
			}
		}
	}
}
}
class email
{
    var $connection;
    var $recipients;
    var $headers;
    var $timeout;
    var $errors;
    var $status;
    var $body;
    var $from;
    var $host;
    var $port;
    var $helo;
    var $auth;
    var $user;
    var $pass;

    /**
     *  参数为一个数组
     *  host        SMTP 服务器的主机       默认：localhost
     *  port        SMTP 服务器的端口       默认：25
     *  helo        发送HELO命令的名称      默认：localhost
     *  user        SMTP 服务器的用户名     默认：空值
     *  pass        SMTP 服务器的登陆密码   默认：空值
     *  timeout     连接超时的时间          默认：5
     *  @return  bool
     */
    function email($params = array())
    {
		define('SMTP_STATUS_NOT_CONNECTED', 1, true);
		define('SMTP_STATUS_CONNECTED',     2, true);
		
		if (!defined('CRLF'))
        {
            define('CRLF', "\r\n", true);
        }
		
        $this->timeout  = 10;
        $this->status   = SMTP_STATUS_NOT_CONNECTED;
        $this->host     = 'localhost';
        $this->port     = 25;
        $this->auth     = false;
        $this->user     = '';
        $this->pass     = '';
        $this->errors   = array();

        foreach ($params AS $key => $value)
        {
            $this->$key = $value;
        }

        $this->helo     = $this->host;

        //  如果没有设置用户名则不验证
        $this->auth = ('' == $this->user) ? false : true;
    }
	
	/**
	 * 邮件发送
	 *
	 * @param: $name[string]        接收人姓名
	 * @param: $email[string]       接收人邮件地址
	 * @param: $subject[string]     邮件标题
	 * @param: $content[string]     邮件内容
	 * @param: $from_name[string]     发邮件人的名称
	 * @param: $type[int]           0 普通邮件， 1 HTML邮件
	 * @param: $notification[bool]  true 要求回执， false 不用回执
	 *
	 * @return boolean
	 */
	function send_mail($name, $email, $subject, $content, $from_name = '', $type = 0, $notification=false)
	{
		$charset   = 'UTF-8';
		/**
		 * 使用mail函数发送邮件
		 */
		if ($this->host == 0 && function_exists('mail'))
		{
			/* 邮件的头部信息 */
			$content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
			$headers = array();
			$headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $this->from . '>';
			$headers[] = $content_type . '; format=flowed';
			if ($notification)
			{
				$headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $this->from . '>';
			}
	
			$res = @mail($email, '=?' . $charset . '?B?' . base64_encode($subject) . '?=', $content, implode("\r\n", $headers));
	
			if (!$res)
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		/**
		 * 使用smtp服务发送邮件
		 */
		else
		{
			/* 邮件的头部信息 */
			$content_type = ($type == 0) ?
				'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
			$content   =  base64_encode($content);
	
			$headers = array();
			$headers[] = 'Date: ' . gmdate('D, j M Y H:i:s') . ' +0000';
			$headers[] = 'To: "' . '=?' . $charset . '?B?' . base64_encode($name) . '?=' . '" <' . $email. '>';
			$headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $this->from . '>';
			$headers[] = 'Subject: ' . '=?' . $charset . '?B?' . base64_encode($subject) . '?=';
			$headers[] = $content_type . '; format=flowed';
			$headers[] = 'Content-Transfer-Encoding: base64';
			$headers[] = 'Content-Disposition: inline';
			if ($notification)
			{
				$headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $this->from . '>';
			}
	
			/* 获得邮件服务器的参数设置 */
			$params['host'] = $this->host;
			$params['port'] = $this->port;
			$params['user'] = $this->user;
			$params['pass'] = $this->pass;
	
			if (empty($params['host']) || empty($params['port']))
			{
				// 如果没有设置主机和端口直接返回 false
				return false;
			}
			else
			{
				// 发送邮件
				if (!function_exists('fsockopen'))
				{
					//如果fsockopen被禁用，直接返回
					return false;
				}
	
				//static $smtp;
	
				$send_params['recipients'] = $email;
				$send_params['headers']    = $headers;
				$send_params['from']       = $this->from;
				$send_params['body']       = $content;
	
				/*if (!isset($smtp))
				{
					$smtp = new smtp($params);
				}*/
	
				if ($this->connect() && $this->send($send_params))
				{
					return true;
				}
				else
				{
					$err_msg = $this->error_msg();
					if (empty($err_msg))
					{
						die('Unknown Error');
					}
					else
					{
						if (strpos($err_msg, 'Failed to connect to server') !== false)
						{
							die(sprintf('邮件服务器连接失败', $params['host'] . ':' . $params['port']));
						}
						else if (strpos($err_msg, 'AUTH command failed') !== false)
						{
							die('登录服务器失败！');
						}
						elseif (strpos($err_msg, 'bad sequence of commands') !== false)
						{
							die('服务器繁忙！');
						}
						else
						{
							die($err_msg);
						}
					}
	
					return false;
				}
			}
		}
	}

    function connect($params = array())
    {
        if (!isset($this->status))
        {
            $obj = new email($params);

            if ($obj->connect())
            {
                $obj->status = SMTP_STATUS_CONNECTED;
            }

            return $obj;
        }
        else
        {
            if (!empty($GLOBALS['_CFG']['smtp_ssl']))
            {
                $this->host = "ssl://" . $this->host;
            }
            $this->connection = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

            if ($this->connection === false)
            {
                $this->errors[] = 'Access is denied.';

                return false;
            }

            @socket_set_timeout($this->connection, 0, 250000);

            $greeting = $this->get_data();

            if (is_resource($this->connection))
            {
                $this->status = SMTP_STATUS_CONNECTED;

                return $this->auth ? $this->ehlo() : $this->helo();
            }
            else
            {
                $this->errors[] = 'Failed to connect to server: ' . $errstr;

                return false;
            }
        }
    }

    /**
     * 参数为数组
     * recipients      接收人的数组
     * from            发件人的地址，也将作为回复地址
     * headers         头部信息的数组
     * body            邮件的主体
     */

    function send($params = array())
    {
        foreach ($params AS $key => $value)
        {
            $this->$key = $value;
        }

        if ($this->is_connected())
        {
            //  服务器是否需要验证
            if ($this->auth)
            {
                if (!$this->auth())
                {
                    return false;
                }
            }

            $this->mail($this->from);

            if (is_array($this->recipients))
            {
                foreach ($this->recipients AS $value)
                {
                    $this->rcpt($value);
                }
            }
            else
            {
                $this->rcpt($this->recipients);
            }

            if (!$this->data())
            {
                return false;
            }

            $headers = str_replace(CRLF . '.', CRLF . '..', trim(implode(CRLF, $this->headers)));
            $body    = str_replace(CRLF . '.', CRLF . '..', $this->body);
            $body    = substr($body, 0, 1) == '.' ? '.' . $body : $body;

            $this->send_data($headers);
            $this->send_data('');
            $this->send_data($body);
            $this->send_data('.');

            return (substr($this->get_data(), 0, 3) === '250');
        }
        else
        {
            $this->errors[] = 'Not connected!';

            return false;
        }
    }

    function helo()
    {
        if (is_resource($this->connection)
                AND $this->send_data('HELO ' . $this->helo)
                AND substr($error = $this->get_data(), 0, 3) === '250' )
        {
            return true;
        }
        else
        {
            $this->errors[] = 'HELO command failed, output: ' . trim(substr($error, 3));

            return false;
        }
    }

    function ehlo()
    {
        if (is_resource($this->connection)
                AND $this->send_data('EHLO ' . $this->helo)
                AND substr($error = $this->get_data(), 0, 3) === '250' )
        {
            return true;
        }
        else
        {
            $this->errors[] = 'EHLO command failed, output: ' . trim(substr($error, 3));

            return false;
        }
    }

    function auth()
    {
        if (is_resource($this->connection)
                AND $this->send_data('AUTH LOGIN')
                AND substr($error = $this->get_data(), 0, 3) === '334'
                AND $this->send_data(base64_encode($this->user))            // Send username
                AND substr($error = $this->get_data(),0,3) === '334'
                AND $this->send_data(base64_encode($this->pass))            // Send password
                AND substr($error = $this->get_data(),0,3) === '235' )
        {
            return true;
        }
        else
        {
            $this->errors[] = 'AUTH command failed: ' . trim(substr($error, 3));

            return false;
        }
    }

    function mail($from)
    {
        if ($this->is_connected()
            AND $this->send_data('MAIL FROM:<' . $from . '>')
            AND substr($this->get_data(), 0, 2) === '250' )
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function rcpt($to)
    {
        if ($this->is_connected()
            AND $this->send_data('RCPT TO:<' . $to . '>')
            AND substr($error = $this->get_data(), 0, 2) === '25')
        {
            return true;
        }
        else
        {
            $this->errors[] = trim(substr($error, 3));

            return false;
        }
    }

    function data()
    {
        if ($this->is_connected()
            AND $this->send_data('DATA')
            AND substr($error = $this->get_data(), 0, 3) === '354' )
        {
            return true;
        }
        else
        {
            $this->errors[] = trim(substr($error, 3));

            return false;
        }
    }

    function is_connected()
    {
        return (is_resource($this->connection) AND ($this->status === SMTP_STATUS_CONNECTED));
    }

    function send_data($data)
    {
        if (is_resource($this->connection))
        {
            return fwrite($this->connection, $data . CRLF, strlen($data) + 2);
        }
        else
        {
            return false;
        }
    }

    function get_data()
    {
        $return = '';
        $line   = '';

        if (is_resource($this->connection))
        {
            while (strpos($return, CRLF) === false OR $line{3} !== ' ')
            {
                $line    = fgets($this->connection, 512);
                $return .= $line;
            }

            return trim($return);
        }
        else
        {
            return '';
        }
    }

    /**
     * 获得最后一个错误信息
     *
     * @access  public
     * @return  string
     */
    function error_msg()
    {
        if (!empty($this->errors))
        {
            $len = count($this->errors) - 1;
            return $this->errors[$len];
        }
        else
        {
            return '';
        }
    }
}

?>