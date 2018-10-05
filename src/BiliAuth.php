<?php
/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Version: 0.0.1
 */

namespace Lkeme;

header('Content-type:text/json');

class BiliAuth
{
    private $headers = [
        'Accept' => '*/*',
        # 'Accept-Encoding' => 'gzip',
        'Accept-Language' => 'zh-cn',
        'Connection' => 'keep-alive',
        'Content-Type' => 'application/x-www-form-urlencoded',
        'User-Agent' => 'bili-universal/8110 CFNetwork/974.2.1 Darwin/18.0.0',
    ];

    /**
     * BiliAuth constructor.
     */
    public function __construct()
    {
        $this->client = new HttpClient($this->headers);
    }

    /**
     * @use login
     * @param $username
     * @param $password
     * @return false|string
     */
    public function login($username, $password)
    {
        $auth = $this->getPublicKey();
        if ($auth['code']) {
            return $this->buildData($auth);
        }
        $key = $auth['key'];
        $hash = $auth['hash'];
        openssl_public_encrypt($hash . $password, $crypt, $key);
        $token = $this->getToken($username, base64_encode($crypt));

        return $this->buildData($token);
    }

    /**
     * @use loginByCaptcha
     * @param $username
     * @param $password
     * @param $captcha
     * @param $cookie
     * @return false|string
     */
    public function loginByCaptcha($username, $password, $captcha, $cookie)
    {
        $auth = $this->getPublicKey();
        if ($auth['code']) {
            return $this->buildData($auth);
        }
        $key = $auth['key'];
        $hash = $auth['hash'];
        openssl_public_encrypt($hash . $password, $crypt, $key);

        $token = $this->getToken($username, base64_encode($crypt), $captcha, $cookie);

        return $this->buildData($token);
    }

    /**
     * @use getCapcha
     * @return false|string
     */
    public function getCapcha()
    {
        $cookie = "sid=" . $this->getRandomString();
        $payload = $this->sign([]);
        $url = 'https://passport.bilibili.com/captcha';

        $raw = $this->client->get($url, $payload, $headers = [], $cookie);

        $data = [
            'code' => $raw['code'],
            'cookie' => $cookie,
            'captcha_img' => base64_encode($raw['content']),
            'bash64_head' => "data:image/jpg/png/gif;base64,",
            'message' => '获取验证码图片(Base64)!',
        ];

        return $this->buildData($data);
    }

    /**
     * @use checkCookie
     * @param $cookie
     * @return false|string
     */
    public function checkCookie($cookie)
    {
        $headers = [
            'Cookie' => $cookie,
        ];
        $payload = [
            'ts' => time(),
        ];
        $url = 'https://api.live.bilibili.com/User/getUserInfo';

        $raw = $this->client->get($url, $payload, $headers);
        $de_raw = json_decode($raw['content'], true);

        if (isset($de_raw['code']) && $de_raw['code'] != 'REPONSE_OK') {
            $data = [
                'code' => $de_raw['code'],
                'status' => 'invalid',
                'message' => '检测 Cookie 无效!'
            ];
        } else {
            $data = [
                'code' => 0,
                'status' => 'valid',
                'message' => '检测 Cookie 有效!'
            ];
        }

        return $this->buildData($data);
    }

    /**
     * @use refreshToken
     * @param $access
     * @param $refresh
     * @return false|string
     */
    public function refreshToken($access, $refresh)
    {
        $payload = [
            'access_token' => $access,
            'refresh_token' => $refresh,
        ];
        $payload = $this->sign($payload);
        $url = 'https://passport.bilibili.com/api/oauth2/refreshToken';

        $raw = $this->client->post($url, $payload);
        $de_raw = json_decode($raw['content'], true);

        if (isset($de_raw['code']) && $de_raw['code']) {
            $data = [
                'code' => $de_raw['code'],
                'message' => '续签令牌: ' . $this->buildMsg($de_raw, '失败!')
            ];
        } else {
            $data = [
                'code' => $de_raw['code'],
                'data' => [
                    'mid' => $de_raw['data']['mid'],
                    'refresh_token' => $de_raw['data']['refresh_token'],
                    'access_token' => $de_raw['data']['access_token'],
                    'expires_in' => date('Y-m-d H:i:s', $de_raw['ts'] + $de_raw['data']['expires_in']),
                ],
                'message' => '续签令牌: ' . $this->buildMsg($de_raw, '成功!')
            ];
        }
        return $this->buildData($data);
    }

    /**
     * @use checkToken
     * @param $access
     * @return false|string
     */
    public function checkToken($access)
    {
        $payload = [
            'access_token' => $access,
        ];
        $payload = $this->sign($payload);
        $url = 'https://passport.bilibili.com/api/oauth2/info';

        $raw = $this->client->get($url, $payload);
        $de_raw = json_decode($raw['content'], true);
        if (isset($de_raw['code']) && $de_raw['code']) {
            $data = [
                'code' => $de_raw['code'],
                'message' => $this->buildMsg($de_raw, '令牌验证失败!')
            ];
        } else {
            $data = [
                'code' => $de_raw['code'],
                'status' => $de_raw['data']['expires_in'] > 86400 ? 1 : 0,
                'message' => '令牌验证成功，有效期至: ' . date('Y-m-d H:i:s', $de_raw['ts'] + $de_raw['data']['expires_in'])
            ];
        }

        return $this->buildData($data);
    }

    /**
     * @use getRandomString
     * @param int $len
     * @param null $chars
     * @return string
     */
    private function getRandomString($len = 6, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000 * (double)microtime());
        for ($i = 0, $data = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
            $data .= $chars[mt_rand(0, $lc)];
        }
        return $data;
    }

    /**
     * @use getToken
     * @param $username
     * @param $password
     * @param string $captcha
     * @param string $cookie
     * @param array $params
     * @return array
     */
    private function getToken($username, $password, $captcha = '', $cookie = '', $params = [])
    {
        $payload = [
            'subid' => 1,
            'permission' => 'ALL',
            'username' => $username,
            'password' => $password,
            'captcha' => $captcha,
        ];
        $payload = $this->sign($payload);

        $url = 'https://passport.bilibili.com/api/v2/oauth2/login';

        $raw = $this->client->post($url, $payload, $params, $headers = [], $cookie);
        $de_raw = json_decode($raw['content'], true);

        if (isset($de_raw['code']) && $de_raw['code']) {
            $data = [
                'code' => $de_raw['code'],
                'message' => $this->buildMsg($de_raw, '登陆失败!')
            ];
        } elseif (isset($de_raw['code']) && $de_raw['code'] == -105) {
            $data = [
                'code' => $de_raw['code'],
                'message' => $this->buildMsg($de_raw, '该账号需要验证码登陆!')
            ];
        } else {
            $temp = '';
            $cookies = $de_raw['data']['cookie_info']['cookies'];
            foreach ($cookies as $cookie) {
                $temp .= $cookie['name'] . '=' . $cookie['value'] . ';';
            }
            $data = [
                'code' => $de_raw['code'],
                'data' => [
                    'uid' => $de_raw['data']['token_info']['mid'],
                    'userName' => $username,
                    'accessToken' => $de_raw['data']['token_info']['access_token'],
                    'refreshToken' => $de_raw['data']['token_info']['refresh_token'],
                    'cookieInfo' => $temp,
                    'csrfToken' => $de_raw['data']['cookie_info']['cookies'][0]['value'],
                    'expires_in' => date('Y-m-d H:i:s', $de_raw['ts'] + $de_raw['data']['token_info']['expires_in'])
                ],
                'message' => $this->buildMsg($de_raw, '账号登陆成功!')
            ];
        }

        return $data;
    }

    /**
     * @use getPublicKey
     * @return array
     */
    private function getPublicKey()
    {
        $payload = $this->sign([]);
        $url = 'https://passport.bilibili.com/api/oauth2/getKey';

        $raw = $this->client->post($url, $payload);
        $de_raw = json_decode($raw['content'], true);

        if (isset($de_raw['code']) && $de_raw['code']) {
            $data = [
                'code' => $de_raw['code'],
                'message' => $this->buildMsg($de_raw, '公匙获取失败!')
            ];
        } else {
            $data = [
                'code' => $de_raw['code'],
                'key' => $de_raw['data']['key'],
                'hash' => $de_raw['data']['hash'],
                'message' => $this->buildMsg($de_raw, '公匙获取成功!')
            ];
        }
        return $data;
    }

    /**
     * @use sign
     * @param $payload
     * @return array
     */
    private function sign($payload)
    {
        # iOS 6680
        $appkey = '27eb53fc9058f8c3';
        $appsecret = 'c2ed53a74eeefe3cf99fbd01d8c9c375';
        # Android
        // $appkey = '1d8b6e7d45233436';
        // $appsecret = '560c52ccd288fed045859ed18bffd973';
        # 云视听 TV
        // $appkey = '4409e2ce8ffd12b8';
        // $appsecret = '59b43e04ad6965f34319062b478f83dd';
        $default = [
            'appkey' => $appkey
        ];
        $payload = array_merge($payload, $default);
        if (isset($payload['sign'])) {
            unset($payload['sign']);
        }
        ksort($payload);
        $data = http_build_query($payload);
        $payload['sign'] = md5($data . $appsecret);
        return $payload;
    }

    /**
     * @use buildData
     * @param $data
     * @return false|string
     */
    private function buildData($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @use buildMsg
     * @param $data
     * @param $key
     * @param $msg
     * @return mixed
     */
    private function buildMsg($data, $msg, $key = 'message')
    {
        return array_key_exists($key, $data) ? $data['message'] : $msg;
    }
}
