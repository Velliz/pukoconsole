<?php

namespace pukoconsole\util;

/**
 * Trait TestingToolkit
 * @package pukoconsole\util
 */
trait TestingToolkit
{

    /**
     * @param $bearer
     * @param $url
     * @param $method
     * @param $type
     * @param $data
     * @return mixed
     */
    public function SendRequest($bearer, $url, $method, $type, $data)
    {
        $curl = curl_init();
        $authorization = "Authorization: Bearer {$bearer}";
        curl_setopt($curl, CURLOPT_HTTPHEADER, array($authorization));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_USERAGENT, 'TestRequest');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if ($type === "JSON") {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $authorization));
        }
        if ($type === "DEF") {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);

        return $response;
    }

    /**
     * @param $request
     * @param $response
     */
    public function WriteDocs($request, $response)
    {
        if (!is_dir("{$_ENV['BASEDIR']}/_docs")) {
            mkdir("{$_ENV['BASEDIR']}/_docs");
        }
        if (file_exists("{$_ENV['BASEDIR']}/_docs/access.md")) {
            $data = file_get_contents("{$_ENV['BASEDIR']}/_docs/access.md");
        } else {
            $data = "";
        }

        $theData = json_encode($request['data'], JSON_PRETTY_PRINT);
        $response = json_encode($response, JSON_PRETTY_PRINT);

        $data .= "```{$request['url']}``` [{$request['method']}]\n";

        $data .= "\nRequest {$request['dataType']}\n";
        $data .= "\n```json\n";
        $data .= $theData;
        $data .= "\n```\n";

        $data .= "\nResponse\n";
        $data .= "\n```json\n";
        $data .= $response;
        $data .= "\n```\n";
        $data .= "\n---\n";

        file_put_contents("{$_ENV['BASEDIR']}/_docs/access.md", $data);
    }

}