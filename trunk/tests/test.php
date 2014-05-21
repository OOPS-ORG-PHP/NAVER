<?php
/* $Id$ */

require_once 'NAVER.php';

// 에러 발생시에 catch를 하기 위하여 error handler를 등록
set_error_handler ('myException::myErrorHandler');

session_start ();

try {
	// 발급받은 키를 등록한다.
	$login = (object) array (
		'id'       => '8avBegO24BpmziA3027D',
		'secret'   => '1zUVPMAl5R',
		'callback' => 'http://domain.com/path/this/file'
	);

	// 인증 과정을 수행
	$naver = new NAVER ($login);

	// 인증 과정 완료 후 사용자 정보를 가져옴
	$user = $naver->getUser ();

	// 유저 정보 출력
	print_r ($user);

} catch ( myException $e ) {
	// 에러 발생시에 다음 출력
	echo '<pre>' . PHP_EOL;
	echo $e->Message () . PHP_EOL;
	print_r ($e->TraceAsArray ());
	exit;
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
