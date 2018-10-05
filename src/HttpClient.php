<?php
/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Version: 0.0.1
 */

namespace Lkeme;

/**
 * Http Client
 */
class HttpClient
{
    private $debug = false;

    /**
     * HttpClient
     * @param array $headers HTTP header
     */
    public function __construct($headers = array())
    {
        $this->headers = $this->buildHeaders($headers);
        $this->connectTimeout = 60000;
        $this->socketTimeout = 60000;
    }

    /**
     * 连接超时
     * @param int $ms 毫秒
     */
    public function setConnectionTimeoutInMillis($ms)
    {
        $this->connectTimeout = $ms;
    }

    /**
     * 响应超时
     * @param int $ms 毫秒
     */
    public function setSocketTimeoutInMillis($ms)
    {
        $this->socketTimeout = $ms;
    }

    /**
     * @param string $url
     * @param array $data
     * @param array $params
     * @param array $headers
     * @param null $cookie
     * @return array
     */
    public function post($url, $data = array(), $params = array(), $headers = array(), $cookie = null)
    {
        $url = $this->buildUrl($url, $params);
        $headers = array_merge($this->headers, $this->buildHeaders($headers));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
        if (!is_null($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($this->debug) {
            curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1"); //代理服务器地址
            curl_setopt($ch, CURLOPT_PROXYPORT, "8888"); //代理服务器端口
        }

        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code === 0) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);
        return array(
            'code' => $code,
            'content' => $content,
        );
    }

    /**
     * @param  string $url
     * @param  array $datas HTTP POST BODY
     * @param  array $params HTTP URL
     * @param  array $headers HTTP header
     * @return array
     */
    public function multi_post($url, $datas = array(), $params = array(), $headers = array())
    {
        $url = $this->buildUrl($url, $params);
        $headers = array_merge($this->headers, $this->buildHeaders($headers));

        $chs = array();
        $result = array();
        $mh = curl_multi_init();
        foreach ($datas as $data) {
            $ch = curl_init();
            $chs[] = $ch;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
            curl_multi_add_handle($mh, $ch);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            usleep(100);
        } while ($running);

        foreach ($chs as $ch) {
            $content = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result[] = array(
                'code' => $code,
                'content' => $content,
            );
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);

        return $result;
    }


    /**
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param null $cookie
     * @return array
     */
    public function get($url, $params = array(), $headers = array(), $cookie = null)
    {
        $url = $this->buildUrl($url, $params);
        $headers = array_merge($this->headers, $this->buildHeaders($headers));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
        if (!is_null($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($this->debug) {
            curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1"); //代理服务器地址
            curl_setopt($ch, CURLOPT_PROXYPORT, "8888"); //代理服务器端口
        }
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code === 0) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);
        return array(
            'code' => $code,
            'content' => $content,
        );
    }

    /**
     * 构造 header
     * @param  array $headers
     * @return array
     */
    private function buildHeaders($headers)
    {
        $result = array();
        foreach ($headers as $k => $v) {
            $result[] = sprintf('%s:%s', $k, $v);
        }
        return $result;
    }

    /**
     *
     * @param  string $url
     * @param  array $params 参数
     * @return string
     */
    private function buildUrl($url, $params)
    {
        if (!empty($params)) {
            $str = http_build_query($params);
            return $url . (strpos($url, '?') === false ? '?' : '&') . $str;
        } else {
            return $url;
        }
    }
}