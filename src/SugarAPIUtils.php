<?php
/*********************************************************************************
 *
 * Copyright (c) 2023 by KINAMU Business Solutions GmbH. All rights reserved.
 *
 * The code is licensed software and may only be used in alignment with the
 * License Agreement received with this software. This software is copyrighted
 * and may not be further distributed without any written consent of
 * KINAMU Business Solutions GmbH (c).
 *
 * You can contact KINAMU Business Solutions GmbH via email at office@kinamu.com
 *
 ********************************************************************************/

namespace SugarModulePackager;
class SugarAPIUtils
{
    private $url;
    private $user;
    private $password;
    private $token;
    private $package_id;
    public function __construct($url,$user,$password)
    {
        $this->url = $url;
        $this->user = $user;
        $this->password = $password;
        $this->token = $this->login();
    }

    private function uploadFile($file){

        if(empty($this->token)){
            PackageOutput::message('Login Failed');
            return;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->url."/rest/v11_17/Administration/packages",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                "upgrade_zip" => new CURLFile($file),
            ],
            CURLOPT_HTTPHEADER => [
                'OAuth-Token: ' . $this->token
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseBody = json_decode($response,true);
            $this->package_id = $responseBody['id'];
        } else {
            PackageOutput::message('Package Upload Failed');
        }
    }

    private function login(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url.'/rest/v11_17/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "grant_type" => "password",
                "client_id" => "sugar",
                "client_secret" => "",
                "username" => $this->user,
                "password" => $this->password,
                "platform" => "base",
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content: application/json',
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseBody = json_decode($response,true);
        return $responseBody['access_token'];
    }

    public function deployPackge($file){
        $this->uploadFile($file);
        if(empty($this->package_id)){
            PackageOutput::message('No Package to install');
            return;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $this->url.'/rest/v11_17/Administration/packages/'.$this->package_id.'/install',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'OAuth-Token: ' . $this->token,
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        PackageOutput::message('Package passed to Sugar for installation');
    }
}