<?php
/**
 * Project: NAVER :: 네이버 로그인(oauth2) pear package<br>
 * File:    NAVER.php<br>
 * Dependency:
 *
 *   - {@link http://pear.oops.org/docs/li_HTTPRelay.html oops/HTTPRelay}
 *   - {@link http://pear.oops.org/docs/li_myException.html oops/myException}
 *   - {@link http://kr1.php.net/manual/en/book.curl.php curl extension}
 *
 * 네이버 로그인 pear package는 oauth2 protocol을 사용하여 네이버 로그인
 * 정보를 이용하여 회원 관리를 할 수 있게 해 준다.
 *
 * @category  HTTP
 * @package   NAVER
 * @author    JoungKyun.Kim <http://oops.org>
 * @copyright (c) 2014 OOPS.org
 * @license   BSD License
 * @version   SVN: $Id$
 * @link      http://pear.oops.org/package/NAVER
 * @since     File available since release 1.0.0
 * @example   pear_NAVER/tests/test.php sample for oops/NAVER pear package
 * @filesource
 */

/**
 * import HTTPRelay class
 */
require_once 'HTTPRelay.php';


/**
 * Naver API의 main class
 *
 * @package NAVER
 * @author    JoungKyun.Kim <http://oops.org>
 * @copyright (c) 2014 OOPS.org
 * @license   BSD License
 * @version   SVN: $Id$
 * @link      http://pear.oops.org/package/NAVER
 * @since     File available since release 1.0.0
 * @example   pear_NAVER/tests/test.php NAVER pear 예제 코드
 */
Class NAVER {
	// {{{ properities
	/**#@+
	 * @access private
	 */
	/**
	 * 세션 이름
	 * @var string
	 */
	private $sessid   = 'NaverOauth';
	/**
	 * login url
	 * @var string
	 */
	private $reqAuth  = 'https://nid.naver.com/oauth2.0/authorize';
	/**
	 * token url
	 * @var string
	 */
	private $reqToken = 'https://nid.naver.com/oauth2.0/token';
	/**
	 * user information url
	 * @var string
	 */
	private $reqUser  = 'https://apis.naver.com/nidlogin/nid/getUserProfile.xml';
	/**
	 * consumer information
	 * @var stdClass memebr는 다음과 같음
	 *   - id     : Naver login CliendID key
	 *   - secret : Naver login ClientSecret key
	 *   - callback : 이 class를 호출하는 페이지
	 */
	private $consumer;
	/**#@-*/
	/**
	 * 네이버 로그인에 필요한 session 값
	 * @access public
	 * @var stdClass
	 */
	public $sess;
	// }}}

	// {{{ +-- public (void) __construct ($v)
	/**
	 * Naver 로그인 인증 과정을 수행한다. 인증 과정 중에
	 * 에러가 발생하면 myException 으로 에러 메시지를
	 * 보낸다.
	 *
	 * @access public
	 * @param stdClass $v
	 *   - id       발급받은 Naver login ClientID key
	 *   - secret   발급받은 Naver login ClientScret key
	 *   - callback 이 클래스가 호출되는 url
	 * @return void
	 */
	function __construct ($v) {
		if ( ! isset ($_SESSION[$this->sessid]) ) {
			$_SESSION[$this->sessid] = new stdClass;
			$_SESSION[$this->sessid]->appId = (object) $v;
		}
		$this->sess = &$_SESSION[$this->sessid];
		$this->consumer = (object) $v;

		if ( isset ($_GET['logout']) ) {
			$this->reqLogout ();
			return;
		}

		$this->checkError ();
		$this->reqLogin ();
		$this->reqAccessToken ();
	}
	// }}}

	// {{{ +-- private (string) mkToken (void)
	/**
	 * 세션 유지를 위한 token 값
	 *
	 * @access private
	 * @return string
	 */
	private function mkToken () {
		$mt = microtime ();
		$rand = mt_rand ();
		return md5 ($mt . $rand);
	}
	// }}}

	// {{{ +-- private (void) reqLogin (void)
	/**
	 * 로그인 창으로 redirect
	 *
	 * @access private
	 * @return void
	 */
	private function reqLogin () {
		$cons = &$this->consumer;
		$this->sess->state = $this->mkToken ();

		if ( $_GET['code'] || isset ($this->sess->oauth)  )
			return;

		$url = sprintf (
			'%s?client_id=%s&response_type=code&redirect_uri=%s&state=%s',
			$this->reqAuth, $cons->id, rawurlencode ($cons->callback),
			$this->sess->state
		);

		Header ('Location: ' . $url);
	}
	// }}}

	// {{{ +-- private (void) reqAccessToken (void)
	/**
	 * Authorization code를 발급받아 session에 등록
	 *
	 * NAVER::$sess->oauth 를 stdClass로 생성하고 다음의
	 * member를 등록한다.
	 *
	 *   - access_token:      발급받은 access token. expires_in(초) 이후 만료
	 *   - refresh_token:     access token 만료시 재발급 키 (14일 expire)
	 *   - token_type:        Bearer or MAC
	 *   - expires_in:        access token 유효시간(초)
	 *   - error:             error code
	 *   - error_description: error 상세값
	 *
	 * @access private
	 * @return void
	 */
	private function reqAccessToken () {
		$sess = &$this->sess;
		$cons = &$this->consumer;

		if ( ! $_GET['code'] || isset ($sess->oauth) )
			return;

		$url = sprintf (
			'%s?client_id=%s&client_secret=%s&grant_type=authorization_code&state=%s&code=%s',
			$this->reqToken, $cons->id, $cons->secret, $sess->state, $_GET['code']
		);

		$http = new HTTPRelay;
		$buf = $http->fetch ($url);
		$r = json_decode ($buf);

		if ( $r->error )
			$this->error ($r->error_description);
		
		$sess->oauth = (object) $r;
	}
	// }}}

	// {{{ +-- private (void) checkError (void)
	/**
	 * 에러 코드가 존재하면 에러 처리를 한다.
	 *
	 * @access private
	 * @return void
	 */
	private function checkError () {
		$sess = &$this->sess;

		if ( $_GET['error'] )
			$this->error ($_GET['error_description']);

		if ( $_GET['state'] && $_GET['state'] != $sess->state )
			$this->error ('Invalude Session state: ' . $_GET['state']);
	}
	// }}}

	// {{{ +-- private (void) error ($msg)
	/**
	 * 에러를 Exception 처리한다.
	 *
	 * @access private
	 * @return void
	 */
	private function error ($msg) {
		$msg = $_SERVER['HTTP_REFERER'] . "\n" . $msg;
		throw new myException ($msg, E_USER_ERROR);
	}
	// }}}

	// {{{ +-- public (stdClass) getUser (void)
	/**
	 * 로그인 과정이 완료되면 발급받은 NAVER::$sess->oauth 에 등록된
	 * 키를 이용하여 로그인 사용자의 정보를 가져온다.
	 *
	 * @access public
	 * @return stdClass 다음의 object를 반환
	 *   - id       사용자 확인 값
	 *   - nickname 사용자 닉네임
	 *   - email    이메일(ID@naver.com)
	 *   - gender   성별 (F:여성/M:남성/U:확인불가)
	 *   - birth    생일 (MM-DD 형식으로 반환)
	 *   - img      프로필 사진 URL 정보 
	 */
	public function getUser () {
		$sess = &$this->sess;

		if ( ! isset ($sess->oauth) )
			return false;

		$req = $sess->oauth->token_type . ' ' . $sess->oauth->access_token;

		$header = array ('Authorization' => $req);
		$http = new HTTPRelay ($header);
		$buf = $http->fetch ($this->reqUser);

		$xml = simplexml_load_string ($buf);
		if ( $xml->result->resultcode != '00' )
			$this->error ($r->result->message);

		$xmlr = &$xml->response;
		$r = array (
			'id' => (string) $xmlr->enc_id->{0},
			'name' => (string) $xmlr->nickname->{0},
			'email' => (string) $xmlr->email->{0},
			'gender' => (string) $xmlr->gender->{0},
			'age'   => (string) $xmlr->age->{0},
			'birth' => (string) $xmlr->birthday,
			'img'  => (string) $xmlr->profile_image
		);

		return (object) $r;
	}
	// }}}

	// {{{ +-- public (void) reqLogout (void)
	/**
	 * 네이버 로그인의 authorization key를 만료 시키고
	 * 세션에 등록된 정보(NAVER::$sess)를 제거한다.
	 *
	 * @access public
	 * @return void
	 */
	public function reqLogout () {
		$sess = &$this->sess;
		$cons = &$this->consumer;

		if ( ! isset ($sess->oauth) )
			return;

		// guide에는 refresh_token을 넣으라고 되어 있는데,
		// 실제로는 access_token을 넣어야 한다.
		$url = sprintf (
			'%s?grant_type=delete&client_id=%s&client_secret=%s&' .
			'access_token=%s&service_provider=NAVER',
			$this->reqToken, $cons->id, $cons->secret, $sess->oauth->access_token
		);

		$http = new HTTPRelay;
		$buf = $http->fetch ($url);

		$r = json_decode ($buf);
		if ( $r->error )
			$this->error ($r->error_description);

		unset ($_SESSION[$this->sessid]);
	}
	// }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
?>
