네이버아이디로그인 OAUTH2 PHP pear package
==

이 패키지는 PHP로 NAVER ___[네이버아이로그인](https://developers.naver.com/docs/login/devguide/)___ 을 사용하기 기능을 제공 합니다.

> 이 패키지는 [oops/OAUTH2](https://github.com/OOPS-ORG-PHP/NAVER)의 sub module 로 포함이 되어 별도의 패키지로는 더이상 유지보수 하지 않습니다. [oops/OAUTH2](https://github.com/OOPS-ORG-PHP/NAVER) pear package 를 이용 하십시오.

## Installation

1. pear

    ```bash
    [root@host ~]# pear channel-discover pear.oops.org
    Adding Channel "pear.oops.org" succeeded
    Discovery of channel "pear.oops.org" succeeded
    [root@host ~]# pear install oops/NAVER
    ```
    
   upgrade 는 ```pear upgrade oops/NAVER``` 명령을 이용하십시오

1. 수동 설치

  * https://github.com/OOPS-ORG-PHP/NAVER/release 에서 가장 마지막 버전을 다운로드 받습니다.
  * PHP의 include_path 에 포함되는 위치에 압축을 풀어 놓습니다.
  * 이 패키지를 사용하기 위해서는 다음의 package가 필요 합니다.
    * myException at https://github.com/OOPS-ORG-PHP/myException/releases/
    * HTTPRelay at https://github.com/OOPS-ORG-PHP/HTTPRelay/releases/


## Reference

* http://pear.oops.org/docs/NAVER/NAVER.html
