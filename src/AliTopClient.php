<?php
/**
 * Created by PhpStorm.
 * User: fioChen
 * Date: 10/19/16
 * Time: 14:47
 */

namespace Dankal\DkAlidayu;

class AliTopClient
{
    public $appKey;

    public $secretKey;

    public $gatewayUrl = "http://gw.api.taobao.com/router/rest";

    public $format = "json";

    public $connectTimeout;

    public $readTimeout;

    protected $signMethod = "md5";

    protected $apiVersion = "2.0";

    protected $sdkVersion = "top-sdk-php-20151012";

    /**
     * AliTopClient constructor.
     *
     * @param string $appKey
     * @param string $secretKey
     */
    public function __construct($appKey = "", $secretKey = "")
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
    }

    /**
     * 排序参数, 生成md5签名
     *
     * @param $params
     *
     * @return string
     */
    protected function generateSign($params)
    {
        ksort($params);

        $stringToBeSigned = $this->secretKey;
        foreach ($params as $k => $v) {
            if (is_string($v) && "@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $this->secretKey;

        return strtoupper(md5($stringToBeSigned));
    }

    /**
     * @param      $url
     * @param null $postFields
     *
     * @return mixed
     * @throws \Exception
     */
    public function curl($url, $postFields = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->readTimeout);
        }
        if ($this->connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, "top-sdk-php");
        //https 请求
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($postFields) && 0 < count($postFields)) {
            $postBodyString = "";
            $postMultipart = false;
            foreach ($postFields as $k => $v) {
                if (!is_string($v)) {
                    continue;
                }

                //判断是不是文件上传
                if ("@" != substr($v, 0, 1)) {
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                } else {
                    $postMultipart = true;
                    if (class_exists('\CURLFile')) {
                        $postFields[$k] = new \CURLFile(substr($v, 1));
                    }
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                if (class_exists('\CURLFile')) {
                    curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            } else {
                $header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }
        $resp = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new \Exception($resp, $httpStatusCode);
            }
        }
        curl_close($ch);

        return $resp;
    }


    /**
     * @param $req
     *
     * @return array
     */
    public function execute($req)
    {
        // 组装系统参数
        $sysParams = [
            "app_key" => $this->appKey, //
            "v" => $this->apiVersion, // 2.0
            "format" => $this->format, // change to json
            "sign_method" => $this->signMethod, // md5
            "method" => $req->getApiMethodName(), //
            "timestamp" => date("Y-m-d H:i:s"), //
            "partner_id" => $this->sdkVersion, //
        ];

        // 获取业务参数
        $apiParams = $req->getApiParas();

        // 签名
        $sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams));


        $requestUrl = $this->gatewayUrl . "?";
        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1); // rtrim '&'

        // 发起HTTP请求
        try {
            $resp = $this->curl($requestUrl, $apiParams);
        } catch (\Exception $e) {
            $result = [
                'error_response' => [
                    'code' => $e->getCode(),
                    'msg'=> $e->getMessage(),
                    'sub_code' => "curl_error",
                    'sub_msg' => '请求失败',
                    'request_id' => '0'
                ]
            ];
            return $result;
        }

        unset($apiParams);
        unset($fileFields);

        $respArray = json_decode($resp, true);
        if (empty($respArray)) {
            $result = [
                'error_response' => [
                    'code' => '1',
                    'msg'=> "HTTP_RESPONSE_NOT_WELL_FORMED",
                    'sub_code' => "HTTP_RESPONSE_NOT_WELL_FORMED",
                    'sub_msg' => '服务器返回格式错误',
                    'request_id' => '0'
                ]
            ];

            return $result;
        }

        return $respArray;
    }

}
