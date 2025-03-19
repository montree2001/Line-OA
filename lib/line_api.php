<?php
class LineAPI {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    
    public function __construct($client_id, $client_secret, $redirect_uri) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect_uri = $redirect_uri;
    }
    
    // สร้าง URL สำหรับ LINE Login
    public function getLoginUrl($state) {
        $url = 'https://access.line.me/oauth2/v2.1/authorize';
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'state' => $state,
            'scope' => 'profile openid'
        ];
        
        return $url . '?' . http_build_query($params);
    }
    
    // รับ Token จาก LINE
    public function getToken($code) {
        $url = 'https://api.line.me/oauth2/v2.1/token';
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        if ($result === false) {
            return false;
        }
        
        $json = json_decode($result, true);
        return isset($json['access_token']) ? $json['access_token'] : false;
    }
    
    // รับข้อมูลผู้ใช้จาก LINE
    public function getUserProfile($token) {
        $url = 'https://api.line.me/v2/profile';
        $headers = ['Authorization: Bearer ' . $token];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        curl_close($ch);
        
        if ($result === false) {
            return false;
        }
        
        return json_decode($result, true);
    }
}
?>