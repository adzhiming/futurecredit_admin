<?php
include "TopSdk.php";

use app\common\controller\AppResult;

/**
 * 阿里大鱼发送短信的
 * @author Yjp
 * @date 2017-08-22
 */
class sendMessageCode {
    /*
    const APPKEY = '23524665';
    const APPSECRECT = 'c994f172c42ef50a895bf28423e6f580';
    const SMS_DEFAULT_TEMPLATE = 'SMS_25700535'; //帮客来支付身份验证
    const SMS_DEFAULT_SIGN_NAME = '测试web短信'; //默认的签名
    private $topClient; //阿里大鱼的client 
*/
    const APPKEY = '23396438';
    const APPSECRECT = 'd2c5e50ee22a929a0261c0b42cf38ee5';
    const SMS_DEFAULT_TEMPLATE = 'SMS_88085006'; //帮客来支付身份验证
    const SMS_DEFAULT_SIGN_NAME = '聚合共享'; //默认的签名
    private $topClient; //阿里大鱼的client
    



    public function __construct($appKey = '', $appSecrect = '') {
        $appKey = empty($appKey) ?: self::APPKEY;
        $appSecrect = empty($appKey) ?: self::APPSECRECT;
        $this->topClient = new TopClient();
    }


    /**
     *
     *@param unknown $parmas 短信模板变量，传参规则{"key":"value"}，key的名字须和申请模板中的变量名一致，多个变量之间以逗号隔开。示例：针对模板“验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！”，传参时需传入{"code":"1234","product":"alidayu"}
     *@param unknown $phone 短信接收号码。支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，一次调用最多传入200个号码。示例：18600000000,13911111111,13322222222
     *@param string $extend 公共回传参数，在“消息返回”中会透传回该参数；举例：用户可以传入自己下级的会员ID，在消息返回时，该会员ID会包含在内，用户可以根据该会员ID识别是哪位会员使用了你的应用
     *@param string $sign 短信签名，传入的短信签名必须是在阿里大鱼“管理中心-短信签名管理”中的可用签名。如“阿里大鱼”已在短信签名管理中通过审核，则可传入”阿里大鱼“（传参时去掉引号）作为短信签名。短信效果示例：【阿里大鱼】欢迎使用阿里大鱼服务。
     *@param string $template 短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。示例：SMS_585014
     *@return Ambigous <unknown, ResultSet, mixed>
     *@return  Ambigous <unknown, ResultSet, mixed>
     *@author JHChan314
     *@date 2016年10月27日
     */
    public static function sendSMSMessage($parmas='', $phone ='', $extend = '', $sign = '', $template = '') {
        $appResult = new AppResult();
        if (!($parmas && $phone)) {
            $appResult->code = 4001;
            $appResult->msg = '缺少参数';
            $appResult->returnJSON();
        } 

        $topClient = new TopClient;
        $topClient->appkey = self::APPKEY;
        $topClient->secretKey = self::APPSECRECT;
        $req = new AlibabaAliqinFcSmsNumSendRequest;
        if ($extend)  $req->setExtend($extend);
        $req->setSmsType("normal");
        $req->setSmsFreeSignName($sign ?: self::SMS_DEFAULT_SIGN_NAME);
        $req->setSmsParam($parmas);
        $req->setRecNum($phone);
        $req->setSmsTemplateCode($template?:self::SMS_DEFAULT_TEMPLATE);
        $result = $topClient->execute($req);
        return $result;
    }

}