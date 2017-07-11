<?php
/**
 * Created by PhpStorm.
 * User: fioChen
 * Date: 10/19/16
 * Time: 14:51
 *
 * @link http://open.taobao.com/docs/api.htm?apiId=25450 阿里大于api文档
 */

namespace Dankal\DkAlidayu;
use Dankal\DkAlidayu\AliTopClient;
use Dankal\DkAlidayu\AlidayuSmsNumSendRequest;

class AlidayuSms
{
    protected $appKey;
    protected $secretKey;
    protected $recNum;
    protected $signName;
    protected $templateCode;
    protected $smsParam; // must be array, example: ["code" => "1234", "product" => "dk"]

    protected $errorCode = '';
    protected $errorMessage = '';

    const SMS_TYPE = 'normal';
    const RESPONSE_KEY_SUCCESS = 'alibaba_aliqin_fc_sms_num_send_response';
    const RESPONSE_KEY_FAIL = 'error_response';

    public function __construct($appKey, $secretKey)
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;
    }

    public function setRecNum($recNum = '')
    {
        if (!empty($recNum)) {
            $this->recNum = $recNum;
        }
    }

    public function setSignName($signName = '')
    {
        if (!empty($signName)) {
            $this->signName = $signName;
        }
    }

    public function setTemplateCode($templateCode = '')
    {
        if (!empty($templateCode)) {
            $this->templateCode = $templateCode;
        }
    }


    public function setSmsParam($smsParam = [])
    {
        if (!empty($smsParam) && is_array($smsParam)) {
            $this->smsParam = $smsParam;
        }
    }

    public function config($recNum = '', $signName = '', $templateCode = '', $smsParam = [])
    {
        $this->setRecNum($recNum);
        $this->setSignName($signName);
        $this->setTemplateCode($templateCode);
        $this->setSmsParam($smsParam);
    }

    public function send()
    {
        $req = new AlidayuSmsNumSendRequest();
        $req->setSmsType(self::SMS_TYPE); // 固定normal
        $req->setRecNum($this->recNum); // 短信接收号码
        $req->setSmsFreeSignName($this->signName); // 短信签名
        $req->setSmsTemplateCode($this->templateCode); // 短信模板ID
        $req->setSmsParam(json_encode($this->smsParam)); // 模板变量

        // 发送请求
        $client = new AliTopClient($this->appKey, $this->secretKey);
        $respArray = $client->execute($req);

        // 处理结果
        if (isset($respArray[self::RESPONSE_KEY_SUCCESS]) &&
            $respArray[self::RESPONSE_KEY_SUCCESS]['result']['success']
        ) {
            $result = true;
        } else {
            if (isset($respArray[self::RESPONSE_KEY_FAIL])) {
                if (isset($respArray[self::RESPONSE_KEY_FAIL]['code'])) {
                    $this->errorCode = $respArray[self::RESPONSE_KEY_FAIL]['code'];
                    $this->errorMessage = $respArray[self::RESPONSE_KEY_FAIL]['msg'];
                }
                if (isset($respArray[self::RESPONSE_KEY_FAIL]['sub_code'])) {
                    $this->errorCode = $respArray[self::RESPONSE_KEY_FAIL]['sub_code'];
                    $this->errorMessage = $respArray[self::RESPONSE_KEY_FAIL]['sub_msg'];
                }
            }
            $result = false;
        }

        return $result;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
