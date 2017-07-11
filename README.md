# dk-alidayu

阿里大于短信 sdk

## NOTICE
迁移至阿里云后的短信服务不适用该 sdk ，请使用 dankal/dk-ali-sms

## Install

Via Composer

``` bash
$ composer require dankal/dk-alidayu
```

## Usage

```
use Dankal\DkAlidayu\AlidayuSms;
```

``` php
$sendSms = new AlidayuSms($appKey, $appSecret);
$sendSms->setRecNum("13100000000");
$sendSms->setSignName("注册验证");
$sendSms->setTemplateCode("SMS_7654321");
$sendSms->setSmsParam(["code" => "123321", "content" => "hello"]);
// or use config
//$sendSms->config("13100000000", "注册验证", "SMS_7654321", ["code" => '123321', "content" => "hello"]);
$result = $sendSms->send();
if (!$result) {
    $sendSms->getErrorCode();
    $sendSms->getErrorMessage();
}
```

## Credits

- [fioChen](https://github.com/fiochen)
- [Dankal](http://www.dankal.cn)
- [All Contributors](https://github.com/fiochen/dk-alidayu/contributors)

## License

The MIT License (MIT). 
