<?php

declare(strict_types=1);

namespace Bot\Services;

use Bot\Handler;
use Bot\Services;
use http\Client;
use http\QueryString;

class Komitet extends Services
{
    protected $comparableCommands = [];

    public function execute(Handler $handler)
    {
        parent::execute($handler);

        $json = @file_get_contents('php://input');

        if ($json) {
            $json = @json_decode($json, true);

            if ($json) {
                $data = [
                    'text' => $json['data']['text'],
                    'dtCreated' => $json['data']['dtCreated'],
                    'channelId' => $json['data']['channel']['idOriginal'],
                    'subsiteId' => (int) $json['data']['author']['id'],
                ];

                $method = null;
                $response = null;
                $sendMessageResponse = null;

                $chatId = md5(sprintf('%s-%s', $data['subsiteId'], $data['channelId']));

                $text = mb_strtolower(trim($data['text']));

                $service_name = $this->getServiceName();
                $comparableCommand = $this->getComparableCommand($handler, $text);

                foreach ($this->getCommands() as $index => $command) {
                    if (
                        $command === trim($text, '/')
                        || $command === $comparableCommand
                    ) {
                        $method = $this->getMethods()[$index];
                        $response = $handler->{$method}($service_name, $chatId);
                        break;
                    }
                }

                if ($method === null) {
                    $response = $handler->onCatcher($service_name, $chatId, $data);
                }

                if (!$response) {
                    if (method_exists($handler, 'commandDefault')) {
                        $response = $handler->commandDefault($service_name, $chatId);
                    }
                }


                if ($response) {
                    $params = [
                        'text' => $response,
                        'channelId' => $data['channelId'],
                        'idTmp' => rand(1, 1000),
                        'ts' => number_format((float) $data['dtCreated'] + .001, 3, '.', ''),
                    ];

                    $this->sendMessage($params);
                }

                return json_encode($data);
            }
        }

        return null;
    }

    public function sendMessage(array $params): ?string
    {
        $sendMessageResponse = null;

        [$domain, $xDeviceToken] = explode(':', $this->token);

        $headers = [
            'User-Agent' => 'Bot. Library: pavelzotikov/bot',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            'x-device-token' => $xDeviceToken,
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf('https://%s/v1.9/m/send', $domain));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($curl);

        if ($response === false || curl_errno($curl)) {
            return null;
        }

        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
            return null;
        }

        curl_close($curl);

        return $response;
    }

    public function getComparableCommand(Handler $handler, string $command = null)
    {
        $comparable = [];

        $comparableCommands = $handler->comparableCommands ?: $this->comparableCommands;

        foreach ($comparableCommands as $key => $item) {
            foreach (explode(',', $item) as $item2) {
                $comparable[$item2] = $key;
            }
        }

        if (!isset($comparable[$command])) {
            return null;
        }

        return $comparable[$command];
    }
}
