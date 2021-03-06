<?php

namespace Jormin\BaiduSpeech;

use Jormin\BaiduSpeech\Libs\AipSpeech;

/**
 * 百度语音SDK库
 *
 * @package Jormin\BaiduSpeech
 */
class BaiduSpeech{

    private $appID, $apiKey, $secretKey;

    /**
     * BaiduSpeech constructor.
     *
     * @param $appID
     * @param $apiKey
     * @param $secretKey
     */
    public function __construct($appID, $apiKey, $secretKey)
    {
        $this->appID = $appID;
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
    }

    /**
     * 语音识别
     *
     * @param $filePath string 语音文件本地路径,优先使用此项
     * @param $url string 语音文件URL路径
     * @param $callback string 回调地址
     * @param $userID string 用户唯一标识
     * @param $format string 语音文件格式 ['pcm', 'wav', 'opus', 'speex', 'amr']
     * @param $rate integer 采样率 [8000, 16000]
     * @param $lan string 语音 ['zh', 'ct', 'en']
     * @return array
     */
    public function recognize($filePath, $url, $callback, $userID=null, $format='wav', $rate=16000, $lan='zh')
    {
        $return = ['success'=>false, 'msg'=>'网络超时'];
        if(!$filePath && !$url){
            $return['msg'] = '语音文件本地路径或URL路径需要至少提供一个';
            return $return;
        }
        if($filePath && !file_exists($filePath)){
            $return['msg'] = '语音文件路径错误';
            return $return;
        }
        if(!in_array($format, ['pcm', 'wav', 'opus', 'speex', 'amr'])){
            $return['msg'] = '语音文件格式错误,当前支持以下格式:pcm（不压缩）、wav、opus、speex、amr';
            return $return;
        }
        if(!in_array($rate, [8000, 16000])){
            $return['msg'] = '采样率错误，当前支持8000或者16000';
            return $return;
        }
        if(!in_array($lan, ['zh', 'ct', 'en'])){
            $return['msg'] = '语言错误，当前支持中文(zh)、粤语(ct)、英文(en)';
            return $return;
        }
        $aipSpeech = new AipSpeech($this->appID, $this->apiKey, $this->secretKey);
        $options = [
            'lan' => $lan
        ];
        if(!$filePath && $url){
            $options['url'] = $url;
        }
        if($callback){
            $options['callback'] = $callback;
        }
        if($userID){
            $options['cuid'] = $userID;
        }
        $response = $aipSpeech->asr($filePath ? file_get_contents($filePath) : null, $format, $rate, $options);
        if($response['err_no'] == 0){
            $return = [
                'success' => true,
                'msg' => '语音识别成功',
                'data' => $response['result']
            ];
        }else{
            $return['msg'] = '语音识别失败';
            $return['data'] = [
                'err_no' => $response['err_no'],
                'err_msg' => $response['err_msg']
            ];
        }
        return $return;
    }

    /**
     * 语音合成
     *
     * @param $storagePath string 存储路径
     * @param $text string 合成的文本
     * @param $userID string 用户唯一标识
     * @param $lan string 语音 ['zh']
     * @param $speed integer 语速，取值0-9，默认为5中语速
     * @param $pitch integer 音调，取值0-9，默认为5中语调
     * @param $volume integer 音量，取值0-15，默认为5中音量
     * @param $person integer 发音人选择, 0为女声，1为男声，3为情感合成-度逍遥，4为情感合成-度丫丫，默认为普通女
     * @return array
     */
    public function combine($storagePath, $text, $userID=null, $lan='zh', $speed=5, $pitch=5, $volume=5, $person=0)
    {
        $return = ['success'=>false, 'msg'=>'网络超时'];
        if(!$storagePath && !file_exists($storagePath)){
            $return['msg'] = '存储路径错误';
            return $return;
        }
        if(!$text){
            $return['msg'] = '缺少合成的文本';
            return $return;
        }
        if($speed<0 || $speed>9){
            $return['msg'] = '语速错误';
            return $return;
        }
        if($pitch<0 || $pitch>9){
            $return['msg'] = '音调错误';
            return $return;
        }
        if($volume<0 || $volume>15){
            $return['msg'] = '音量错误';
            return $return;
        }
        if($person<0 || $person>4){
            $return['msg'] = '发音人错误';
            return $return;
        }
        $aipSpeech = new AipSpeech($this->appID, $this->apiKey, $this->secretKey);
        $options = [
            'lan' => $lan,
            'spd' => $speed,
            'pit' => $pitch,
            'vol' => $volume,
            'per' => $person
        ];
        if(!$userID){
            $options['cuid'] = $userID;
        }
        $response = $aipSpeech->synthesis($text, $lan, 1, $options);
        if(!is_array($response)){
            $dir = $storagePath.'/'.date('Y').'/'.date('m').'/'.date('d');
            if(!file_exists($dir)){
                mkdir($dir, 0777, true);
            }
            $fileName = $dir.'/'.uniqid().'.mp3';
            file_put_contents($fileName, $response);
            chmod($fileName, 0777);
            $return = [
                'success' => true,
                'msg' => '语音合成成功',
                'data' => $fileName
            ];
        }else{
            $return['msg'] = '语音合成失败';
            $return['data'] = [
                'err_no' => $response['err_no'],
                'err_msg' => $response['err_msg']
            ];
        }
        return $return;
    }
}