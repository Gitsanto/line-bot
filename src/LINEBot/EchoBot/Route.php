<?php

/**
 * Copyright 2016 LINE Corporation
 *
 * LINE Corporation licenses this file to you under the Apache License,
 * version 2.0 (the "License"); you may not use this file except in compliance
 * with the License. You may obtain a copy of the License at:
 *
 *   https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace LINE\LINEBot\EchoBot;

use LINE\LINEBot;
use LINE\LINEBot\Constant\HTTPHeader;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\MessageEvent\TextMessage;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\Exception\UnknownEventTypeException;
use LINE\LINEBot\Exception\UnknownMessageTypeException;

class Route
{
    public function register(\Slim\App $app)
    {
        $app->post('/callback', function (\Slim\Http\Request $req, \Slim\Http\Response $res) {
            /** @var \LINE\LINEBot $bot */
            $bot = $this->bot;
            /** @var \Monolog\Logger $logger */
            $logger = $this->logger;

            $signature = $req->getHeader(HTTPHeader::LINE_SIGNATURE);
            if (empty($signature)) {
                return $res->withStatus(400, 'Bad Request');
            }

            // Check request with signature and parse request
            try {
                $events = $bot->parseEventRequest($req->getBody(), $signature[0]);
            } catch (InvalidSignatureException $e) {
                return $res->withStatus(400, 'Invalid signature');
            } catch (UnknownEventTypeException $e) {
                return $res->withStatus(400, 'Unknown event type has come');
            } catch (UnknownMessageTypeException $e) {
                return $res->withStatus(400, 'Unknown message type has come');
            } catch (InvalidEventRequestException $e) {
                return $res->withStatus(400, "Invalid event request");
            }

            foreach ($events as $event) {
                if (!($event instanceof MessageEvent)) {
                    $logger->info('Non message event has come');
                    continue;
                }

                

                $userId = $event->getUserId();
                $mesId = $event->getMessageId();

             $bot->pushMessage($userId, new LINEBot\MessageBuilder\TextMessageBuilder('push'));

             //for profile
             //$profile_response = $bot->getProfile($userId);

            // if ($profile_response->isSucceeded()) {
            //   $profile = $response->getJSONDecodedBody();
           //    echo $profile['displayName'];
            //   echo $profile['pictureUrl'];
             //  echo $profile['statusMessage'];
}

            //for file saving in own server and displaying it
             $response = $bot->getMessageContent($mesId);
             if ($response->isSucceeded()) {
               error_log('isSucceeded');//for checking error
               $tempfile = tmpfile();
               $fp = fopen(__DIR__ . '/../../../public/' . $mesId, 'w');//here it hasnot own website but our heroku is like website to use it
               fwrite($fp, $response->getRawBody());
               $bot->pushMessage($userId, new LINEBot\MessageBuilder\ImageMessageBuilder('https://raibeta.herokuapp.com/'.$mesId,'https://raibeta.herokuapp.com/'.$mesId));
             } else {
               error_log($response->getHTTPStatus() . ' ' . $response->getRawBody());
               $bot->pushMessage($userId, new LINEBot\MessageBuilder\TextMessageBuilder('unsuccess'));

             }

                //$replyText = $event->getText();
               // $logger->info('Reply text: ' . $replyText);
               // $resp = $bot->replyText($event->getReplyToken(), $replyText);
              //  $logger->info($resp->getHTTPStatus() . ': ' . $resp->getRawBody());
            }

            $res->write('OK');
            return $res;
        });
    }
}
